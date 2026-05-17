<?php require_once('../../inc/config.php');

// Conectar a la base de datos mediante PDO
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Recuperar el token desde la URL
$token = $_GET['token'] ?? '';
$message = '';
$error = '';

// Verificar que se haya recibido un token
if (!$token) {
    die("Token inválido.");
}

// Buscar el token en la base de datos y verificar que no haya expirado
$stmt = $pdo->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = :token LIMIT 1");
$stmt->execute([':token' => $token]);
$resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resetRequest) {
    die("Token no válido.");
}

if ($resetRequest['expires_at'] < time()) {
    die("El token ha expirado.");
}

// Procesar el formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
        $error = "Por favor, ingresa la nueva contraseña y confírmala.";
    } elseif ($password !== $confirmPassword) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
        $stmt->execute([
            ':password' => $passwordHash,
            ':id'       => $resetRequest['user_id']
        ]);

        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = :token");
        $stmt->execute([':token' => $token]);

        $message = "Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión con tu nueva contraseña.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Restablecer Contraseña</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
      background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url("<?php echo $url;?>/admin/images/background.jpg") no-repeat center center fixed;
      background-size: cover;
    }
    .login-card {
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      border-radius: 10px;
      padding: 20px;
      background-color: #fff;
    }
    .input-group-text {
      cursor: pointer;
    }
    #passwordError {
      color: red;
      font-size: 14px;
      display: none;
    }
  </style>
</head>
<body>
 <div class="container d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="login-card w-100" style="max-width: 400px;">
      <div class="text-center">
        <img src="<?php echo URLBASE; ?><?php echo SITE_LOGO; ?>?<?php echo time()?>" alt="Logo" class="img-fluid mb-3" style="max-width:150px;">
      </div>
      <h3>Restablecer Contraseña</h3>
      <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
        <a href="<?php echo $url; ?>/admin/login/" class="btn btn-primary">Iniciar Sesión</a>
      <?php else: ?>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="">
          <div class="form-group">
            <label for="password">Nueva Contraseña</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password" name="password" required>
              <div class="input-group-append">
                <span class="input-group-text toggle-password" data-target="#password">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="confirm_password">Confirmar Nueva Contraseña</label>
            <div class="input-group">
              <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
              <div class="input-group-append">
                <span class="input-group-text toggle-password" data-target="#confirm_password">
                  <i class="fas fa-eye"></i>
                </span>
              </div>
            </div>
          </div>
          <!-- Mensaje de error solo cuando se escribe en el campo de confirmación -->
          <div id="passwordError">Las contraseñas no coinciden.</div>
          <button type="submit" id="resetButton" class="btn btn-primary" disabled>Restablecer Contraseña</button>
        </form>
      <?php endif; ?>
    </div>
 </div>
 <script>
    // Función para alternar la visibilidad utilizando los "ojitos"
    document.querySelectorAll('.toggle-password').forEach(function(span) {
      span.addEventListener('click', function() {
        var targetSelector = this.getAttribute('data-target');
        var input = document.querySelector(targetSelector);
        var icon = this.querySelector('i');
        if (input.getAttribute('type') === 'password') {
          input.setAttribute('type', 'text');
          icon.classList.remove('fa-eye');
          icon.classList.add('fa-eye-slash');
        } else {
          input.setAttribute('type', 'password');
          icon.classList.remove('fa-eye-slash');
          icon.classList.add('fa-eye');
        }
      });
    });

    // Validación en tiempo real para que ambas contraseñas coincidan,
    // pero solo muestra el mensaje de error cuando se empieza a escribir en el campo de Confirmar Nueva Contraseña.
    var passwordInput = document.getElementById('password');
    var confirmPasswordInput = document.getElementById('confirm_password');
    var resetButton = document.getElementById('resetButton');
    var passwordError = document.getElementById('passwordError');

    function validatePasswords() {
      // Si el campo de confirmación está vacío, no mostramos el error y el botón permanece deshabilitado
      if (confirmPasswordInput.value === '') {
        resetButton.disabled = true;
        passwordError.style.display = 'none';
      } else {
        if (passwordInput.value === confirmPasswordInput.value) {
          resetButton.disabled = false;
          passwordError.style.display = 'none';
        } else {
          resetButton.disabled = true;
          passwordError.style.display = 'block';
        }
      }
    }

    // Escuchar solo en el campo de confirmación para mostrar el mensaje
    confirmPasswordInput.addEventListener('input', validatePasswords);
    // También es útil validar cuando se escribe en el otro campo
    passwordInput.addEventListener('input', validatePasswords);
 </script>
</body>
</html>


