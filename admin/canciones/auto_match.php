<?php
require_once __DIR__ . '/../../inc/config.php'; 
header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['nombre'])) {
    echo json_encode([
        "status" => "error",
        "msg"    => "Nombre de canción no recibido"
    ]);
    exit;
}

$nombreReproducida = trim($_POST['nombre']);

/* ============================================================
   FUNCIÓN PARA PORCENTAJE DE SIMILITUD
   ============================================================ */
function matchPercent($a, $b) {
    similar_text(
        mb_strtolower($a),
        mb_strtolower($b),
        $p
    );
    return $p;
}

try {

    /* ============================================================
       1. OBTENER SOLICITUDES PENDIENTES
       ============================================================ */
    $stmt = $pdo->query("
        SELECT id, artista, cancion 
        FROM solicitudes 
        WHERE estado = 'Programada'
    ");

    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $umbral = 70;      // porcentaje mínimo para considerarse coincidencia
    $marcadas = 0;     // contador de coincidencias marcadas


    /* ============================================================
       2. COMPARAR CADA SOLICITUD CON LA CANCIÓN REPRODUCIDA
       ============================================================ */
    foreach ($solicitudes as $s) {

        $nombreSolicitud = $s['artista'] . " - " . $s['cancion'];
        $pct = matchPercent($nombreReproducida, $nombreSolicitud);

        if ($pct >= $umbral) {

            // marcar como Sonada
            $upd = $pdo->prepare("
                UPDATE solicitudes 
                SET estado = 'Sonada' 
                WHERE id = ?
            ");
            $upd->execute([$s['id']]);

            $marcadas++;
        }
    }


    /* ============================================================
       3. RESPUESTA
       ============================================================ */
    echo json_encode([
        "status"     => "ok",
        "reproducida"=> $nombreReproducida,
        "marcadas"   => $marcadas
    ]);

} catch (Exception $e) {

    echo json_encode([
        "status" => "error",
        "msg"    => "Error en auto_match: " . $e->getMessage()
    ]);
}
