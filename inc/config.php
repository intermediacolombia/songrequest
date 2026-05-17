<?php
// ================================================
// CONFIGURACIÓN GENERAL - SONG REQUEST
// ================================================

// Zona horaria Colombia
date_default_timezone_set('America/Bogota');

/* ========= Credenciales DB (solo si no existen) ========= */
if (!isset($host))   $host   = 'localhost';
if (!isset($dbname)) $dbname = 'intermed_songrequest';
if (!isset($dbuser)) $dbuser = 'intermed_songrequest';
if (!isset($dbpass)) $dbpass = '-kGvYjZf^Ql&Lc8';

/* ========= Autoload (si usas Composer) ========= */
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

/* ========= Conexión PDO (única instancia global) ========= */
if (!isset($GLOBALS['pdo']) || !($GLOBALS['pdo'] instanceof PDO)) {
    try {
        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
        $GLOBALS['pdo'] = new PDO($dsn, $dbuser, $dbpass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $GLOBALS['pdo']->exec("SET NAMES 'utf8mb4'");
    } catch (PDOException $e) {
        die('Error de conexión a la base de datos.');
    }
}

$pdo = $GLOBALS['pdo'];

/* ========= Cargar ajustes del sistema ========= */
if (!isset($GLOBALS['SYS_SETTINGS'])) {
    try {
        $stmt = $pdo->query("SELECT setting_name, value FROM system_settings");
        $GLOBALS['SYS_SETTINGS'] = [];
        foreach ($stmt->fetchAll() as $row) {
            $GLOBALS['SYS_SETTINGS'][$row['setting_name']] = $row['value'];
        }
    } catch (Throwable $e) {
        $GLOBALS['SYS_SETTINGS'] = [];
    }
}

$sys = $GLOBALS['SYS_SETTINGS'];

/* ========= Constantes base ========= */
if (!defined('URLBASE'))   define('URLBASE', 'https://songrequest.intermediacolombia.com');
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

$url = URLBASE;
$numeroLimite = isset($sys['limite_solicitudes']) ? (int)$sys['limite_solicitudes'] : 3;

/* ========= Configuraciones específicas ========= */
define('SITE_TITLE', $sys['site_title'] ?? 'Song Request');
define('SITE_LOGO', $sys['site_logo'] ?? '');
define('FORMULARIO_PUBLICO', $sys['formulario_publico'] ?? 'true');

/* ========= Helpers globales ========= */
if (!function_exists('getSetting')) {
    function getSetting(string $name): ?string {
        global $pdo;
        $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name = :name");
        $stmt->execute(['name' => $name]);
        return $stmt->fetchColumn() ?: null;
    }
}

if (!function_exists('setSetting')) {
    function setSetting(string $name, string $value): bool {
        global $pdo;
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_name, value)
            VALUES (:name, :value)
            ON DUPLICATE KEY UPDATE value = :value
        ");
        return $stmt->execute(['name' => $name, 'value' => $value]);
    }
}
