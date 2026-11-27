<?php

const URL_Servi = 'https://192.168.4.33:50000/b1s/v1/';

function LoginSAP1() {
    $usuario = 'manager';
    $contrasena = 'tama0120';
	$con = 'SBO_TAMAING_PRODUC';

    $url_login = URL_Servi . 'Login';
    $datos_login = json_encode(array("CompanyDB" => $con, "UserName" => $usuario, "Password" => $contrasena));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_login);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datos_login);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($datos_login)
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $respuesta_login = curl_exec($ch);
    curl_close($ch);

    $datos_respuesta_login = json_decode($respuesta_login, true);
    $token_sesion = $datos_respuesta_login["SessionId"];

    return $token_sesion;
}

$SESION=LoginSAP1() ;

if(!empty($SESION)){

$datos = json_decode($SESION, TRUE);


}