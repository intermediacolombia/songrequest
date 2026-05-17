<?php
require_once __DIR__ . '/../inc/config.php';

if (!isset($_COOKIE['solicitud_id'])) {
    die("<p class='text-center text-danger'>Error: No se pudo identificar tu dispositivo.</p>");
}

$cookie_id = $_COOKIE['solicitud_id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE cookie_id = ? ORDER BY id DESC");
    $stmt->execute([$cookie_id]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include __DIR__ . '/../includes/tabla_solicitudes.php';

} catch (PDOException $e) {
    echo "<p class='text-danger text-center'>Error BD: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

