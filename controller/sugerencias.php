<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/config.php';

// Limpiar cualquier texto previo
if (ob_get_length()) ob_clean();

date_default_timezone_set('America/Bogota');

$tipo     = $_GET['tipo'] ?? '';
$busqueda = trim($_GET['q'] ?? '');

if ($busqueda === '') {
    echo json_encode([]);
    exit;
}

try {
    // Conexión manual, igual que en guardar.php
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $dbuser,
        $dbpass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    if ($tipo === 'artista') {
        $stmt = $pdo->prepare("
            SELECT nombre 
            FROM sugerencias_artistas 
            WHERE nombre LIKE ? 
            ORDER BY conteo DESC 
            LIMIT 10
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT nombre 
            FROM sugerencias_canciones 
            WHERE nombre LIKE ? 
            ORDER BY conteo DESC 
            LIMIT 10
        ");
    }

    $stmt->execute(["%{$busqueda}%"]);
    $sugerencias = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode($sugerencias);
    exit;

} catch (PDOException $e) {
    echo json_encode([]);
    exit;
}

