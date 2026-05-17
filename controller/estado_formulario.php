<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    // Consultar estado actual
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name='formulario_publico'");
    $stmt->execute();
    $activo = $stmt->fetchColumn() === 'true';

    // Consultar hora de reactivación (si existe)
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name='formulario_reactivacion'");
    $stmt->execute();
    $horaFin = $stmt->fetchColumn();

    // Si hay una hora guardada, validar si ya pasó
    if ($horaFin) {
        $horaActual = date('H:i');
        if ($horaActual >= $horaFin) {
            // Reactivar automáticamente
            $pdo->prepare("
                INSERT INTO system_settings (setting_name, value)
                VALUES ('formulario_publico', 'true')
                ON DUPLICATE KEY UPDATE value = 'true'
            ")->execute();

            // Borrar hora de reactivación
            $pdo->prepare("DELETE FROM system_settings WHERE setting_name = 'formulario_reactivacion'")->execute();

            $activo = true;
            $horaFin = null;
        }
    }

    // Verificar cupo global (Pendiente + Programada)
    $stmtCupo = $pdo->prepare("SELECT COUNT(*) FROM solicitudes WHERE estado IN ('Pendiente','Programada')");
    $stmtCupo->execute();
    $cupoActual = (int)$stmtCupo->fetchColumn();
    $cupoGlobal = isset($sys['cupo_global']) ? (int)$sys['cupo_global'] : 10;
    $cupoLleno  = ($cupoActual >= $cupoGlobal);

    echo json_encode(['activo' => $activo, 'hora_fin' => $horaFin, 'cupo_lleno' => $cupoLleno]);
} catch (Exception $e) {
    echo json_encode(['activo' => true, 'hora_fin' => null, 'cupo_lleno' => false]);
}

