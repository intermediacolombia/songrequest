<?php
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Error BD: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';
$ids = $_POST['ids'] ?? [];
if (!is_array($ids)) {
    $ids = explode(',', $ids); // convierte "1,2,3" en [1,2,3]
}
$ids = array_filter(array_map('intval', $ids));

$estado = $_POST['estado'] ?? '';

// Asegurar que los IDs sean array y válidos
if (!is_array($ids)) {
    // Si vino como string "1,2,3"
    $ids = explode(',', $ids);
}
$ids = array_filter(array_map('intval', $ids)); // elimina vacíos y no numéricos

if (empty($ids)) {
    echo json_encode(['status' => 'error', 'message' => 'No se recibieron IDs válidos.']);
    exit;
}

switch ($action) {

    // ==========================================================
    // 🔹 CAMBIAR ESTADO MASIVO
    // ==========================================================
    case 'estado':
        if (!in_array($estado, ['Pendiente', 'Programada', 'Sonada', 'Rechazada'])) {
            echo json_encode(['status' => 'error', 'message' => 'Estado inválido.']);
            exit;
        }

        $in = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("UPDATE solicitudes SET estado = ? WHERE id IN ($in)");
        $ok = $stmt->execute(array_merge([$estado], $ids));

        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok
                ? "Se actualizaron {$stmt->rowCount()} solicitud(es) a estado $estado."
                : "No se pudo actualizar el estado de las solicitudes."
        ]);
        break;
		
	case 'bloqueo':
			$ids = $_POST['ids'] ?? '';
			$tipo = $_POST['tipo'] ?? '';
			if (empty($ids)) {
				echo json_encode(['status' => 'error', 'message' => 'No se enviaron IDs.']);
				exit;
			}

			$idList = explode(',', $ids);
			$marca = $tipo === 'Bloquear' ? 1 : 0; // 1 = bloqueada, 0 = desbloqueada

			$in  = str_repeat('?,', count($idList) - 1) . '?';
			$stmt = $pdo->prepare("UPDATE solicitudes SET bloqueada = ? WHERE id IN ($in)");
			$stmt->execute(array_merge([$marca], $idList));

			$msg = $marca ? 'Solicitudes bloqueadas correctamente.' : 'Solicitudes desbloqueadas correctamente.';
			echo json_encode(['status' => 'success', 'message' => $msg]);
			break;


    // ==========================================================
    // 🔹 ELIMINAR MASIVO
    // ==========================================================
    case 'eliminar':
        $in = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM solicitudes WHERE id IN ($in)");
        $ok = $stmt->execute($ids);

        echo json_encode([
            'status' => $ok ? 'success' : 'error',
            'message' => $ok
                ? "Se eliminaron {$stmt->rowCount()} solicitud(es) correctamente."
                : "No se pudieron eliminar las solicitudes."
        ]);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción no válida.']);
        break;
}
