<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/config.php';
// Zona horaria Bogotá
date_default_timezone_set('America/Bogota');
if (!isset($_COOKIE['solicitud_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'No se pudo identificar tu dispositivo.']);
    exit;
}
$cookie_id = $_COOKIE['solicitud_id'];
$nombre   = trim($_POST['nombre'] ?? '');
$cancion  = trim($_POST['cancion'] ?? '');
$artista  = trim($_POST['artista'] ?? '');
$genero   = trim($_POST['genero'] ?? '');
if ($nombre === '' || $cancion === '' || $artista === '' || $genero === '') {
    echo json_encode(['status' => 'error', 'message' => 'Faltan datos obligatorios.']);
    exit;
}
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    // 🔹 1. Verificar límite de canciones pendientes del dispositivo
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM solicitudes WHERE cookie_id = ? AND estado = 'Pendiente'");
    $stmt->execute([$cookie_id]);
    $pendientes = (int)$stmt->fetchColumn();
    if ($pendientes >= $numeroLimite) {
        echo json_encode([
            'status' => 'limit',
            'message' => 'Ya tienes ' .$numeroLimite. ' canciones pendientes de sonar. Espera a que suenen para poder solicitar mas.'
        ]);
        exit;
    }

    // 🔹 1b. Verificar cupo global (Pendiente + Programada)
    $cupoGlobal = isset($sys['cupo_global']) ? (int)$sys['cupo_global'] : 10;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM solicitudes WHERE estado IN ('Pendiente','Programada')");
    $stmt->execute();
    $totalActivos = (int)$stmt->fetchColumn();
    if ($totalActivos >= $cupoGlobal) {
        echo json_encode([
            'status' => 'cupo_lleno',
            'message' => 'El listado está completo en este momento. Espera a que suenen algunas canciones e intenta nuevamente.'
        ]);
        exit;
    }

    // 🔹 2. Verificar si la canción ya existe globalmente (en CUALQUIER estado)
    // Verificamos tanto canción como artista para mayor precisión
    $stmt = $pdo->prepare("SELECT estado FROM solicitudes WHERE LOWER(cancion) = LOWER(?) AND LOWER(artista) = LOWER(?) ORDER BY id DESC LIMIT 1");
    $stmt->execute([$cancion, $artista]);
    $cancionExistente = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($cancionExistente) {
        $estado = $cancionExistente['estado'];
        
        // Mensajes personalizados según el estado
        $mensajes = [
            'Pendiente'   => 'Esta cancion ya fue solicitada y esta pendiente de sonar',
            'Programada'  => 'Esta cancion ya esta programada para sonar pronto',
            'Sonada'      => 'Esta cancion ya sono recientemente',
            'Rechazada'   => 'Esta cancion fue rechazada previamente'
        ];
        
        echo json_encode([
            'status' => 'duplicate',
            'message' => $mensajes[$estado] ?? 'Esta cancion ya fue registrada en el sistema.'
        ]);
        exit;
    }
	
	
	
	// GUARDAR ARTISTA EN SUGERENCIAS
    try {
        $stmt = $pdo->prepare("INSERT INTO sugerencias_artistas (nombre) VALUES (?) 
                              ON DUPLICATE KEY UPDATE conteo = conteo + 1");
        $stmt->execute([$artista]);
    } catch (PDOException $e) {
        error_log('Error sugerencias_artistas: ' . $e->getMessage());
    }
    // GUARDAR CANCIÓN EN SUGERENCIAS
    try {
        $stmt = $pdo->prepare("INSERT INTO sugerencias_canciones (nombre) VALUES (?) 
                              ON DUPLICATE KEY UPDATE conteo = conteo + 1");
        $stmt->execute([$cancion]);
    } catch (PDOException $e) {
        error_log('Error sugerencias_canciones: ' . $e->getMessage());
    }
    // 🔹 3. Insertar nueva solicitud (Pendiente)
    $fecha = date('Y-m-d');
    $hora  = date('H:i:s');
    $stmt = $pdo->prepare("
    INSERT INTO solicitudes (nombre, cancion, artista, genero, cookie_id, estado, fecha, hora, bloqueada)
    VALUES (?, ?, ?, ?, ?, 'Pendiente', ?, ?, 1)
");
$stmt->execute([$nombre, $cancion, $artista, $genero, $cookie_id, $fecha, $hora]);
    echo json_encode([
        'status' => 'success',
        'message' => 'Tu solicitud fue enviada con exito'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}


