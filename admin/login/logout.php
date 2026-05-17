<?php require_once('../../inc/config.php'); // Este archivo debería definir $host, $dbname, $dbuser, $dbpass, etc.

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    // En caso de error, detén la ejecución o maneja el error según corresponda
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

session_start();

// Si existe la cookie "remember_me", elimina el token de la base de datos y la cookie
if (isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $stmt = $pdo->prepare("DELETE FROM user_tokens WHERE token = :token");
    $stmt->execute([':token' => $token]);
    setcookie('remember_me', '', time() - 3600, '/', 'app.activgym.com.co', true, true);
}

// Destruir la sesión
session_unset();
session_destroy();

// Iniciar una nueva sesión para el mensaje flash
session_start();
$_SESSION['success'] = 'Has cerrado sesión correctamente.';
header("Location: $url/admin/login/");
exit();
?>

