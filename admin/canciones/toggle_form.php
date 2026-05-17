<?php
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    //  MODO PERMANENTE
    if (isset($_POST['permanente'])) {
        $valor = $_POST['permanente'] === 'true' ? 'true' : 'false';

        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_name, value)
            VALUES ('formulario_permanente', ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $stmt->execute([$valor]);

        echo json_encode(['status' => 'ok', 'msg' => $valor === 'true' 
            ? 'El formulario ha sido desactivado permanentemente.' 
            : 'El formulario permanente ha sido reactivado.']);
        exit;
    }

    //  SWITCH NORMAL (YA EXISTENTE)
    $estado = $_POST['estado'] ?? 'true';
    $horaFin = $_POST['hora_fin'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_name, value)
        VALUES ('formulario_publico', ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ");
    $stmt->execute([$estado]);

    // Si hay hora programada
    if (!$estado || $estado === 'false') {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_name, value)
            VALUES ('formulario_reactivacion', ?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $stmt->execute([$horaFin]);
        echo json_encode(['status' => 'ok', 'msg' => 'Formulario desactivado hasta las ' . $horaFin]);
    } else {
        $pdo->exec("DELETE FROM system_settings WHERE setting_name = 'formulario_reactivacion'");
        echo json_encode(['status' => 'ok', 'msg' => 'Formulario activado correctamente']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'msg' => 'Error: ' . $e->getMessage()]);
}



