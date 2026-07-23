<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$db_file = __DIR__ . '/../../../db.json';

// Helper para leer variables de entorno
function get_env_variable($key, $default = null) {
    $val = getenv($key);
    if ($val !== false) {
        return $val;
    }
    
    $env_path = __DIR__ . '/../../../../../.env';
    if (file_exists($env_path)) {
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                $value = trim($value, "\"'");
                if ($name === $key) {
                    return $value;
                }
            }
        }
    }
    return $default;
}

$expected_user = get_env_variable('ADMIN_USER', 'admin');
$expected_pass = get_env_variable('ADMIN_PASS', 'admin123');

// Solicitar autenticación Basic Auth
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || 
    $_SERVER['PHP_AUTH_USER'] !== $expected_user || $_SERVER['PHP_AUTH_PW'] !== $expected_pass) {
    header('WWW-Authenticate: Basic realm="Admin Area"');
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(array("success" => false, "error" => "Autenticación requerida."));
    exit;
}

// Obtener el JSON enviado
$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(array("success" => false, "error" => "ID de consulta no provisto."));
    exit;
}

$id = $data['id'];

// Leer base de datos local
$db = array();
if (file_exists($db_file)) {
    $db_content = file_get_contents($db_file);
    $db = json_decode($db_content, true);
    if (!is_array($db)) {
        $db = array();
    }
}

// Buscar y actualizar el elemento correspondiente
$found = false;
foreach ($db as &$q) {
    if ($q['id'] === $id) {
        if (isset($data['respondido'])) {
            $q['respondido'] = (bool)$data['respondido'];
        }
        if (isset($data['zona'])) {
            $q['zona'] = trim($data['zona']);
        }
        if (isset($data['notas'])) {
            $q['notas'] = trim($data['notas']);
        }
        if (isset($data['tipo_proyecto'])) {
            $q['tipo_proyecto'] = trim($data['tipo_proyecto']);
        }
        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(array("success" => false, "error" => "Consulta no encontrada."));
    exit;
}

// Guardar archivo JSON
if (file_put_contents($db_file, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(array("success" => false, "error" => "Error al guardar en la base de datos local."));
    exit;
}

echo json_encode(array("success" => true, "message" => "Consulta actualizada correctamente."));
?>
