<?php
// includes/tabla_solicitudes.php
require_once __DIR__ . '/../inc/config.php';

if (!isset($_COOKIE['solicitud_id'])) {
    echo '<p class="text-center text-muted">No se pudo identificar tu dispositivo.</p>';
    exit;
}

$cookie_id = $_COOKIE['solicitud_id'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Obtener solicitudes del usuario con su posición en cola (SOLO PROGRAMADAS)
    $stmt = $pdo->prepare("
        SELECT 
            s.*,
            (SELECT COUNT(*) 
             FROM solicitudes s2 
             WHERE s2.estado = 'Programada'
             AND s2.id < s.id) + 1 AS posicion_cola
        FROM solicitudes s
        WHERE s.cookie_id = ? 
        ORDER BY s.id DESC
    ");
    $stmt->execute([$cookie_id]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $duracionPromedio = 4.5; // minutos por canción

    if (empty($solicitudes)) {
        echo '<p class="text-center text-muted py-4">No tienes solicitudes aún.</p>';
    } else {
        echo '<table class="table table-borderless mb-0">
                <thead>
                  <tr>
                    <th>Canción</th>
                    <th>Artista</th>
                    <th>Estado</th>
                    <th class="fecha">Fecha</th>
                  </tr>
                </thead>
                <tbody>';

        foreach ($solicitudes as $sol) {
            $estado = $sol['estado'];
            $badgeClass = match($estado) {
                'Pendiente' => 'bg-warning text-dark',
                'Programada' => 'bg-primary',
                'Sonada' => 'bg-success',
                'Rechazada' => 'bg-danger',
                default => 'bg-secondary'
            };

            // Calcular tiempo estimado SOLO para Programadas (las pendientes pueden ser rechazadas)
            $tiempoEstimadoHTML = '';
            if ($estado === 'Programada') {
                $posicion = (int)$sol['posicion_cola'];
                $minutosEstimados = $posicion * $duracionPromedio;
                $horas = floor($minutosEstimados / 60);
                $minutos = round($minutosEstimados % 60);
                
                $textoTiempo = $horas > 0 
                    ? "{$horas}h {$minutos}min" 
                    : "{$minutos}min";
                
                $tiempoEstimadoHTML = '<span class="tiempo-estimado-badge" title="Tiempo estimado de espera">
                    <i class="fas fa-clock me-1"></i>Sonará aproximadamente en ' . $textoTiempo . '
                </span>';
            }

            $comentario = htmlspecialchars($sol['comentario'] ?? '', ENT_QUOTES, 'UTF-8');
            $claseComentable = $comentario ? 'comentable' : '';
            $cursorStyle = $comentario ? 'cursor:pointer;' : '';

            echo '<tr class="' . $claseComentable . '" data-comentario="' . $comentario . '" style="' . $cursorStyle . '">
                    <td data-label="Canción">' . htmlspecialchars($sol['cancion']) . '</td>
                    <td data-label="Artista">' . htmlspecialchars($sol['artista']) . '</td>
                    <td data-label="Estado">
                        <span class="badge ' . $badgeClass . '">' . $estado . '</span>
                        ' . $tiempoEstimadoHTML . '
                        ' . ($comentario ? '<i class="fas fa-comment-dots text-warning ms-1" title="Tiene comentario"></i>' : '') . '
                    </td>
                    <td data-label="Fecha" class="fecha">' . htmlspecialchars($sol['fecha']) . '</td>
                  </tr>';
        }

        echo '</tbody></table>';
    }

} catch (PDOException $e) {
    echo '<p class="text-center text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
