<?php
require_once __DIR__ . '/../../inc/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'msg' => 'Método no permitido']);
    exit;
}

$setting = $_POST['setting_name'] ?? '';
$value   = $_POST['value'] ?? '';

if (!$setting) {
    echo json_encode(['status' => 'error', 'msg' => 'Falta el parámetro setting_name']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Si existe, actualiza; si no, crea el registro
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM system_settings WHERE setting_name = ?");
    $stmt->execute([$setting]);
    $existe = $stmt->fetchColumn();

    if ($existe) {
        $stmt = $pdo->prepare("UPDATE system_settings SET value = ? WHERE setting_name = ?");
        $stmt->execute([$value, $setting]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_name, value) VALUES (?, ?)");
        $stmt->execute([$setting, $value]);
    }

    echo json_encode(['status' => 'ok', 'msg' => 'Guardado correctamente']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
}
