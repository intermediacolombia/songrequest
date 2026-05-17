<?php
require_once __DIR__ . '/inc/config.php';

// Generar cookie si no existe
if (!isset($_COOKIE['solicitud_id'])) {
    $cookie_id = bin2hex(random_bytes(16));
    setcookie('solicitud_id', $cookie_id, [
    'expires' => time() + (86400 * 30),
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax'
]);

    // recargar automáticamente para que la cookie exista
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
} else {
    $cookie_id = $_COOKIE['solicitud_id'];
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Obtener solicitudes del usuario
    $stmt = $pdo->prepare("SELECT * FROM solicitudes WHERE cookie_id = ? ORDER BY id DESC");
    $stmt->execute([$cookie_id]);
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular totales
    $totalSolicitudes = count($solicitudes);
    $bloqueadas = 0;
    $tieneDesbloqueadas = false;

    foreach ($solicitudes as $sol) {
        if ((int)$sol['bloqueada'] === 1) {
            $bloqueadas++;
        } else {
            $tieneDesbloqueadas = true;
        }
    }

    // Lógica principal
    $puedeEnviar = ($bloqueadas < $numeroLimite) || $tieneDesbloqueadas;
    $restantes = max(0, $numeroLimite - $bloqueadas);

    // Verificar si el formulario público está activo
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name = 'formulario_publico'");
    $stmt->execute();
    $formularioActivo = $stmt->fetchColumn() === 'true';

    // Consultar si hay una hora de reactivación
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name = 'formulario_reactivacion'");
    $stmt->execute();
    $horaReactivacion = $stmt->fetchColumn();

    // Consultar si la desactivación permanente está activa
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name = 'formulario_permanente'");
    $stmt->execute();
    $formularioPermanente = $stmt->fetchColumn() === 'true';

} catch (PDOException $e) {
    die("Error BD: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Solicita tu Canción</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* ======== FONDO GENERAL ======== */
body {
  background: radial-gradient(circle at top, #1a1a1a, #000);
  font-family: "Poppins", sans-serif;
  color: #f5f5f5;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
}

.form-container, 
.table-container {
  width: 100%;
  max-width: 600px;
  margin: 0 auto 20px auto;
}

.table-container {
  background: rgba(255,255,255,0.05);
  border-radius: 15px;
  border: 1px solid rgba(255,215,0,0.2);
  box-shadow: 0 0 10px rgba(0,0,0,0.3);
}

/* ======== CONTENEDOR ======== */
.form-container {
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(15px);
  border-radius: 18px;
  border: 1px solid rgba(255, 215, 0, 0.25);
  box-shadow: 0 6px 25px rgba(0, 0, 0, 0.6);
  padding: 35px;
  width: 100%;
  max-width: 500px;
  animation: fadeInUp 0.7s ease;
}

@keyframes fadeInUp {
  from {opacity: 0; transform: translateY(20px);}
  to {opacity: 1; transform: translateY(0);}
}

/* ======== TÍTULO ======== */
.form-container h2 {
  text-align: center;
  font-weight: 700;
  font-size: 1.8rem;
  color: #f8c200;
  margin-bottom: 25px;
  text-shadow: 0 0 10px rgba(255, 215, 0, 0.4);
}
.form-container h2 i {
  color: #c92020;
  margin-right: 6px;
}

/* ======== INPUT CON ÍCONO ======== */
.input-group-custom {
  position: relative;
  margin-bottom: 1.2rem;
}
.input-group-custom i {
  position: absolute;
  top: 50%;
  left: 14px;
  transform: translateY(-50%);
  color: #f8c200;
  font-size: 1rem;
  z-index: 2;
}
.input-group-custom input,
.input-group-custom select {
  width: 100%;
  background: #151515;
  border: 1px solid rgba(255,255,255,0.15);
  color: #f1f1f1;
  border-radius: 10px;
  height: 55px;
  padding: 0 15px 0 40px;
  transition: all 0.3s ease;
}
.input-group-custom input:focus,
.input-group-custom select:focus {
  background: #1c1c1c;
  border-color: #f8c200;
  box-shadow: 0 0 8px rgba(255, 215, 0, 0.3);
}
.input-group-custom input::placeholder,
.input-group-custom select option {
  color: rgba(255,255,255,0.6);
}

/* ======== BOTÓN ======== */
.btn-modern {
  background: linear-gradient(90deg, #f8c200, #b88a00);
  border: none;
  border-radius: 10px;
  color: #000;
  font-weight: 700;
  font-size: 1rem;
  width: 100%;
  padding: 12px;
  transition: all 0.3s ease;
  text-transform: uppercase;
}
.btn-modern:hover {
  background: linear-gradient(90deg, #ffdf3d, #cfa400);
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(255,215,0,0.3);
}

/* ======== TEXTO LÍMITE ======== */
#contadorSolicitudes {
  text-align: center;
  margin-top: 15px;
  color: #ccc;
  font-size: 0.9rem;
}

/* ======== TABLA ======== */
.table-container {
  background: rgba(255,255,255,0.05);
  border-radius: 15px;
  overflow-x: auto;
  margin-top: 25px;
  border: 1px solid rgba(255,215,0,0.2);
}

.table {
  width: 100%;
  color: #f8f8f8;
  font-size: 0.9rem;
  margin-bottom: 0;
  border-collapse: collapse;
}
.table thead {
  background: linear-gradient(90deg, #c92020, #8b0000);
  color: #fff;
}
.table th, .table td {
  vertical-align: middle;
  padding: 10px;
  white-space: nowrap;
  background: #191919;
  color: #fff;
}
.table tbody tr:hover {
  background: rgba(255, 215, 0, 0.1);
}
.badge {
  font-size: 0.8rem;
  border-radius: 8px;
  padding: 6px 10px;
}

/* ======== RESPONSIVE ======== */
@media (max-width: 600px) {
  .form-container { padding: 25px; }
  .table { font-size: 0.8rem; }
}

/* ======== TABLA RESPONSIVA EN MÓVILES ======== */
@media (max-width: 768px) {
  .table thead {
    display: none;
  }

  .table tbody, .table tr, .table td {
    display: block;
    width: 100%;
  }

  .table tr {
    background: rgba(0,0,0,0.65);
    margin-bottom: 8px;
    border-radius: 10px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.3);
    padding: 6px 10px;
    transition: all 0.2s ease-in-out;
  }

  .table tr:hover {
    background: rgba(255, 215, 0, 0.1);
    transform: scale(1.01);
  }

  .table td {
    text-align: left;
    padding: 5px 8px;
    border: none;
    position: relative;
    font-size: 0.85rem;
    line-height: 1.3;
    background: #191919;
    color: #fff;
  }

  .table td::before {
    content: attr(data-label);
    font-weight: 600;
    color: #f8c200;
    display: block;
    margin-bottom: 2px;
    font-size: 0.78rem;
  }

  .badge {
    padding: 4px 7px;
    font-size: 0.75rem;
  }

  .table td.fecha {
    display: none;
  }
  
  .tiempo-estimado-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
  }
}

/* ======== AUTOCOMPLETE ======== */
.autocomplete-box {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: #1a1a1a;
  border: 1px solid rgba(255,255,255,0.15);
  border-radius: 10px;
  z-index: 999;
  max-height: 200px;
  overflow-y: auto;
  display: none;
}

.autocomplete-item {
  padding: 10px;
  cursor: pointer;
  color: #eee;
}

.autocomplete-item:hover {
  background: rgba(255,215,0,0.1);
}

/* ======== TIEMPO ESTIMADO ======== */
.tiempo-estimado-badge {
  display: inline-block;
  padding: 4px 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: #fff;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-left: 8px;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
  animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.85; transform: scale(1.02); }
}
</style>
</head>

<body>
	
<?php
if ($formularioPermanente) {
?>
	
<div class="text-center py-4" style="color:#bbb; font-size:0.9rem;">
  <img src="/attachments/logo/logo.png" width="250px">
  <p class="mx-auto" style="max-width:700px; color:#ccc; line-height:1.5; text-align: center;">
    ¡Hola! 😊 Pide aquí tus canciones favoritas.  
    Pero antes, asegúrate de  
    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalTerminos" style="color:#f8c200;">
      leer esta información importante.
    </a>
  </p>
  
  <div class="mb-3">
    <a href="https://www.facebook.com/vallenateando.ando.221850" target="_blank" class="text-decoration-none mx-2" style="color:#f8c200;">
      <i class="fab fa-facebook-f"></i> Facebook
    </a>
    <a href="https://www.instagram.com/vallenateandoaxm/" target="_blank" class="text-decoration-none mx-2" style="color:#f8c200;">
      <i class="fab fa-instagram"></i> Instagram
    </a>
    <div class="text-center mt-2" style="font-size:0.9rem; color:#bbb;">
      <a href="https://www.instagram.com/dj_edme/" target="_blank" class="text-decoration-none" style="color:#bbb;">
        <i class="fab fa-instagram"></i> Sigue al DJ
      </a>
    </div>
  </div>
</div>
	
<div class="text-center mb-4">
  <h2 class="fw-bold text-danger mb-3">Tenemos nuestro listado lleno</h2>
  <p>Por favor, intenta nuevamente en unos minutos.</p>
</div>

<div id="tablaSolicitudes" class="table-container table-responsive mx-auto" style="max-width:700px;">
  <?php include __DIR__ . '/includes/tabla_solicitudes.php'; ?>
</div>
<?php
  exit;
}
?>

<div class="text-center py-4" style="color:#bbb; font-size:0.9rem;">
  <img src="/attachments/logo/logo.png" width="250px">
  <p class="mx-auto" style="max-width:700px; color:#ccc; line-height:1.5; text-align: center;">
    ¡Hola! 😊 Pide aquí tus canciones favoritas.  
    Pero antes, asegúrate de  
    <a href="#" class="text-decoration-none" data-bs-toggle="modal" data-bs-target="#modalTerminos" style="color:#f8c200;">
      leer esta información importante.
    </a>
  </p>
  
  <div class="mb-3">
    <a href="https://www.facebook.com/vallenateando.ando.221850" target="_blank" class="text-decoration-none mx-2" style="color:#f8c200;">
      <i class="fab fa-facebook-f"></i> Facebook
    </a>
    <a href="https://www.instagram.com/vallenateandoaxm/" target="_blank" class="text-decoration-none mx-2" style="color:#f8c200;">
      <i class="fab fa-instagram"></i> Instagram
    </a>
    <div class="text-center mt-2" style="font-size:0.9rem; color:#bbb;">
      <a href="https://www.instagram.com/dj_edme/" target="_blank" class="text-decoration-none" style="color:#bbb;">
        <i class="fab fa-instagram"></i> Sigue al DJ
      </a>
    </div>
  </div>
</div>

<!-- Mensaje de bloqueo -->
<div id="mensajeBloqueo" class="text-center mb-4" style="display:none;">
  <h2 class="fw-bold text-danger mb-3">Tenemos nuestro listado lleno</h2>
  <p>Por favor, intenta nuevamente en:</p>
  <h1 id="relojRestante" style="font-size:3rem; color:#ff3b3b; font-weight:bold;">--:--:--</h1>
</div>

<!-- Formulario -->
<div id="contenedorFormulario" class="form-container mx-auto mb-4" style="max-width:600px;">
  <h2><i class="fa-solid fa-music"></i> Solicita tu canción</h2>

  <form id="formSolicitud">
    <div class="input-group-custom">
      <i class="fa-solid fa-user"></i>
      <input type="text" name="nombre" placeholder="Tu nombre" required <?= !$puedeEnviar ? 'disabled' : '' ?>>
    </div>

    <div class="input-group-custom" style="position:relative;">
      <i class="fa-solid fa-microphone-lines"></i>
      <input type="text" name="artista" id="inputArtista" placeholder="Artista" required <?= !$puedeEnviar ? 'disabled' : '' ?>>
      <div id="autoArtista" class="autocomplete-box"></div>
    </div>

    <div class="input-group-custom" style="position:relative;">
      <i class="fa-solid fa-compact-disc"></i>
      <input type="text" name="cancion" id="inputCancion" placeholder="Nombre de la canción" required <?= !$puedeEnviar ? 'disabled' : '' ?>>
      <div id="autoCancion" class="autocomplete-box"></div>
    </div>

    <div class="input-group-custom">
      <i class="fa-solid fa-headphones"></i>
      <select name="genero" required <?= !$puedeEnviar ? 'disabled' : '' ?>>
        <option value="">Selecciona un género</option>
        <option value="Vallenato">Vallenato</option>
        <option value="Popular">Popular</option>
        <option value="Salsa">Salsa</option>
        <option value="Merengue">Merengue</option>
      </select>
    </div>

    <button id="btnEnviar" type="submit" class="btn-modern mt-3" <?= !$puedeEnviar ? 'disabled' : '' ?>>
      <?= !$puedeEnviar ? 'Límite alcanzado' : 'Enviar solicitud' ?>
    </button>
  </form>

  <p id="contadorSolicitudes" class="mt-2 mb-0 text-center">
    <?php if ($puedeEnviar): ?>
      Te quedan <strong><?= $restantes ?></strong>
      <?= $restantes == 1 ? 'solicitud' : 'solicitudes' ?> disponibles.
    <?php endif; ?>
  </p>
</div>

<!-- Tabla de canciones -->
<div id="tablaSolicitudes" class="table-container table-responsive mx-auto" style="max-width:700px;">
  <?php include __DIR__ . '/includes/tabla_solicitudes.php'; ?>
</div>

<!-- Modal Términos y Condiciones -->
<div class="modal fade" id="modalTerminos" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold">
          <i class="fa-solid fa-music me-2 text-danger"></i> Reglas de uso – Vallenateando
        </h5>
        <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
        <p>¡Hola! 👋</p>
        <p>
          Bienvenid@ a <strong>Vallenateando</strong>, <em>la casa del Vallenato en Armenia</em>🎶  
          Aquí el corazón late al ritmo del acordeón, y cada solicitud nos conecta con la pasión por nuestra música.
        </p>
        <p>
          Antes de pedir tu canción, te invitamos a leer estas sencillas reglas para que todo suene con armonía:
        </p>

        <ol class="ps-3">
          <li class="mb-2">
            <strong>Máximo <?php echo $numeroLimite; ?> <?= $numeroLimite == 1 ? 'cancion' : 'canciones' ?> por persona.</strong><br>
            Puedes realizar hasta <strong><?php echo $numeroLimite; ?> <?= $numeroLimite == 1 ? 'solicitud' : 'solicitudes' ?> en total</strong>.  
            Así todos tendrán la oportunidad de hacer sonar su tema favorito, cuando <?= $numeroLimite == 1 ? 'tu canción suene' : 'tus canciones suenen' ?> y el DJ habilite más solicitudes tendras la oportunidad de pedir más.
          </li>
          <li class="mb-2">
            <strong>Una canción por formulario.</strong><br>
            Si escribes varias canciones en una sola solicitud, es posible que  
            <strong>ninguna sea programada</strong>. Por eso te pedimos enviar solo una por vez.
          </li>
          <li class="mb-2">
            <strong>Canciones conocidas en nuestra zona.</strong><br>
            Algunas canciones pueden no estar disponibles o no ser tan populares en nuestra región,  
            por lo que podrían <strong>no sonar al aire</strong>.
          </li>
          <li class="mb-2">
            <strong>Paciencia y buena vibra.</strong><br>
            Recibimos muchas solicitudes en la noche 🎧, así que tu canción puede tardar un poquito en sonar,  
            ¡pero llegará con todo el cariño vallenato!
          </li>
        </ol>

        <p class="mt-3 mb-0 text-center">
          💛 <em>Gracias por hacer parte de esta parranda musical.  
          Con tu alegría seguimos <strong>Vallenateando</strong> juntos,  
          <br>¡porque somos <strong>la casa del Vallenato en Armenia</strong>!</em> 💛
        </p>
      </div>

      <div class="modal-footer">
        <button class="btn btn-warning text-dark fw-bold" data-bs-dismiss="modal">Aceptar</button>
      </div>
    </div>
  </div>
</div>

<!-- ============================================ -->
<!-- SCRIPTS EN ORDEN CORRECTO -->
<!-- ============================================ -->

<!-- 1. jQuery PRIMERO -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- 2. Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- 3. TODA LA LÓGICA EN UN SOLO BLOQUE -->
<script>
// ============================================
// APLICACIÓN PRINCIPAL
// ============================================
(function() {
  'use strict';
  
  // Variables globales de configuración del servidor
  const CONFIG = {
    formularioActivo: <?= getSetting('formulario_publico') === 'true' ? 'true' : 'false' ?>,
    horaReactivacion: "<?= getSetting('formulario_reactivacion') ?? '' ?>"
  };
  
  let timerBloqueo = null;
  let autoRefreshInterval = null;
  
  // ============================================
  // INICIALIZACIÓN PRINCIPAL
  // ============================================
  document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ DOM completamente cargado');
    
    // Limpiar URL de parámetros
    limpiarURL();
    
    // Inicializar todos los módulos
    initModalTerminos();
    initFormulario();
    initAutocomplete();
    initControlFormulario();
    initAutoRefresh();
    prepararModalComentarios();
    
    console.log('✅ Todos los módulos inicializados correctamente');
  });
  
  // ============================================
  // LIMPIAR URL
  // ============================================
  function limpiarURL() {
    if (window.location.search || window.location.hash) {
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  }
  
  // ============================================
  // MODAL DE TÉRMINOS (PRIMERA VISITA)
  // ============================================
  function initModalTerminos() {
    if (!localStorage.getItem('terminosVistos')) {
      console.log('🔔 Primera visita - Mostrando modal de términos');
      
      // Esperar a que Bootstrap esté completamente listo
      setTimeout(function() {
        const modalEl = document.getElementById('modalTerminos');
        if (modalEl && typeof bootstrap !== 'undefined') {
          const modal = new bootstrap.Modal(modalEl);
          modal.show();
          
          // Guardar cuando se cierre
          modalEl.addEventListener('hidden.bs.modal', function() {
            localStorage.setItem('terminosVistos', 'true');
            console.log('✅ Términos marcados como vistos');
          }, { once: true });
        }
      }, 400);
    }
  }
  
  // ============================================
  // FORMULARIO DE SOLICITUD
  // ============================================
  function initFormulario() {
    // Usar delegación de eventos para asegurar que funcione siempre
    $(document).off('submit', '#formSolicitud').on('submit', '#formSolicitud', async function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const formData = new FormData(this);
      const form = this;
      
      try {
        const response = await fetch('controller/guardar.php', { 
          method: 'POST', 
          body: formData 
        });
        
        const raw = await response.text();
        const cleaned = raw.trim().replace(/^\uFEFF/, '');
        let result;
        
        try {
          result = JSON.parse(cleaned);
        } catch (jsonError) {
          console.error('❌ Error parseando JSON:', jsonError);
          Swal.fire({
            icon: 'error',
            title: 'Error del servidor',
            html: '<pre style="text-align:left; font-size:12px;">' + cleaned.substring(0, 500) + '</pre>'
          });
          return;
        }

        if (result.status === 'success') {
          Swal.fire({
            icon: 'success',
            title: '¡Listo!',
            text: result.message,
            showConfirmButton: false,
            timer: 2000
          });
          
          form.reset();
          setTimeout(() => actualizarTabla(), 500);

        } else if (result.status === 'limit' || result.status === 'duplicate') {
          Swal.fire({
            icon: 'warning',
            title: result.status === 'limit' ? 'Límite alcanzado' : 'Canción duplicada',
            text: result.message
          });
          if (result.status === 'limit') bloquearFormulario();

        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: result.message
          });
        }
        
      } catch (error) {
        console.error('❌ Error en la petición:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'No se pudo enviar la solicitud.'
        });
      }
      
      return false;
    });
    
    console.log('✅ Formulario inicializado con delegación de eventos');
  }
  
  // ============================================
  // ACTUALIZAR TABLA Y CONTADOR
  // ============================================
  async function actualizarTabla() {
    try {
      const res = await fetch('controller/listar.php');
      const html = await res.text();
      $('#tablaSolicitudes').html(html);

      const contadorRes = await fetch('controller/contador.php');
      const contadorHtml = await contadorRes.text();
      $('#contadorSolicitudes').html(contadorHtml);

      const texto = contadorHtml
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim();

      const limiteDetectado =
        texto.includes("has llegado al limite") ||
        texto.includes("has alcanzado el limite") ||
        texto.includes("limite de") ||
        texto.includes("limite alcanzado");

      if (limiteDetectado) {
        bloquearFormulario();
      } else {
        desbloquearFormulario();
      }

      prepararModalComentarios();

    } catch (err) {
      console.error("❌ Error al actualizar tabla:", err);
    }
  }
  
  // ============================================
  // BLOQUEO/DESBLOQUEO DE FORMULARIO
  // ============================================
  function bloquearFormulario() {
    $('#formSolicitud input, #formSolicitud select, #btnEnviar').prop('disabled', true);
    $('#btnEnviar').text('Límite alcanzado').addClass('disabled');
  }

  function desbloquearFormulario() {
    $('#formSolicitud input, #formSolicitud select, #btnEnviar').prop('disabled', false);
    $('#btnEnviar').text('Enviar solicitud').removeClass('disabled');
  }
  
  // ============================================
  // MODAL DE COMENTARIOS (CON DELEGACIÓN)
  // ============================================
  function prepararModalComentarios() {
    // Usar delegación para elementos dinámicos
    $(document).off('click', '.comentable').on('click', '.comentable', function() {
      const comentario = $(this).data('comentario');
      if (!comentario || !comentario.trim()) return;

      let modal = $('#comentarioModal');
      if (!modal.length) {
        const modalHTML = `
          <div class="modal fade" id="comentarioModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content bg-dark text-light">
                <div class="modal-header bg-danger text-white">
                  <h5 class="modal-title">Comentario del administrador</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="comentarioTexto" style="white-space: pre-line; font-size: 1rem;"></div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-success" data-bs-dismiss="modal">Cerrar</button>
                </div>
              </div>
            </div>
          </div>`;
        $('body').append(modalHTML);
        modal = $('#comentarioModal');
      }

      $('#comentarioTexto').text(comentario);
      const modalInstancia = new bootstrap.Modal(modal[0]);
      modalInstancia.show();
    });
  }
  
  // ============================================
  // AUTO-REFRESH CADA 5 SEGUNDOS
  // ============================================
  function initAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    autoRefreshInterval = setInterval(() => actualizarTabla(), 5000);
    console.log('✅ Auto-refresh activado (cada 5 segundos)');
  }
  
  // ============================================
  // AUTOCOMPLETE DE ARTISTAS Y CANCIONES
  // ============================================
  function initAutocomplete() {
    async function obtenerSugerencias(texto, tipo) {
      const res = await fetch('controller/sugerencias.php?tipo=' + tipo + '&q=' + encodeURIComponent(texto));
      return await res.json();
    }

    function renderSugerencias(box, lista, input) {
      box.empty();
      if (lista.length === 0) {
        box.hide();
        return;
      }

      lista.forEach(item => {
        const div = $('<div></div>')
          .addClass('autocomplete-item')
          .text(item)
          .on('click', function() {
            input.val(item);
            box.hide();
          });
        box.append(div);
      });

      box.show();
    }

    function activarAutocomplete(inputId, boxId, tipo) {
      const input = $('#' + inputId);
      const box = $('#' + boxId);

      // Usar delegación también aquí
      $(document).off('input', '#' + inputId).on('input', '#' + inputId, async function() {
        const texto = $(this).val().trim();
        if (texto.length < 2) {
          box.hide();
          return;
        }
        const sugerencias = await obtenerSugerencias(texto, tipo);
        renderSugerencias(box, sugerencias, input);
      });

      // Cerrar al hacer clic fuera
      $(document).on('click', function(e) {
        if (!box.is(e.target) && !box.has(e.target).length && !input.is(e.target)) {
          box.hide();
        }
      });
    }

    activarAutocomplete('inputArtista', 'autoArtista', 'artista');
    activarAutocomplete('inputCancion', 'autoCancion', 'cancion');
    
    console.log('✅ Autocomplete inicializado');
  }
  
  // ============================================
  // CONTROL DE FORMULARIO PÚBLICO
  // ============================================
  function initControlFormulario() {
    const contenedorFormulario = $('#contenedorFormulario');
    const mensajeBloqueo = $('#mensajeBloqueo');
    const relojRestante = $('#relojRestante');

    if (!CONFIG.formularioActivo) {
      desactivarFormulario(CONFIG.horaReactivacion);
    } else {
      activarFormulario();
    }

    // Verificar estado cada 5 segundos
    setInterval(verificarEstadoFormulario, 5000);

    async function verificarEstadoFormulario() {
      try {
        const res = await fetch('controller/estado_formulario.php');
        const data = await res.json();

        if (data.activo) {
          activarFormulario();
        } else {
          desactivarFormulario(data.hora_fin);
        }
      } catch (e) {
        console.error('Error verificando estado:', e);
      }
    }

    function desactivarFormulario(horaFin) {
      contenedorFormulario.hide();
      mensajeBloqueo.show();
      if (horaFin) iniciarReloj(horaFin);
    }

    function activarFormulario() {
      mensajeBloqueo.hide();
      contenedorFormulario.show();
      if (timerBloqueo) clearInterval(timerBloqueo);
    }

    function iniciarReloj(horaFin) {
      if (timerBloqueo) clearInterval(timerBloqueo);
      
      const ahora = new Date();
      const [h, m] = horaFin.split(':').map(Number);
      const objetivo = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate(), h, m, 0);

      if (objetivo <= ahora) {
        activarFormulario();
        return;
      }

      timerBloqueo = setInterval(() => {
        const diff = objetivo - new Date();
        if (diff <= 0) {
          clearInterval(timerBloqueo);
          activarFormulario();
          return;
        }
        const horas = Math.floor(diff / 1000 / 60 / 60);
        const mins = Math.floor((diff / 1000 / 60) % 60);
        const segs = Math.floor((diff / 1000) % 60);
        relojRestante.text(
          String(horas).padStart(2,'0') + ':' + 
          String(mins).padStart(2,'0') + ':' + 
          String(segs).padStart(2,'0')
        );
      }, 1000);
    }
    
    console.log('✅ Control de formulario público inicializado');
  }
  
})();
</script>

</body>
</html>
