<?php
// O puedes mostrar solo errores graves, ocultando warnings:
error_reporting(E_ERROR | E_PARSE);
?>

<?php
// session.php
require_once __DIR__ . '/../../inc/config.php';
$cookieDomain = str_replace(['https://','http://'], '', $url);
// Configurar los parámetros de la cookie de sesión
$tiempoUnAno = 365 * 24 * 60 * 60;
session_set_cookie_params([
    'lifetime' => $tiempoUnAno,
    'path'     => '/',
    'domain'   => $cookieDomain,
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax'
]);

ini_set('session.gc_maxlifetime', $tiempoUnAno);
session_start();

// Crear la conexión a la base de datos y asignarla a $pdo
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Aquí se usa $pdo, por ejemplo, para revisar la cookie "remember_me"
if (!isset($_SESSION["user"]) && isset($_COOKIE['remember_me'])) {
    $token = $_COOKIE['remember_me'];
    $now = time();

    $stmt = $pdo->prepare("SELECT user_id FROM user_tokens WHERE token = :token AND expires_at > :now LIMIT 1");
    $stmt->execute([
        ':token' => $token,
        ':now'   => $now
    ]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        $stmtUser = $pdo->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
        $stmtUser->execute([':id' => $result['user_id']]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION["user"] = $user;
            // Opcional: regenerar el token para mayor seguridad
            $newToken = bin2hex(random_bytes(16));
            $newExpiry = $now + (30 * 24 * 60 * 60);
            $updateStmt = $pdo->prepare("UPDATE user_tokens SET token = :newToken, expires_at = :newExpiry WHERE token = :oldToken");
            $updateStmt->execute([
                ':newToken'  => $newToken,
                ':newExpiry' => $newExpiry,
                ':oldToken'  => $token
            ]);
            setcookie('remember_me', $newToken, $newExpiry, '/', $cookieDomain, true, true);
        }
    } else {
        setcookie('remember_me', '', time() - 3600, '/', $cookieDomain, true, true);
    }
}

// Verificar que el usuario esté logueado
if (!isset($_SESSION["user"])) {
    header("Location: $url/admin/login");
    exit();
}

// Obtener datos del usuario para usarlos en la aplicación
$id_user   = $_SESSION["user"]["id"];
$nombre    = $_SESSION["user"]["nombre"];
$apellido  = $_SESSION["user"]["apellido"];
$rol_id    = $_SESSION["user"]["rol_id"]; // Obtener el ID del rol

// Consultar el nombre del rol
try {
    $stmtRol = $pdo->prepare("SELECT name FROM roles WHERE id = :rol_id LIMIT 1");
    $stmtRol->execute([':rol_id' => $rol_id]);
    $rolData = $stmtRol->fetch(PDO::FETCH_ASSOC);
    $rolUser = $rolData ? $rolData['name'] : 'Sin Rol'; // Almacenar el nombre del rol
} catch (PDOException $e) {
    $rolUser = 'Sin Rol'; // En caso de error, asignar un valor predeterminado
}


// Consultar los permisos asociados al rol del usuario
try {
    $stmtPermisos = $pdo->prepare("SELECT p.name AS permission_name 
                                   FROM role_permissions rp 
                                   JOIN permissions p ON rp.permission_id = p.id 
                                   WHERE rp.role_id = :rol_id");
    $stmtPermisos->execute([':rol_id' => $rol_id]);
    $permisos = $stmtPermisos->fetchAll(PDO::FETCH_COLUMN); // Devuelve un array con los nombres de los permisos
} catch (PDOException $e) {
    $permisos = []; // En caso de error, dejar la lista de permisos vacía
}

// Guardar los permisos en una variable accesible globalmente
$_SESSION["user_permissions"] = $permisos;

// Obtener la caja abierta para el usuario actual
/*$stmtCaja = $pdo->prepare("SELECT id FROM cajas WHERE usuario_id = :usuario_id AND estado = 1 LIMIT 1");
$stmtCaja->execute([':usuario_id' => $id_user]);
$caja = $stmtCaja->fetch(PDO::FETCH_ASSOC);
$caja_id = $caja ? $caja['id'] : null;*/

// Imprimir los permisos para depuración
//echo "<pre>";
//print_r($_SESSION["user_permissions"]);
//echo "</pre>";
?>





