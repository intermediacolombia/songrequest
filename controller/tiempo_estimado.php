<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../inc/config.php';

if (!isset($_COOKIE['solicitud_id'])) {
    echo json_encode(['error' => 'No se pudo identificar tu dispositivo']);
    exit;
}

$cookie_id = $_COOKIE['solicitud_id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 1. Contar canciones en cola (SOLO Programadas, las pendientes pueden ser rechazadas)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM solicitudes 
        WHERE estado = 'Programada'
    ");
    $stmt->execute();
    $enCola = (int)$stmt->fetchColumn();

    // 2. Obtener canciones del usuario que están programadas
    $stmt = $pdo->prepare("
        SELECT id, cancion, artista, estado, 
               (SELECT COUNT(*) 
                FROM solicitudes s2 
                WHERE s2.estado = 'Programada'
                AND s2.id < solicitudes.id) + 1 AS posicion
        FROM solicitudes 
        WHERE cookie_id = ? 
        AND estado = 'Programada'
        ORDER BY id ASC
    ");
    $stmt->execute([$cookie_id]);
    $misCanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Calcular tiempo estimado para cada canción
    $duracionPromedio = 4.5; // minutos
    $canciones = [];

    foreach ($misCanciones as $cancion) {
        $posicion = (int)$cancion['posicion'];
        $minutosEstimados = $posicion * $duracionPromedio;
        
        // Convertir a horas y minutos
        $horas = floor($minutosEstimados / 60);
        $minutos = round($minutosEstimados % 60);
        
        $canciones[] = [
            'id' => $cancion['id'],
            'cancion' => $cancion['cancion'],
            'artista' => $cancion['artista'],
            'estado' => $cancion['estado'],
            'posicion' => $posicion,
            'tiempo_estimado' => [
                'total_minutos' => round($minutosEstimados),
                'horas' => $horas,
                'minutos' => $minutos,
                'texto' => $horas > 0 
                    ? "{$horas}h {$minutos}min" 
                    : "{$minutos} min"
            ]
        ];
    }

    echo json_encode([
        'success' => true,
        'total_en_cola' => $enCola,
        'mis_canciones' => $canciones,
        'duracion_promedio' => $duracionPromedio
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
