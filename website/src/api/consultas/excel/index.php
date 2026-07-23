<?php
$db_file = __DIR__ . '/../../db.json';

// Helper para leer variables de entorno (desde panel de Hostinger o archivo .env)
function get_env_variable($key, $default = null) {
    $val = getenv($key);
    if ($val !== false) {
        return $val;
    }
    
    $env_path = __DIR__ . '/../../../../.env';
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
    echo 'Autenticación requerida.';
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

// Ordenar las consultas por fecha (más nuevas arriba)
usort($db, function($a, $b) {
    return strcmp($b['fecha'], $a['fecha']);
});

// Cabecera UTF-8 BOM para soporte de acentos en Excel
$csv_content = "\xEF\xBB\xBF";

// Encabezados del CSV
$csv_content .= '"Fecha","Nombre","Teléfono","Email","Tipo de Proyecto","Mensaje"' . "\r\n";

// Helper para escapar comillas
function escape_csv_str($val) {
    return '"' . str_replace('"', '""', $val) . '"';
}

// Rellenar filas
foreach ($db as $q) {
    // Formatear fecha legible
    $date_formatted = date('d/m/Y H:i:s', strtotime($q['fecha']));
    
    $csv_content .= escape_csv_str($date_formatted) . ','
                 . escape_csv_str($q['nombre']) . ','
                 . escape_csv_str($q['telefono']) . ','
                 . escape_csv_str($q['email']) . ','
                 . escape_csv_str($q['tipo_proyecto']) . ','
                 . escape_csv_str($q['mensaje']) . "\r\n";
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=consultas_rojas_construcciones.csv');
echo $csv_content;
?>
