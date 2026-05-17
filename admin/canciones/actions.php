<?php
require_once __DIR__ . '/../../inc/config.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    echo json_encode(['status'=>'error','message'=>'Error BD: '.$e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'listar':
        $stmt = $pdo->query("SELECT * FROM solicitudes ORDER BY id DESC");
        echo json_encode(['data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        break;

    case 'estado':
        $id = (int)$_POST['id'];
        $estado = $_POST['estado'] ?? '';
        if (!in_array($estado, ['Pendiente','Programada','Sonada','Rechazada'])) {
            echo json_encode(['status'=>'error','message'=>'Estado inválido']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE solicitudes SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);
        //echo json_encode(['status'=>'success','message'=>"Solicitud marcada como $estado"]);
        break;

    case 'eliminar':
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM solicitudes WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['status'=>'success','message'=>'Solicitud eliminada']);
        break;

    case 'comentar':
        $id = (int)$_POST['id'];
        $comentario = trim($_POST['comentario'] ?? '');
        $stmt = $pdo->prepare("UPDATE solicitudes SET comentario = ? WHERE id = ?");
        $stmt->execute([$comentario, $id]);
        echo json_encode(['status'=>'success','message'=>'Comentario guardado']);
        break;
		
	case 'bloqueo':
		$id = intval($_POST['id'] ?? 0);
		$bloqueada = intval($_POST['bloqueada'] ?? 0);

		$stmt = $pdo->prepare("UPDATE solicitudes SET bloqueada = ? WHERE id = ?");
		$stmt->execute([$bloqueada, $id]);

		$msg = $bloqueada == 1 
			? 'Solicitud bloqueada correctamente.' 
			: 'Solicitud desbloqueada correctamente.';

		echo json_encode(['status' => 'success', 'message' => $msg]);
		break;



    default:
        echo json_encode(['status'=>'error','message'=>'Acción no válida']);
}


