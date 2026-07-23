<?php
$vps_url = 'http://187.77.224.159:8181/api/consultas';

$headers = array();
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $credentials = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
    $headers[] = 'Authorization: Basic ' . $credentials;
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $headers[] = 'Authorization: ' . $_SERVER['HTTP_AUTHORIZATION'];
}

$ch = curl_init($vps_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 401) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    http_response_code(401);
    echo 'Autenticación requerida.';
    exit;
}

http_response_code($http_code);
header('Content-Type: application/json');
echo $response;
?>
