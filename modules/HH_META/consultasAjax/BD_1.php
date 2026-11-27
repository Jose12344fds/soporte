<?php
// Ajusta la ruta según sea necesario
$file_path = 'conexionloginSap.php';
include_once $file_path;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos enviados en el POST
    $var_json = $_POST['var_json'] ?? null;
    $ord_dato = $_POST['ord_dato'] ?? null;

    // Verificar que las variables no sean nulas
    if ($var_json && $ord_dato) {
        // Intentar realizar el proceso de actualización
        $token_sesion = LoginSAP1();  // Iniciar sesión en SAP

        // Asegúrate de que $var_json esté bien formateado y sea un JSON válido
        $var_json_decoded = json_decode($var_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'JSON inválido: ' . json_last_error_msg()]);
            exit;
        }

        // Realizar la actualización de salida de mercancia en SAP
        $sap_result = ActualizarSalidaMercancia($token_sesion, $var_json_decoded, $ord_dato);

        // Retornar el resultado de la actualización
        echo json_encode(['success' => strpos($sap_result, 'éxito') !== false, 'message' => $sap_result]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Datos insuficientes.']);
    }
} else {
    echo json_encode(['error' => 'Método no permitido o formulario no enviado.']);
}

// Función para actualizar la salida de mercancia en SAP
function ActualizarSalidaMercancia($token_sesion, $var_json, $ord_dato) {
    // URL para actualizar la orden de producción en SAP
      $url_actualizar_salida = "https://192.168.4.33:50000/b1s/v1/ProductTrees('" . $ord_dato . "')";  //
    
    // Convertir el JSON a un string adecuado para la solicitud
    $Campos1 = json_encode($var_json);

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_actualizar_salida);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $Campos1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($Campos1),
        'Cookie: B1SESSION=' . $token_sesion
    ));

    // Desactivar verificación SSL (usado solo para desarrollo, no recomendado en producción)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // Ejecutar la solicitud cURL y capturar la respuesta
    $data = curl_exec($ch);

    // Verificar si la solicitud fue exitosa
    if ($data === false) {
        $error_message = curl_error($ch);
        curl_close($ch);
        return 'Error en la solicitud cURL: ' . $error_message;
    }

    // Decodificar la respuesta JSON de SAP
    $response = json_decode($data, true);
    curl_close($ch);

    // Verificar si SAP devolvió un error
    if (isset($response['error'])) {
        return 'Error en la actualización SAP: ' . $response['error']['message']['value'];
    }

    // Si todo fue correcto, retornar un mensaje de éxito
    return 'Solicitud enviada con éxito.';
}
?>