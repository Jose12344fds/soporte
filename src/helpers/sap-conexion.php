<?php
/**
 * Helper para conexiones a SAP HANA / SAP B1
 * Proporciona funciones para conectar y consultar SAP HANA
 */

class SAPHANAConnection {
    private $connection = null;
    private $dsn = 'Driver={HDBODBC};ServerNode=192.168.250.32:39013;';
    private $user = 'SYSTEM';
    private $password = 'tama0120';
    private $database = 'SYSTEMDB';
    
    // Configuración alternativa vía API REST
    private $sap_url = 'https://192.168.250.32:50000/b1s/v1/';
    private $sap_user = 'manager';
    private $sap_password = 'tama0120';
    private $sap_company = 'SBO_TAMAING_PRODUC';
    private $session_id = null;
    
    /**
     * Conectar a SAP HANA vía ODBC
     */
    public function conectarODBC() {
        try {
            $this->connection = odbc_connect($this->dsn, $this->user, $this->password);
            if (!$this->connection) {
                throw new Exception('Error al conectar a SAP HANA vía ODBC: ' . odbc_error());
            }
            return $this->connection;
        } catch (Exception $e) {
            error_log('HANA ODBC Connection Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Conectar a SAP B1 vía API REST
     */
    public function conectarREST() {
        try {
            $login_data = json_encode([
                'CompanyDB' => $this->sap_company,
                'UserName' => $this->sap_user,
                'Password' => $this->sap_password
            ]);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->sap_url . 'Login');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $login_data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($login_data)
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code !== 200) {
                throw new Exception('Error al conectar a SAP REST API: HTTP ' . $http_code);
            }
            
            $response_data = json_decode($response, true);
            if (!isset($response_data['SessionId'])) {
                throw new Exception('No SessionId en respuesta de SAP');
            }
            
            $this->session_id = $response_data['SessionId'];
            return true;
        } catch (Exception $e) {
            error_log('SAP REST Connection Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Ejecutar consulta SQL a SAP HANA vía ODBC
     */
    public function queryODBC($sql) {
        if (!$this->connection) {
            $this->conectarODBC();
        }
        
        try {
            $result = odbc_exec($this->connection, $sql);
            if ($result === false) {
                throw new Exception('Error en consulta ODBC: ' . odbc_error());
            }
            
            $data = [];
            while ($row = odbc_fetch_array($result)) {
                $data[] = $row;
            }
            odbc_free_result($result);
            
            return $data;
        } catch (Exception $e) {
            error_log('ODBC Query Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Ejecutar consulta a SAP vía OData API
     */
    public function queryREST($endpoint, $filters = []) {
        if (!$this->session_id) {
            $this->conectarREST();
        }
        
        try {
            $url = $this->sap_url . $endpoint;
            if (!empty($filters)) {
                $url .= "?" . http_build_query($filters);
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Cookie: B1SESSION=' . $this->session_id
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code !== 200) {
                throw new Exception('Error en consulta REST API: HTTP ' . $http_code);
            }
            
            $response_data = json_decode($response, true);
            return $response_data;
        } catch (Exception $e) {
            error_log('REST Query Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Obtener lista de proyectos (OT) desde SAP
     * Filtra por horas de fabricación
     */
    public function obtenerProyectos($filtroHoras = 'mayor') {
        try {
            // Primero intenta vía API REST
            $resultado = $this->obtenerProyectosREST($filtroHoras);
            if (!empty($resultado)) {
                return $resultado;
            }
            
            // Si falla, intenta ODBC
            return $this->obtenerProyectosODBC($filtroHoras);
        } catch (Exception $e) {
            error_log('Error obtenerProyectos: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener proyectos vía API REST
     */
    private function obtenerProyectosREST($filtroHoras = 'mayor') {
        try {
            // OData query para WorkOrders
            $filters = [
                '$format' => 'json',
                '$filter' => "Status eq 'O'" // Status Open
            ];
            
            if ($filtroHoras === 'mayor') {
                $filters['$filter'] .= " and PlannedWorkHours gt 300";
            } else if ($filtroHoras === 'menor') {
                $filters['$filter'] .= " and PlannedWorkHours lt 300";
            }
            
            $result = $this->queryREST('WorkOrders', $filters);
            
            if (isset($result['value'])) {
                return array_map(function($item) {
                    return [
                        'OT' => $item['DocumentNumber'] ?? '',
                        'Proyecto1' => $item['Name'] ?? '',
                        'Nivelproyecto' => $item['Project'] ?? '',
                        'TotalPesPptoTotaKg' => $item['Weight'] ?? 0,
                        'PeNetTotKg' => $item['NetWeight'] ?? 0,
                        'HHFab' => $item['PlannedWorkHours'] ?? 0,
                    ];
                }, $result['value']);
            }
            
            return [];
        } catch (Exception $e) {
            error_log('REST obtenerProyectos Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Obtener proyectos vía ODBC
     */
    private function obtenerProyectosODBC($filtroHoras = 'mayor') {
        try {
            $sql = 'SELECT * FROM "SBO_TAMAING_PRODUC"."OWOR" T0 WHERE T0."Status" = \'O\'';
            
            if ($filtroHoras === 'mayor') {
                $sql .= ' AND T0."PlannedLabor" > 300';
            } else if ($filtroHoras === 'menor') {
                $sql .= ' AND T0."PlannedLabor" < 300';
            }
            
            $result = $this->queryODBC($sql);
            
            return array_map(function($item) {
                return [
                    'OT' => $item['DocNum'] ?? '',
                    'Proyecto1' => $item['Name'] ?? '',
                    'Nivelproyecto' => $item['Project'] ?? '',
                    'TotalPesPptoTotaKg' => $item['Weight'] ?? 0,
                    'PeNetTotKg' => $item['NetWeight'] ?? 0,
                    'HHFab' => $item['PlannedLabor'] ?? 0,
                ];
            }, $result);
        } catch (Exception $e) {
            error_log('ODBC obtenerProyectos Error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Desconectar
     */
    public function desconectar() {
        if ($this->connection) {
            odbc_close($this->connection);
            $this->connection = null;
        }
    }
    
    public function __destruct() {
        $this->desconectar();
    }
}

/**
 * Función auxiliar para obtener proyectos rápidamente
 */
function obtenerProyectosSAP($filtroHoras = 'mayor') {
    try {
        $sap = new SAPHANAConnection();
        $proyectos = $sap->obtenerProyectos($filtroHoras);
        $sap->desconectar();
        return $proyectos;
    } catch (Exception $e) {
        error_log('obtenerProyectosSAP Error: ' . $e->getMessage());
        return [];
    }
}
?>
