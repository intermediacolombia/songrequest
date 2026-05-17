<?php
require_once __DIR__ . '/../inc/config.php';

header('Content-Type: application/json');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Obtener géneros bloqueados desde system_settings
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name = 'generos_bloqueados'");
    $stmt->execute();
    $bloqueadosJson = $stmt->fetchColumn();
    
    $generosBloqueados = $bloqueadosJson ? json_decode($bloqueadosJson, true) : [];
    
    // Lista completa de géneros disponibles
    $generosCompletos = ['Vallenato', 'Popular', 'Salsa', 'Merengue'];
    
    // Filtrar solo los géneros activos (no bloqueados)
    $generosActivos = array_diff($generosCompletos, $generosBloqueados);
    
    echo json_encode([
        'status' => 'success',
        'generos_activos' => array_values($generosActivos),
        'generos_bloqueados' => $generosBloqueados
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
