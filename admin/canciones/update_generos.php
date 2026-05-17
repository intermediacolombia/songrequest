<?php
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../login/session.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido']);
    exit;
}

$generosBloqueados = isset($_POST['generos_bloqueados']) ? $_POST['generos_bloqueados'] : '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Guardar como JSON
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_name, value) 
        VALUES ('generos_bloqueados', ?) 
        ON DUPLICATE KEY UPDATE value = ?
    ");
    
    $stmt->execute([$generosBloqueados, $generosBloqueados]);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Géneros actualizados correctamente'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error al actualizar: ' . $e->getMessage()
    ]);
}
