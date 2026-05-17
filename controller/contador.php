<?php
require_once __DIR__ . '/../inc/config.php';



$cookie_id = $_COOKIE['solicitud_id'] ?? '';

if (!$cookie_id) {
    echo "<span class='text-danger'>Error de identificación</span>";
    exit;
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Consultar las solicitudes del usuario
    $stmt = $pdo->prepare("SELECT bloqueada FROM solicitudes WHERE cookie_id = ?");
    $stmt->execute([$cookie_id]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $bloqueadas = 0;
    $tieneDesbloqueadas = false;

    foreach ($solicitudes as $b) {
        if ((int)$b === 1) $bloqueadas++;
        if ((int)$b === 0) $tieneDesbloqueadas = true;
    }

    // Lógica igual que el index
    $puedeEnviar = ($bloqueadas < $numeroLimite) || $tieneDesbloqueadas;
    $restantes = max(0, $numeroLimite - $bloqueadas);

    if ($puedeEnviar) {
        echo "Te quedan <strong>{$restantes}</strong> " . ($restantes == 1 ? "solicitud" : "solicitudes") . " disponibles.";
    } else {
        echo "<div class='text-center mb-4'>
    <h2 class='fw-bold text-danger mb-3'>Has Llegado al limite de $numeroLimite solicitudes</h2>
    <p>Por favor, espera a que el administrador las desbloquee o se libere un cupo para enviar nuevas canciones.</p>
  </div>";
    }

} catch (PDOException $e) {
    echo "<span class='text-danger'>Error BD: ".$e->getMessage()."</span>";
}

