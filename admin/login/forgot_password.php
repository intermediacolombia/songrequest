<?php 

require_once __DIR__ . '/../../inc/config.php';

// Conexión a la base de datos mediante PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$messageSent = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recoge y sanitiza el correo electrónico ingresado (campo "correo")
    $correo = filter_var($_POST['correo'], FILTER_SANITIZE_EMAIL);

    // Busca el usuario por correo
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE correo = :correo AND estado = 0 AND borrado = 0 LIMIT 1");
    $stmt->execute([':correo' => $correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Genera un token único (32 caracteres hexadecimales)
        $token = bin2hex(random_bytes(16));
        // Define el tiempo de expiración del token (1 hora)
        $expires = time() + 3600;

        // Guarda el token en la tabla password_resets
        $stmtReset = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)
                                    ON DUPLICATE KEY UPDATE token = :token, expires_at = :expires_at");
        $stmtReset->execute([
            ':user_id'    => $user['id'],
            ':token'      => $token,
            ':expires_at' => $expires
        ]);

        // Construye el enlace de restablecimiento
        $resetLink = "$url/admin/login/reset_password.php?token=$token";

        // Incluir el archivo que envía el correo y llamar a la función
        require_once('../../mailer/password-resets.php');
        // Combina nombre y apellido para mostrar el nombre completo
        $nombreCompleto = $user['nombre'] . ' ' . $user['apellido'];
        sendResetPasswordEmail($correo, $nombreCompleto, $resetLink, $url, $logo);
    }
    // Siempre se muestra el mismo mensaje para no revelar si el correo existe o no
    $messageSent = true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Recuperar Contraseña</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<style>
    body {
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url("<?php echo $url;?>/admin/images/background.jpg") no-repeat center center fixed;
  background-size: cover;
}

    .login-card {
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      border-radius: 10px;
      padding: 20px;
      background-color: #fff;
    }
  </style>
</head>
<body>
 <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="login-card w-100" style="max-width: 400px;">
      <div class="text-center">
        <img src="<?php echo URLBASE;?><?php echo SITE_LOGO;?>" alt="Logo" class="img-fluid mb-3" style="max-width:150px;">
      </div>
		
		
   <h3>Recuperar Contraseña</h3>
    <?php if ($messageSent): ?>
      <div class="alert alert-success">
        Si tu correo electrónico <?php echo $correo;?> se encuentra registrado en el sistema, recibirás un mensaje con las instrucciones para restablecer tu contraseña.<br><br>

		  <a href="<?php echo $url;?>/admin/login" style="text-decoration:none; color:#0a3622;"><i class="fas fa-arrow-left"></i> Volver al Inicio de Sesión</a>
      </div>
    <?php endif; ?>
    <form method="post" action="">
      <div class="form-group">
        <label for="correo">Ingresa tu correo electrónico</label>
        <input type="email" class="form-control" id="correo" name="correo" required>
      </div>
      <button type="submit" class="btn btn-primary">Enviar Instrucciones</button>
    </form>
  
		
	 </div>
	</div>
</body>
</html>




