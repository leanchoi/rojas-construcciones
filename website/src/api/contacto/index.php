<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$db_file = __DIR__ . '/../db.json';

// Helper para leer variables de entorno (desde panel de Hostinger o archivo .env)
function get_env_variable($key, $default = null) {
    // 1. Intentar leer del sistema/panel de Hostinger
    $val = getenv($key);
    if ($val !== false) {
        return $val;
    }
    
    // 2. Fallback: Intentar leer de un archivo .env en la raíz del public_html
    $env_path = __DIR__ . '/../../../.env';
    if (file_exists($env_path)) {
        $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $name = trim($parts[0]);
                $value = trim($parts[1]);
                // Remover comillas si existen
                $value = trim($value, "\"'");
                if ($name === $key) {
                    return $value;
                }
            }
        }
    }
    return $default;
}

// Obtener el JSON enviado por el cliente
$input_data = file_get_contents('php://input');
$data = json_decode($input_data, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(array("success" => false, "error" => "Datos inválidos o vacíos."));
    exit;
}

$name = isset($data['name']) ? trim($data['name']) : '';
$phone = isset($data['phone']) ? trim($data['phone']) : '';
$email = isset($data['email']) ? trim($data['email']) : 'No especificado';
$project_type = isset($data['tipo_obra']) ? trim($data['tipo_obra']) : (isset($data['projectType']) ? trim($data['projectType']) : 'No especificado');
$message = isset($data['message']) ? trim($data['message']) : '';

if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(array("success" => false, "error" => "Nombre y Teléfono son requeridos."));
    exit;
}

// Leer base de datos local
$db = array();
if (file_exists($db_file)) {
    $db_content = file_get_contents($db_file);
    $db = json_decode($db_content, true);
    if (!is_array($db)) {
        $db = array();
    }
}

$new_query = array(
    "id" => uniqid() . bin2hex(random_bytes(2)),
    "fecha" => date('c'),
    "nombre" => $name,
    "telefono" => $phone,
    "email" => $email,
    "tipo_proyecto" => $project_type,
    "mensaje" => $message
);

// Insertar al inicio de la lista
array_unshift($db, $new_query);

// Guardar archivo JSON
if (file_put_contents($db_file, json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
    http_response_code(500);
    echo json_encode(array("success" => false, "error" => "Error al escribir en la base de datos local."));
    exit;
}

// Enviar a Google Sheets si el webhook está configurado
$webhook_url = get_env_variable('GOOGLE_SHEETS_WEBHOOK');
if (!empty($webhook_url)) {
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array(
        "type" => "contact",
        "id" => $new_query['id'],
        "name" => $new_query['nombre'],
        "email" => $new_query['email'],
        "phone" => $new_query['telefono'],
        "subject" => "Proyecto: " . $new_query['tipo_proyecto'],
        "message" => $new_query['mensaje']
    )));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    // Enviar de fondo (fire-and-forget con timeout bajo para no demorar la respuesta de la web)
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    curl_close($ch);
}

echo json_encode(array("success" => true, "message" => "Consulta registrada correctamente."));
?>
