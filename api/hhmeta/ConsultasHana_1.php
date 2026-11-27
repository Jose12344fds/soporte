<?php
header('Content-Type: application/json; charset=utf-8');
include_once 'ConsultasHana.php';

$logFile = dirname(__FILE__) . '/consultas_debug.log';

 

        $horaOption = 'todos';
        file_put_contents($logFile, "Opción: " . $horaOption . "\n", FILE_APPEND);
        
        $consultas = new Consultas();
        file_put_contents($logFile, "Clase Consultas instanciada\n", FILE_APPEND);
        
        // Si es "todos", obtener todos sin filtro
        if ($horaOption === 'todos') {
            $data = $consultas->obtenerProyectos4();
        } else {
            $data = $consultas->obtenerProyectos($horaOption);
        }
        
        file_put_contents($logFile, "Datos obtenidos: " . count($data) . " registros\n", FILE_APPEND);
        file_put_contents($logFile, "Datos: " . json_encode($data) . "\n", FILE_APPEND);
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
   

exit;