<?php

require_once __DIR__ . '/../../inc/config.php';
$cookieDomain = str_replace(['https://','http://'], '', $url);

// Si el usuario ya está logueado, opcionalmente se puede redirigir a dashboard
if(isset($_SESSION['user'])) {
header("Location: /admin");
exit();
}

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Credenciales de la base de datos
   //include('../../inc/config.php');
// Conexión a la base de datos mediante PDO
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error de conexión: " . $e->getMessage();
        header("Location: $url/admin/login/");
        exit();
    }

    // Recuperar y sanitizar los datos del formulario
    $username = strtolower(trim($_POST["username"] ?? ""));
    $password = $_POST["password"] ?? "";

    // Buscar el usuario por nombre de usuario (único)
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = :username LIMIT 1");
    $stmt->execute(["username" => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si no se encontró el usuario o está marcado como borrado, mostramos mensaje genérico
    if (!$user || $user["borrado"] == 1) {
        $_SESSION["error"] = "Usuario o contraseña incorrectos.";
        header("Location: $url/admin/login/");
        exit();
    }

    // Si el usuario está inactivo, se muestra el mensaje específico
    if ($user["estado"] == 1) {
        $_SESSION["error"] = "Usuario inactivo, comuníquese con el administrador.";
        header("Location: $url/admin/login/");
        exit();
    }

    // Verificar la contraseña
    if (!password_verify($password, $user["password"])) {
        // La contraseña es incorrecta, incrementar el contador de intentos
        $intentos = $user["intentos"] + 1;
        if ($intentos >= 5) {
            // Se alcanzó el máximo de intentos, se marca al usuario como inactivo
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos = :intentos, estado = 1 WHERE id = :id");
            $stmt->execute(["intentos" => $intentos, "id" => $user["id"]]);
            $_SESSION["error"] = "Usuario inactivo, comuníquese con el administrador.";
        } else {
            // Actualizar el número de intentos y mostrar los intentos restantes
            $stmt = $pdo->prepare("UPDATE usuarios SET intentos = :intentos WHERE id = :id");
            $stmt->execute(["intentos" => $intentos, "id" => $user["id"]]);
            $restantes = 5 - $intentos;
            $_SESSION["error"] = "Nombre de usuario o contraseña incorrectos. Tienes $restantes intento(s) más.";
        }
        header("Location: $url/admin/login/");
        exit();
    }

    // La contraseña es correcta: reiniciamos el contador de intentos
$stmt = $pdo->prepare("UPDATE usuarios SET intentos = 0 WHERE id = :id");
$stmt->execute(["id" => $user["id"]]);

// Iniciar sesión: almacenar los datos del usuario en sesión
$_SESSION["user"] = $user;

// IMPLEMENTAR REMEMBER ME: 
// Si el usuario marcó la opción "remember me" en el formulario, generamos el token
if (isset($_POST['remember_me']) && $_POST['remember_me'] == 1) {
    // Genera un token seguro (32 caracteres hexadecimales)
    $token = bin2hex(random_bytes(16));
    // Define el tiempo de expiración (por ejemplo, 30 días)
    $expiry = time() + (30 * 24 * 60 * 60);

    // Inserta el token en la base de datos (asegúrate de tener la tabla user_tokens)
    $stmtToken = $pdo->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
    $stmtToken->execute([
         ':user_id'    => $user["id"],
         ':token'      => $token,
         ':expires_at' => $expiry
    ]);

    // Guarda el token en una cookie persistente
    // Asegúrate de que el dominio y demás parámetros coincidan con tu configuración
    setcookie('remember_me', $token, $expiry, '/', $cookieDomain, true, true);
}

header("Location: $url/admin/");
exit();
}
?>


<!DOCTYPE html>

<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login</title>
 
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Font Awesome para íconos -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    body {
  background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url("<?php echo $url;?>/attachments/background/background.jpg") no-repeat center center fixed;
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
        <img src="<?php echo URLBASE; ?><?php echo SITE_LOGO; ?>?<?php echo time()?>" alt="Logo" class="img-fluid mb-3" style="max-width:150px;">
      </div>
      <h3 class="text-center mb-4">Iniciar Sesión</h3>
      
      <!-- Alertas de Bootstrap para mensajes de sesión -->
      <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>
      <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      <?php endif; ?>

      <form method="post" action="" class="needs-validation" novalidate>
        <!-- Campo de nombre de usuario con ícono -->
        <div class="form-group">
          <label for="username">Nombre de Usuario</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fa fa-user"></i></span>
            </div>
            <input type="text" class="form-control" id="username" name="username" required oninput="this.value = this.value.toLowerCase()">
          </div>
          <div class="invalid-feedback">Ingrese su nombre de usuario.</div>
        </div>
        <!-- Campo de contraseña con ícono y botón para alternar visibilidad -->
        <div class="form-group">
          <label for="password">Contraseña</label>
          <div class="input-group">
            <div class="input-group-prepend">
              <span class="input-group-text"><i class="fa fa-lock"></i></span>
            </div>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="input-group-append">
              <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#password">
                <i class="fa fa-eye"></i>
              </button>
            </div>
          </div>
          <div class="invalid-feedback">Ingrese su contraseña.</div>
        </div>
		  
		  <div class="form-group form-check">
  <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" value="1">
  <label class="form-check-label" for="remember_me">Mantener la sesión iniciada</label>
</div>
		  <!-- En tu página de login, justo debajo del formulario -->
<p><a href="forgot_password.php">¿Olvidaste tu contraseña?</a></p>


		  
		  
		  
        <button type="submit" class="btn btn-primary btn-block">Iniciar Sesión</button>
      </form>
    </div>
  </div>
  
  <!-- jQuery y Bootstrap JS -->
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Alternar visibilidad de la contraseña
    document.querySelectorAll('.toggle-password').forEach(function(button) {
      button.addEventListener('click', function() {
         var targetSelector = this.getAttribute('data-target');
         var targetInput = document.querySelector(targetSelector);
         if (targetInput.getAttribute('type') === 'password') {
            targetInput.setAttribute('type', 'text');
            this.innerHTML = '<i class="fa fa-eye-slash"></i>';
         } else {
            targetInput.setAttribute('type', 'password');
            this.innerHTML = '<i class="fa fa-eye"></i>';
         }
      });
    });

    // Validación de formulario con Bootstrap (opcional)
    (function () {
      'use strict';
      window.addEventListener('load', function () {
         var forms = document.getElementsByClassName('needs-validation');
         Array.prototype.filter.call(forms, function (form) {
            form.addEventListener('submit', function (event) {
               if (form.checkValidity() === false) {
                  event.preventDefault();
                  event.stopPropagation();
               }
               form.classList.add('was-validated');
            }, false);
         });
      }, false);
    })();
  </script>
</body>
</html>


