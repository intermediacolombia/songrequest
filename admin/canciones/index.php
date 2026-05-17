<?php
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../login/session.php';

$cupoGlobal = (int)(getSetting('cupo_global') ?: 10);
$stmtCupo = $GLOBALS['pdo']->query("SELECT COUNT(*) FROM solicitudes WHERE estado IN ('Pendiente','Programada')");
$cupoActual = (int)$stmtCupo->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel de Administración - Solicitudes</title>

<!-- Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<!-- FontAwesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<style>
body { background: #f8f9fa; }
.btn-action { margin: 0 2px; }
.table td, .table th { vertical-align: middle !important; }
.card-counter {
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    padding: 15px;
    border-radius: 10px;
    color: #fff;
    text-align: center;
}
.card-counter h4 { margin: 0; font-size: 1.2rem; }
.card-counter span { font-size: 1.6rem; font-weight: bold; display: block; }
.bg-pendiente { background: #ffc107; color: #000; }
.bg-programada { background: #0d6efd; color: #000; }
.bg-sonada { background: #28a745; }
.bg-rechazada { background: #dc3545; }

.navbar {
  background: linear-gradient(90deg, #111, #222, #111);
  border-bottom: 2px solid #f8c200;
}
.navbar-brand {
  color: #f8c200 !important;
  letter-spacing: 0.5px;
}
.navbar .btn-outline-warning:hover {
  background-color: #f8c200;
  color: #000;
  border-color: #f8c200;
}
.form-check-input:checked {
  background-color: #f8c200 !important;
  border-color: #f8c200 !important;
}

	
	
	
/* ====== MODO OSCURO ====== */
body.modo-oscuro {
  background-color: #121212 !important;
  color: #f1f1f1 !important;
}

body.modo-oscuro .card,
body.modo-oscuro .table {
  background-color: #1e1e1e !important;
  color: #ddd !important;
}

body.modo-oscuro .table-striped tbody tr:nth-of-type(odd) {
  background-color: #252525 !important;
}

body.modo-oscuro .navbar {
  background: linear-gradient(90deg, #000, #111, #000);
  border-bottom: 2px solid #b78e00;
}

body.modo-oscuro .card-counter {
  box-shadow: none;
  opacity: 0.95;
}

/* ====== MODO OSCURO SOLO PARA LA TABLA ====== */
body.modo-oscuro .table thead th {
  background-color: #111 !important;
  color: #f1f1f1 !important;
  border-color: #2b2b2b !important;
}

body.modo-oscuro .table tbody tr {
  background-color: #1b1b1b !important;
  color: #ddd !important;
}

body.modo-oscuro .table tbody tr:nth-of-type(even) {
  background-color: #161616 !important;
}

body.modo-oscuro .table tbody tr:hover {
  background-color: rgba(248, 194, 0, 0.15) !important;
}

body.modo-oscuro .table td, 
body.modo-oscuro .table th {
  border-color: #2b2b2b !important;
	background: #000;
	color: #fff;
}

body.modo-oscuro .modal-body {
    
    background: #1E1E1E!important;
}

	body.modo-oscuro  .modal-footer {
   background: #1E1E1E!important;
}

/* Controles DataTables */
body.modo-oscuro .dataTables_wrapper .dataTables_filter input,
body.modo-oscuro .dataTables_wrapper .dataTables_length select {
  background-color: #1a1a1a !important;
  color: #eaeaea !important;
  border: 1px solid #2a2a2a !important;
}
body.modo-oscuro .dataTables_wrapper .dataTables_filter label,
body.modo-oscuro .dataTables_wrapper .dataTables_length label,
body.modo-oscuro .dataTables_wrapper .dataTables_info {
  color: #ccc !important;
}
body.modo-oscuro .dataTables_wrapper .dataTables_paginate .paginate_button {
  background-color: #1a1a1a !important;
  color: #eaeaea !important;
  border: 1px solid #2a2a2a !important;
}
body.modo-oscuro .dataTables_wrapper .dataTables_paginate .paginate_button.current .textos,
body.modo-oscuro .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
  background-color: #222 !important;
  color: #fff !important;
  border: 1px solid #3a3a3a !important;
}
	
	/* === Tarjeta del límite === */
.card-limite {
  width: 140px;
  background: #f1f3f4;
  border: 1px solid rgba(0,0,0,0.1);

  transition: background 0.3s, color 0.3s, border-color 0.3s;
}

.card-limite {
  width: 140px;
  background: #f1f3f4;
  border: 1px solid rgba(0,0,0,0.1);
  transition: background 0.3s, color 0.3s, border-color 0.3s;
}

/* Mantiene la altura fija del área del mensaje */
.estado-container {
  min-height: 18px; /* espacio suficiente para cualquier mensaje */
  line-height: 18px;
}

.estado-limite {
  font-size: 0.75rem;
  display: inline-block;
  transition: color 0.3s, opacity 0.3s;
}

.estado-limite.guardando { color: #999; }
.estado-limite.ok { color: #28a745; }
.estado-limite.error { color: #dc3545; }
.estado-limite.base { color: #6c757d; }

/* Tema oscuro */
body.modo-oscuro .card-limite {
  background: #1e1e1e;
  border: 1px solid #333;
  color: #e0e0e0;
}
body.modo-oscuro .estado-limite.base { color: #bbb; }


.counter-icon {
    position: absolute;
    right: 15px;
    bottom: 10px;
    font-size: 58px;   /* tamaño grande */
    opacity: 0.15;     /* muy tenue */
    pointer-events: none;
}

.copy-icon {
    margin-left: 6px;
    color: #555;
    cursor: pointer;
    font-size: 14px;
    opacity: .6;
    transition: opacity .2s ease;
}

/* Ocultar el icono por defecto */
.table td .copy-icon {
    opacity: 0;
    margin-left: 6px;
    color: #555;
    cursor: pointer;
    font-size: 14px;
    transition: opacity .2s ease;
}

/* Mostrar solo cuando el mouse está sobre la celda */
.table td:hover .copy-icon {
    opacity: 0.8;
}

.table td:hover .copy-icon:hover {
    opacity: 1;
    color: #000;
}

/* Modo oscuro */
body.modo-oscuro .table td:hover .copy-icon {
    color: #ddd;
}
body.modo-oscuro .table td:hover .copy-icon:hover {
    color: #fff;
}

/* Estilos para el dropdown de filtros */
.dropdown-menu {
  max-height: 400px;
  overflow-y: auto;
}

body.modo-oscuro .dropdown-menu {
  background-color: #1e1e1e !important;
  border-color: #333 !important;
}

body.modo-oscuro .dropdown-item {
  color: #ddd !important;
}

body.modo-oscuro .dropdown-item:hover {
  background-color: #2a2a2a !important;
}

body.modo-oscuro .dropdown-divider {
  border-color: #444 !important;
}
/* Texto pequeño informativo en dropdowns */
body.modo-oscuro .dropdown-menu .text-muted {
  color: #999 !important;
}

/* Checkboxes en dropdowns - modo oscuro */
body.modo-oscuro .dropdown-menu .form-check-input {
  background-color: #2a2a2a !important;
  border-color: #444 !important;
}

body.modo-oscuro .dropdown-menu .form-check-input:checked {
  background-color: #f8c200 !important;
  border-color: #f8c200 !important;
}

body.modo-oscuro .dropdown-menu .form-check-label {
  color: #ddd !important;
}

/* Botones dentro de dropdowns - modo oscuro */
body.modo-oscuro .dropdown-menu .btn-outline-danger {
  color: #ff6b6b !important;
  border-color: #ff6b6b !important;
}

body.modo-oscuro .dropdown-menu .btn-outline-danger:hover {
  background-color: #ff6b6b !important;
  color: #000 !important;
}

body.modo-oscuro .dropdown-menu .btn-outline-success {
  color: #51cf66 !important;
  border-color: #51cf66 !important;
}

body.modo-oscuro .dropdown-menu .btn-outline-success:hover {
  background-color: #51cf66 !important;
  color: #000 !important;
}

body.modo-oscuro .dropdown-menu .btn-primary {
  background-color: #4dabf7 !important;
  border-color: #4dabf7 !important;
}

body.modo-oscuro .dropdown-menu .btn-primary:hover {
  background-color: #339af0 !important;
}

/* Botón principal "Bloquear géneros" en modo oscuro */
body.modo-oscuro .btn-outline-danger {
  color: #ff6b6b !important;
  border-color: #ff6b6b !important;
}

body.modo-oscuro .btn-outline-danger:hover {
  background-color: #ff6b6b !important;
  color: #fff !important;
}

/* Badge de contador en modo oscuro */
body.modo-oscuro .badge.bg-danger {
  background-color: #c92a2a !important;
}

body.modo-oscuro .badge.bg-warning {
  background-color: #fab005 !important;
  color: #000 !important;
}
</style>
</head>
<body class="p-4">
<!-- BARRA SUPERIOR FIJA -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm fixed-top">
  <div class="container-fluid px-4 d-flex justify-content-between align-items-center">

    <!-- Logo / Nombre del panel -->
    <div class="d-flex align-items-center">
      <i class="fas fa-headphones-alt text-warning me-2 fs-5"></i>
      <span class="navbar-brand fw-bold mb-0 fs-5">Panel de Solicitudes</span>
    </div>

    <!-- Usuario, switch y salir -->
    <div class="d-flex align-items-center gap-3">

      <!-- Modo oscuro -->
      <div class="form-check form-switch text-warning mb-0">
        <input class="form-check-input bg-warning border-0" type="checkbox" id="modoOscuroSwitch">
        <label class="form-check-label small" for="modoOscuroSwitch">
          <i class="fas fa-moon"></i>
        </label>
      </div>

      <!-- Nombre de usuario -->
      <span class="text-white fw-semibold">
        <i class="fas fa-user-circle text-warning me-1"></i>
        <?php echo htmlspecialchars($nombre . " " . $apellido); ?>
      </span>

      <!-- Botón salir -->
      <a href="<?php echo URLBASE; ?>/admin/login/logout.php" 
         class="btn btn-outline-warning btn-sm fw-bold px-3">
        <i class="fas fa-sign-out-alt me-1"></i> Salir
      </a>
    </div>

  </div>
</nav>

<!-- Espaciado para compensar barra fija -->
<div style="height:70px;"></div>

<!-- FIN BARRA SUPERIOR FIJA -->
<div class="container-fluid">

  <!-- ENCABEZADO + SWITCH -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="fw-bold mb-0">Panel de Solicitudes</h1>
	  
	  <!-- Tarjetas de límites -->
<div class="d-flex gap-2">

  <!-- Límite por usuario -->
  <div class="card-limite text-center p-2 rounded-3 shadow-sm">
    <label for="inputLimite" class="fw-semibold small d-block mb-1">Límite por usuario</label>
    <input type="number" id="inputLimite" min="1"
           class="form-control form-control-sm text-center mx-auto"
           style="width: 70px;"
           value="<?= htmlspecialchars(getSetting('limite_solicitudes') ?: 3) ?>">
    <div class="estado-container mt-1">
      <span id="statusLimite" class="estado-limite">Canciones</span>
    </div>
  </div>

  <!-- Cupo global -->
  <div class="card-limite text-center p-2 rounded-3 shadow-sm">
    <label for="inputCupoGlobal" class="fw-semibold small d-block mb-1">Cupo global</label>
    <input type="number" id="inputCupoGlobal" min="1"
           class="form-control form-control-sm text-center mx-auto"
           style="width: 70px;"
           value="<?= htmlspecialchars($cupoGlobal) ?>">
    <div class="estado-container mt-1">
      <span id="statusCupoGlobal" class="estado-limite base">
        <span id="cupoActualDisplay"><?= $cupoActual ?></span>/<?= $cupoGlobal ?>
      </span>
    </div>
  </div>

</div>




    
	  <div class="d-flex flex-column align-items-start" style="gap:6px;">
  <?php
    $estado = getSetting('formulario_publico') === 'true';
    $reactivacion = getSetting('formulario_reactivacion');
    $permanente = getSetting('formulario_permanente') === 'true';
  ?>
  
  <!--  Switch normal -->
  <div class="form-check form-switch d-flex align-items-center">
    <input 
      class="form-check-input me-2" 
      type="checkbox" 
      id="switchFormulario" 
      <?= $estado ? 'checked' : '' ?> 
      <?= $permanente ? 'disabled' : '' ?>>
    <label class="form-check-label" for="switchFormulario">
      Formulario público 
      <span id="estadoTexto" class="ms-1"><?= $estado ? '🟢 Activado' : '🔴 Desactivado' ?></span>
    </label>
  </div>

  <!--  Contador de reactivación -->
  <div id="contadorContainer" class="text-danger fw-bold small" style="display:none;">
    Se reactivará automáticamente a las 
    <span id="horaReactivacionTexto"></span> 
    (<span id="contadorTiempo">--:--:--</span>)
  </div>

  <!--  Switch de desactivación permanente -->
  <div class="form-check form-switch d-flex align-items-center mt-2">
    <input 
      class="form-check-input me-2" 
      type="checkbox" 
      id="switchPermanente" 
      <?= $permanente ? 'checked' : '' ?>>
    <label class="form-check-label" for="switchPermanente">
      Desactivación permanente 
      <span id="estadoPermanenteTexto" class="ms-1"><?= $permanente ? '🔴 Activa' : '🟢 Inactiva' ?></span>
    </label>
  </div>
</div>



	  
	  
  </div>

  <!-- Modal para programar reactivación -->
  <div class="modal fade" id="modalHora" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header bg-warning">
          <h5 class="modal-title">⏰ Desactivar formulario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p>Indica hasta qué hora estará desactivado el formulario público:</p>
          <input type="time" id="horaFin" class="form-control">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" id="guardarHora" class="btn btn-warning">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ============ -->
  <!-- CONTADORES -->
  <!-- ============ -->
<div class="row row-cols-1 row-cols-md-4 g-4 text-center mb-4" id="contadores">

    <div class="col">
        <div class="card-counter bg-pendiente position-relative">
            <i class="fa fa-clock"></i>
            <h4>Pendientes</h4>
            <span id="countPendientes">0</span>
        </div>
    </div>

    <div class="col">
        <div class="card-counter bg-programada position-relative">
            <i class="fas fa-music"></i>
            <h4>Programadas</h4>
            <span id="countProgramadas">0</span>
        </div>
    </div>

    <div class="col">
        <div class="card-counter bg-sonada position-relative">
            <i class="fas fa-check"></i>
            <h4>Sonadas</h4>
            <span id="countSonadas">0</span>
        </div>
    </div>

    <div class="col">
        <div class="card-counter bg-rechazada position-relative">
            <i class="fas fa-times"></i>
            <h4>Rechazadas</h4>
            <span id="countRechazadas">0</span>
        </div>
    </div>

</div>

	
	
	<!-- BOTONES DE ACCIÓN MASIVA -->
<div id="accionesMasivas" class="mb-3" style="display:none;">
  <div class="d-flex flex-wrap justify-content-center gap-2">
    <button id="btnMasivaSonada" class="btn btn-success btn-sm">
      <i class="fas fa-check"></i> Marcar Sonadas
    </button>
    <button id="btnMasivaPendiente" class="btn btn-warning btn-sm">
      <i class="fa fa-clock"></i> Marcar Pendientes
    </button>
	<button id="btnMasivaProgramada" class="btn bg-primary btn-sm">
      <i class="fas fa-music"></i> Marcar Programada
    </button>
    <button id="btnMasivaRechazar" class="btn btn-danger btn-sm">
      <i class="fas fa-times"></i> Rechazar Seleccionadas
    </button>
	  
	<button id="btnMasivaBloquear" class="btn btn-dark btn-sm">
  <i class="fas fa-lock"></i> Bloquear Seleccionadas
</button>
<button id="btnMasivaDesbloquear" class="btn btn-secondary btn-sm">
  <i class="fas fa-lock-open"></i> Desbloquear Seleccionadas
</button>

	  
	  
    <button id="btnMasivaEliminar" class="btn btn-secondary btn-sm">
      <i class="fas fa-trash"></i> Eliminar Seleccionadas
    </button>
  </div>
</div>

 <!-- ============ -->
<!-- FILTROS EN LÍNEA (UNO AL LADO DEL OTRO) -->
<!-- ============ -->
<div class="mb-3 d-flex gap-2 flex-wrap align-items-center">
  
  <!-- Filtro de Estados -->
  <div class="dropdown">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="filtroEstadosBtn" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="fas fa-filter me-2"></i>Filtrar por estado
      <span id="contadorFiltros" class="badge bg-warning text-dark ms-2" style="display:none;">0</span>
    </button>
    <ul class="dropdown-menu p-3" aria-labelledby="filtroEstadosBtn" style="min-width: 250px;" onclick="event.stopPropagation();">
      <li class="mb-2">
        <div class="form-check">
          <input class="form-check-input filtro-estado" type="checkbox" value="Pendiente" id="filtroPendiente" checked>
          <label class="form-check-label" for="filtroPendiente">
            <span class="badge bg-warning text-dark">Pendiente</span>
          </label>
        </div>
      </li>
      <li class="mb-2">
        <div class="form-check">
          <input class="form-check-input filtro-estado" type="checkbox" value="Programada" id="filtroProgramada" checked>
          <label class="form-check-label" for="filtroProgramada">
            <span class="badge bg-primary text-dark">Programada</span>
          </label>
        </div>
      </li>
      <li class="mb-2">
        <div class="form-check">
          <input class="form-check-input filtro-estado" type="checkbox" value="Sonada" id="filtroSonada" checked>
          <label class="form-check-label" for="filtroSonada">
            <span class="badge bg-success">Sonada</span>
          </label>
        </div>
      </li>
      <li class="mb-2">
        <div class="form-check">
          <input class="form-check-input filtro-estado" type="checkbox" value="Rechazada" id="filtroRechazada" checked>
          <label class="form-check-label" for="filtroRechazada">
            <span class="badge bg-danger">Rechazada</span>
          </label>
        </div>
      </li>
      <li><hr class="dropdown-divider"></li>
      <li class="d-flex justify-content-between">
        <button class="btn btn-sm btn-outline-primary" id="btnSeleccionarTodos">
          <i class="fas fa-check-double"></i> Todos
        </button>
        <button class="btn btn-sm btn-outline-secondary" id="btnLimpiarFiltros">
          <i class="fas fa-times"></i> Limpiar
        </button>
      </li>
    </ul>
  </div>

  <!-- Control de Géneros Bloqueados (AL LADO) -->
  <div class="dropdown">
    <button class="btn btn-outline-danger dropdown-toggle" type="button" id="controlGenerosBtn" data-bs-toggle="dropdown" aria-expanded="false">
      <i class="fas fa-ban me-2"></i>Bloquear géneros
      <span id="contadorGenerosBloqueados" class="badge bg-danger text-white ms-2" style="display:none;">0</span>
    </button>
    <ul class="dropdown-menu p-3" aria-labelledby="controlGenerosBtn" style="min-width: 280px;" onclick="event.stopPropagation();">
      <li class="mb-2">
        <small class="text-muted d-block mb-2">
          <i class="fas fa-info-circle"></i> Los géneros bloqueados NO aparecerán en el formulario público
        </small>
      </li>
      <?php
      // Obtener géneros bloqueados actuales
      $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE setting_name = 'generos_bloqueados'");
      $stmt->execute();
      $generosBloqueadosJson = $stmt->fetchColumn();
      $generosBloqueados = $generosBloqueadosJson ? json_decode($generosBloqueadosJson, true) : [];
      
      $todosGeneros = ['Vallenato', 'Popular', 'Salsa', 'Merengue'];
      foreach ($todosGeneros as $genero):
        $bloqueado = in_array($genero, $generosBloqueados);
      ?>
      <li class="mb-2">
        <div class="form-check">
          <input class="form-check-input bloqueo-genero" type="checkbox" value="<?= $genero ?>" 
                 id="genero<?= $genero ?>" <?= $bloqueado ? 'checked' : '' ?>>
          <label class="form-check-label" for="genero<?= $genero ?>">
            <i class="fas fa-music me-1"></i><?= $genero ?>
          </label>
        </div>
      </li>
      <?php endforeach; ?>
      <li><hr class="dropdown-divider"></li>
      <li class="d-flex justify-content-between">
        <button class="btn btn-sm btn-outline-danger" id="btnBloquearTodosGeneros">
          <i class="fas fa-ban"></i> Bloquear todos
        </button>
        <button class="btn btn-sm btn-outline-success" id="btnDesbloquearTodosGeneros">
          <i class="fas fa-check"></i> Habilitar todos
        </button>
      </li>
      <li class="mt-2">
        <button class="btn btn-sm btn-primary w-100" id="btnGuardarGeneros">
          <i class="fas fa-save"></i> Guardar cambios
        </button>
      </li>
    </ul>
  </div>

</div>
  <!-- ============ -->
  <!-- TABLA -->
  <!-- ============ -->
  <div class="card shadow">
    <div class="card-body">
      <table id="tablaAdmin" class="table table-striped table-hover align-middle" width="100%">
        <thead class="table-dark text-center">
  <tr>
    <th><input type="checkbox" id="chkAll"></th>
    <th>ID</th><th>Nombre</th><th>Canción</th><th>Artista</th><th>Género</th><th>Estado</th><th>Bloqueo</th>

    <th>Com</th><th>Fecha</th><th>Hora</th><th>Acciones</th>
  </tr>
</thead>

        <tbody></tbody>
      </table>
    </div>
  </div>
</div>

<!-- ========================== -->
<!-- MODAL PARA COMENTARIOS -->
<!-- ========================== -->
<div class="modal fade" id="comentarioModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title">Agregar Comentario</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="comentarioId">
        <textarea id="comentarioTexto" class="form-control" rows="4" placeholder="Escribe el comentario..."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn btn-success" id="guardarComentario"><i class="fas fa-save"></i> Guardar</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<!-- Dependencias -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
	
	  // ============================
  // NOTIFICACIONES PUSH
  // ============================
  let ultimaSolicitudId = null; // Para detectar nuevas

  async function pedirPermisoNotificaciones() {
    if (!("Notification" in window)) return;
    if (Notification.permission === "default") {
      await Notification.requestPermission();
    }
  }

  function mostrarNotificacion(solicitud) {
    if (Notification.permission !== "granted") return;
    const titulo = "Nueva solicitud de canción";
    const cuerpo = `${solicitud.nombre} ha solicitado - ${solicitud.artista} - ${solicitud.cancion}`;
    const opciones = {
      body: cuerpo,
      icon: "https://songrequest.intermediacolombia.com/attachments/logo/logo.png",
      badge: "https://songrequest.intermediacolombia.com/attachments/logo/logo.png"
    };
    const noti = new Notification(titulo, opciones);
    noti.onclick = () => { window.focus(); noti.close(); };
  }

  pedirPermisoNotificaciones();


  // ================================
  // INICIALIZAR DATATABLE
  // ================================
  const tabla = $('#tablaAdmin').DataTable({
        ajax: {
      url: 'actions.php?action=listar',
      dataSrc: function(json) {
        actualizarContadores(json.data);

        if (json.data && json.data.length > 0) {
          const ultima = json.data[0];
          if (!ultimaSolicitudId) {
            ultimaSolicitudId = ultima.id;
          } else if (ultima.id > ultimaSolicitudId) {
            ultimaSolicitudId = ultima.id;
            mostrarNotificacion(ultima);
          }
        }

        return json.data;
      }
    },

    columns: [
      { 
        data: null,
        orderable: false,
        className: 'text-center',
        render: function(data) {
          return `<input type="checkbox" class="chkSelect" value="${data.id}">`;
        }
      },
      { data: 'id' },
      { data: 'nombre' },
      {
  data: 'cancion',
  render: function(txt) {
    return `
      <span>${txt}</span>
      <i class="fas fa-copy copy-icon" data-copy="${txt}" title="Copiar"></i>
    `;
  }
},
{
  data: 'artista',
  render: function(txt) {
    return `
      <span>${txt}</span>
      <i class="fas fa-copy copy-icon" data-copy="${txt}" title="Copiar"></i>
    `;
  }
},


      { data: 'genero' },
      { data: 'estado', render: function(estado) {
          if (estado === 'Pendiente') return '<span class="badge bg-warning text-dark">Pendiente</span>';
          if (estado === 'Programada') return '<span class="badge bg-primary text-dark">Programada</span>';
          if (estado === 'Sonada') return '<span class="badge bg-success">Sonada</span>';
          if (estado === 'Rechazada') return '<span class="badge bg-danger">Rechazada</span>';
          return '';
      }},
		
		
		{ data: 'bloqueada', render: function(d) {
    return d == 1 
      ? '<span class="badge bg-secondary">Bloqueada</span>' 
      : '<span class="badge bg-success">Desbloqueada</span>';
}},

		
      { data: 'comentario', render: function(c) {
          return c ? `<i class="fas fa-comment text-danger" title="${c}"></i>` : '';
      }},
      { data: 'fecha' },
      { data: 'hora' },
      { data: null, orderable: false, className: 'text-center',
        render: function(data) {
  const iconoBloqueo = data.bloqueada == 1 
    ? '<i class="fas fa-lock-open"></i>' 
    : '<i class="fas fa-lock"></i>';
  const tituloBloqueo = data.bloqueada == 1 ? 'Desbloquear' : 'Bloquear';
  const claseBloqueo = data.bloqueada == 1 ? 'btn-dark' : 'btn-secondary';

  return `
    <button class="btn btn-success btn-sm btn-action sonada" data-id="${data.id}" title="Marcar Sonada"><i class="fas fa-check"></i></button>
    <button class="btn btn-warning btn-sm btn-action pendiente" data-id="${data.id}" title="Marcar Pendiente"><i class="fa fa-clock"></i></button>
    <button class="btn btn-primary btn-sm btn-action programada" data-id="${data.id}" title="Marcar Programada"><i class="fas fa-music"></i></button>
    <button class="btn btn-danger btn-sm btn-action rechazar" data-id="${data.id}" title="Rechazar"><i class="fas fa-times"></i></button>
    <button class="btn btn-info btn-sm btn-action comentar" data-id="${data.id}" title="Agregar Comentario"><i class="fas fa-comment-dots"></i></button>
    <button class="btn ${claseBloqueo} btn-sm btn-action toggleBloqueo" data-id="${data.id}" data-bloqueada="${data.bloqueada}" title="${tituloBloqueo}">
      ${iconoBloqueo}
    </button>
    <button class="btn btn-secondary btn-sm btn-action borrar" data-id="${data.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
  `;
}

      }
    ],
    order: [[1, 'asc']],
    pageLength: 50,
    language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
  });

  // ================================
  // FILTRO DE ESTADOS EN TIEMPO REAL
  // ================================
  
  // Función personalizada de filtrado
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
    const estadoFila = data[6]; // Columna de estado (índice 6)
    const checkboxes = $('.filtro-estado:checked');
    
    // Si no hay ningún checkbox marcado, mostrar todo
    if (checkboxes.length === 0) return true;
    
    // Verificar si el estado de la fila está en los seleccionados
    let mostrar = false;
    checkboxes.each(function() {
      const valorCheckbox = $(this).val();
      if (estadoFila.includes(valorCheckbox)) {
        mostrar = true;
        return false; // break
      }
    });
    
    return mostrar;
  });

  // Evento change en los checkboxes
  $('.filtro-estado').on('change', function() {
    actualizarContadorFiltros();
    tabla.draw();
  });

  // Botón "Seleccionar Todos"
  $('#btnSeleccionarTodos').on('click', function() {
    $('.filtro-estado').prop('checked', true);
    actualizarContadorFiltros();
    tabla.draw();
  });

  // Botón "Limpiar"
  $('#btnLimpiarFiltros').on('click', function() {
    $('.filtro-estado').prop('checked', false);
    actualizarContadorFiltros();
    tabla.draw();
  });

  // Función para actualizar el contador de filtros activos
  function actualizarContadorFiltros() {
    const totalEstados = 4; // Total de estados disponibles
    const seleccionados = $('.filtro-estado:checked').length;
    const contador = $('#contadorFiltros');
    
    if (seleccionados === 0 || seleccionados === totalEstados) {
      contador.hide();
    } else {
      contador.text(seleccionados).show();
    }
  }

  // Inicializar contador
  actualizarContadorFiltros();

  // ================================
  // CONTADORES
  // ================================
  function actualizarContadores(data) {
    const pendientes = data.filter(d => d.estado === 'Pendiente').length;
    const programadas = data.filter(d => d.estado === 'Programada').length;
    const sonadas = data.filter(d => d.estado === 'Sonada').length;
    const rechazadas = data.filter(d => d.estado === 'Rechazada').length;
    $('#countPendientes').text(pendientes);
    $('#countProgramadas').text(programadas);
    $('#countSonadas').text(sonadas);
    $('#countRechazadas').text(rechazadas);

    // Actualizar contador del cupo global
    const activos = pendientes + programadas;
    const limite = parseInt($('#inputCupoGlobal').val()) || 10;
    $('#cupoActualDisplay').text(activos);
    const statusCupo = document.getElementById('statusCupoGlobal');
    if (statusCupo) {
      statusCupo.className = activos >= limite
        ? 'estado-limite error'
        : activos >= Math.ceil(limite * 0.8)
          ? 'estado-limite guardando'
          : 'estado-limite ok';
    }
  }

  // ================================
  // 🔄 AUTO-REFRESH CON CONTROL
  // ================================
  let autoRefresh = null;
  let refreshActivo = true;

  function iniciarAutoRefresh() {
    if (!autoRefresh) {
      autoRefresh = setInterval(() => {
        if (refreshActivo) tabla.ajax.reload(null, false);
      }, 5000);
    }
  }

  function detenerAutoRefresh() {
    if (autoRefresh) {
      clearInterval(autoRefresh);
      autoRefresh = null;
    }
  }

  iniciarAutoRefresh();

  // ================================
  // EVENTOS DE BOTONES INDIVIDUALES
  // ================================
  $('#tablaAdmin').on('click', '.sonada', function() { cambiarEstado($(this).data('id'), 'Sonada'); });
  $('#tablaAdmin').on('click', '.pendiente', function() { cambiarEstado($(this).data('id'), 'Pendiente'); });
  $('#tablaAdmin').on('click', '.programada', function() { cambiarEstado($(this).data('id'), 'Programada'); });
  $('#tablaAdmin').on('click', '.rechazar', function() { cambiarEstado($(this).data('id'), 'Rechazada'); });
  $('#tablaAdmin').on('click', '.borrar', function() { eliminar($(this).data('id')); });
  $('#tablaAdmin').on('click', '.comentar', function() {
    $('#comentarioId').val($(this).data('id'));
    $('#comentarioTexto').val('');
    new bootstrap.Modal(document.getElementById('comentarioModal')).show();
  });
	// Bloquear / Desbloquear
$('#tablaAdmin').on('click', '.toggleBloqueo', function() {
  const id = $(this).data('id');
  const bloqueada = $(this).data('bloqueada'); // 1 o 0
  const nuevoEstado = bloqueada == 1 ? 0 : 1;
  const accionTexto = nuevoEstado == 1 ? 'bloquear' : 'desbloquear';

  Swal.fire({
    title: `¿Deseas ${accionTexto} esta solicitud?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: `Sí, ${accionTexto}`,
    cancelButtonText: 'Cancelar'
  }).then(result => {
    if (!result.isConfirmed) return;

    $.post('actions.php?action=bloqueo', { id, bloqueada: nuevoEstado }, function(res) {
      if (res.status === 'success') {
        Swal.fire('Listo', res.message, 'success');
        $('#tablaAdmin').DataTable().ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json');
  });
});



  // ================================
  // GUARDAR COMENTARIO
  // ================================
  $('#guardarComentario').click(function() {
    const id = $('#comentarioId').val();
    const texto = $('#comentarioTexto').val().trim();
    if (!texto) return Swal.fire('Atención', 'El comentario no puede estar vacío', 'warning');
    $.post('actions.php?action=comentar', { id, comentario: texto }, function(res) {
      if (res.status === 'success') {
        Swal.fire('Guardado', res.message, 'success');
        $('#comentarioModal').modal('hide');
        tabla.ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json');
  });

  // ================================
  // FUNCIONES AJAX ESTADO / ELIMINAR (INDIVIDUAL)
  // ================================
  function cambiarEstado(id, estado) {
    $.post('actions.php?action=estado', { id, estado }, function(res) {
      if (res.status === 'success') {
        Swal.fire('Actualizado', res.message, 'success');
        tabla.ajax.reload(null, false);
      } else {
        Swal.fire('Error', res.message, 'error');
      }
    }, 'json');
  }

  function eliminar(id) {
    Swal.fire({
      title: '¿Eliminar solicitud?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then(result => {
      if (result.isConfirmed) {
        $.post('actions.php?action=eliminar', { id }, function(res) {
          if (res.status === 'success') {
            Swal.fire('Eliminada', res.message, 'success');
            tabla.ajax.reload(null, false);
          } else {
            Swal.fire('Error', res.message, 'error');
          }
        }, 'json');
      }
    });
  }

  // ================================
  // SWITCH FORMULARIO (sin recarga)
  // ================================
  const modalHora = new bootstrap.Modal('#modalHora');
  const contadorContainer = document.getElementById('contadorContainer');
  const contadorTiempo = document.getElementById('contadorTiempo');
  const horaReactivacionIni = "<?= $reactivacion ?? '' ?>";
  const estadoInicial = <?= $estado ? 'true' : 'false' ?>;
  let timerInterval;

  // Mostrar contador si ya está desactivado con hora programada
  if (horaReactivacionIni && !estadoInicial) iniciarContador(horaReactivacionIni);

  $('#switchFormulario').on('change', function() {
    const activo = this.checked;
    if (!activo) {
      // Abrir modal para pedir hora fin
      modalHora.show();
    } else {
      actualizarEstadoFormulario(true);
    }
  });

  $('#guardarHora').on('click', () => {
    const hora = $('#horaFin').val();
    if (!hora) {
      Swal.fire('Atención','Debes indicar una hora de reactivación','warning');
      return;
    }
    modalHora.hide();
    actualizarEstadoFormulario(false, hora);
  });

  function actualizarEstadoFormulario(activo, horaFin='') {
    fetch('toggle_form.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ estado: activo ? 'true' : 'false', hora_fin: horaFin })
    })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'ok') {
        $('#estadoTexto').text(activo ? '🟢 Activado' : '🔴 Desactivado');
        Swal.fire('Actualizado', res.msg, 'success');

        if (!activo && horaFin) iniciarContador(horaFin);
        if (activo) {
          clearInterval(timerInterval);
          contadorContainer.style.display = 'none';
        }
      } else {
        Swal.fire('Error', res.msg, 'error');
      }
    })
    .catch(() => Swal.fire('Error','No se pudo actualizar el estado','error'));
  }

  // ================================
  // SWITCH DESACTIVACIÓN PERMANENTE
  // ================================
  $('#switchPermanente').on('change', function() {
    const activo = this.checked;

    Swal.fire({
      title: activo ? '¿Desactivar permanentemente el formulario?' : '¿Reactivar formulario permanente?',
      text: activo 
        ? 'El formulario quedará completamente bloqueado hasta que lo actives nuevamente.' 
        : 'El formulario volverá a funcionar normalmente.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: activo ? 'Sí, desactivar' : 'Sí, reactivar',
      cancelButtonText: 'Cancelar'
    }).then(result => {
      if (result.isConfirmed) {
        actualizarEstadoPermanente(activo);
      } else {
        $('#switchPermanente').prop('checked', !activo);
      }
    });
  });

  function actualizarEstadoPermanente(activo) {
    fetch('toggle_form.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ permanente: activo ? 'true' : 'false' })
    })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'ok') {
        $('#estadoPermanenteTexto').text(activo ? '🔴 Activa' : '🟢 Inactiva');
        Swal.fire('Actualizado', res.msg, 'success');

        // Si se activa permanente → deshabilitar el otro switch
        if (activo) {
          $('#switchFormulario').prop('checked', false).prop('disabled', true);
          $('#estadoTexto').text('🔴 Desactivado');
          clearInterval(timerInterval);
          contadorContainer.style.display = 'none';
        } else {
          $('#switchFormulario').prop('disabled', false);
        }
      } else {
        Swal.fire('Error', res.msg, 'error');
      }
    })
    .catch(() => Swal.fire('Error', 'No se pudo actualizar el estado permanente', 'error'));
  }

  // ================================
  // CONTADOR DE REACTIVACIÓN AUTOMÁTICA
  // ================================
  function iniciarContador(horaFin) {
    clearInterval(timerInterval);

    const ahora = new Date();
    const [h, m] = horaFin.split(':').map(Number);
    const objetivo = new Date(ahora.getFullYear(), ahora.getMonth(), ahora.getDate(), h, m, 0);

    if (objetivo <= ahora) {
      reactivarFormulario();
      return;
    }

    contadorContainer.style.display = 'block';
    const horaTxt = document.getElementById('horaReactivacionTexto');
    if (horaTxt) horaTxt.textContent = horaFin;

    timerInterval = setInterval(() => {
      const diff = objetivo - new Date();
      if (diff <= 0) {
        clearInterval(timerInterval);
        reactivarFormulario();
        return;
      }
      const horas = Math.floor(diff / 1000 / 60 / 60);
      const mins = Math.floor((diff / 1000 / 60) % 60);
      const segs = Math.floor((diff / 1000) % 60);
      contadorTiempo.textContent = `${String(horas).padStart(2,'0')}:${String(mins).padStart(2,'0')}:${String(segs).padStart(2,'0')}`;
    }, 1000);
  }

  function reactivarFormulario() {
    fetch('toggle_form.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({ estado: 'true' })
    })
    .then(r => r.json())
    .then(res => {
      if (res.status === 'ok') {
        $('#switchFormulario').prop('checked', true);
        $('#estadoTexto').text('🟢 Activado');
        contadorContainer.style.display = 'none';
      }
    })
    .catch(() => console.warn('Error al reactivar automáticamente'));
  }

  // ===================================================
  // 🔹 ACCIONES MASIVAS CON CHECKBOX + CONTROL REFRESH
  // ===================================================
  const contenedorAcciones = $('#accionesMasivas');
  const tablaBody = $('#tablaAdmin tbody');

  function actualizarEstadoAcciones() {
    const seleccionadas = $('.chkSelect:checked').length;
    contenedorAcciones.toggle(seleccionadas > 0);
    refreshActivo = seleccionadas === 0; // si hay seleccionadas → pausa el refresh
  }

  // Individual
  tablaBody.on('change', '.chkSelect', function() {
    actualizarEstadoAcciones();
  });

  // General
  $('#chkAll').on('change', function() {
    const checked = this.checked;
    $('.chkSelect').prop('checked', checked);
    actualizarEstadoAcciones();
  });

  // Obtener IDs seleccionados
  function obtenerSeleccionados() {
    return $('.chkSelect:checked').map(function() { return this.value; }).get();
  }

  // Acciones masivas
  $('#btnMasivaSonada').on('click', () => accionMasiva('Sonada'));
  $('#btnMasivaPendiente').on('click', () => accionMasiva('Pendiente'));
  $('#btnMasivaProgramada').on('click', () => accionMasiva('Programada'));
  $('#btnMasivaRechazar').on('click', () => accionMasiva('Rechazada'));
	$('#btnMasivaBloquear').on('click', () => accionMasiva('Bloquear'));
	$('#btnMasivaDesbloquear').on('click', () => accionMasiva('Desbloquear'));
  $('#btnMasivaEliminar').on('click', () => accionMasiva('Eliminar'));

  // Función centralizada (enviamos IDs como CSV)
  function accionMasiva(tipo) {
    const ids = obtenerSeleccionados();
    if (ids.length === 0) return;

    let texto = '';
    let accion = '';
    let color = '';

    switch (tipo) {
      case 'Sonada': texto = 'marcar como sonadas'; accion = 'estado'; color = 'success'; break;
      case 'Pendiente': texto = 'marcar como pendientes'; accion = 'estado'; color = 'warning'; break;
      case 'Programada': texto = 'marcar como programadas'; accion = 'estado'; color = 'primary'; break;
      case 'Rechazada': texto = 'rechazar'; accion = 'estado'; color = 'danger'; break;
      case 'Eliminar': texto = 'eliminar'; accion = 'eliminar'; color = 'secondary'; break;
		case 'Bloquear': texto = 'bloquear'; accion = 'bloqueo'; color = 'dark'; break;
  		case 'Desbloquear': texto = 'desbloquear'; accion = 'bloqueo'; color = 'secondary'; break;
    }

    Swal.fire({
      title: `¿Deseas ${texto} ${ids.length} solicitud(es)?`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'Cancelar',
      confirmButtonColor: '#28a745'
    }).then(result => {
      if (!result.isConfirmed) return;

      $.ajax({
        url: `masive_actions.php?action=${accion}`,
        method: 'POST',
        data: accion === 'estado'
  ? { ids: ids.join(','), estado: tipo }
  : accion === 'bloqueo'
    ? { ids: ids.join(','), tipo: tipo }
    : { ids: ids.join(',') },

        dataType: 'json',
        success: function(res) {
          if (res.status === 'success' || res.status === 'ok') {
            Swal.fire('¡Listo!', res.message || 'Acción completada.', color);
            tabla.ajax.reload(null, false);
            contenedorAcciones.hide();
            $('#chkAll').prop('checked', false);
            refreshActivo = true; // reactiva el refresh al finalizar
            iniciarAutoRefresh();
          } else {
            Swal.fire('Error', res.message || 'No se pudo completar la acción', 'error');
          }
        },
        error: () => Swal.fire('Error', 'Ocurrió un problema con la conexión.', 'error')
      });
    });
  }

});
</script>


	
	<script>
document.addEventListener("DOMContentLoaded", () => {
  const switchOscuro = document.getElementById('modoOscuroSwitch');
  const body = document.body;

  // Leer modo guardado
  if (localStorage.getItem('modoOscuro') === 'true') {
    body.classList.add('modo-oscuro');
    switchOscuro.checked = true;
  }

  // Cambiar modo
  switchOscuro.addEventListener('change', () => {
    if (switchOscuro.checked) {
      body.classList.add('modo-oscuro');
      localStorage.setItem('modoOscuro', 'true');
    } else {
      body.classList.remove('modo-oscuro');
      localStorage.setItem('modoOscuro', 'false');
    }
  });
});
</script>
	
<script>
document.addEventListener("DOMContentLoaded", () => {
  const input = document.getElementById("inputLimite");
  const status = document.getElementById("statusLimite");
  const textoBase = "Canciones";
  let timer;

  if (!input) return;

  input.addEventListener("input", () => {
    clearTimeout(timer);
    status.textContent = "Guardando...";
    status.className = "estado-limite guardando";
    timer = setTimeout(() => guardarLimite(input.value), 800);
  });

  async function guardarLimite(valor) {
    if (!valor || isNaN(valor) || valor <= 0) {
      status.textContent = "Valor inválido";
      status.className = "estado-limite error";
      setTimeout(resetearEstado, 2000);
      return;
    }

    try {
      const res = await fetch("update_setting.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          setting_name: "limite_solicitudes",
          value: valor
        })
      });
      const data = await res.json();

      if (data.status === "ok") {
        status.textContent = "Guardado ✓";
        status.className = "estado-limite ok";
      } else {
        status.textContent = "Error";
        status.className = "estado-limite error";
      }
    } catch {
      status.textContent = "Error conexión";
      status.className = "estado-limite error";
    }

    setTimeout(resetearEstado, 2000);
  }

  function resetearEstado() {
    status.textContent = textoBase;
    status.className = "estado-limite base";
  }
});

// ================================
// CUPO GLOBAL
// ================================
document.addEventListener("DOMContentLoaded", () => {
  const inputCupo = document.getElementById("inputCupoGlobal");
  const statusCupo = document.getElementById("statusCupoGlobal");
  let timerCupo;

  if (!inputCupo) return;

  inputCupo.addEventListener("input", () => {
    clearTimeout(timerCupo);
    timerCupo = setTimeout(() => guardarCupoGlobal(inputCupo.value), 800);
  });

  async function guardarCupoGlobal(valor) {
    if (!valor || isNaN(valor) || valor <= 0) {
      statusCupo.textContent = "Valor inválido";
      statusCupo.className = "estado-limite error";
      setTimeout(() => { statusCupo.className = "estado-limite base"; }, 2000);
      return;
    }
    try {
      const res = await fetch("update_setting.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ setting_name: "cupo_global", value: valor })
      });
      const data = await res.json();
      if (data.status === "ok") {
        // Actualizar denominador visible
        document.querySelectorAll('#statusCupoGlobal').forEach(el => {
          const span = el.querySelector('#cupoActualDisplay');
          if (span) el.lastChild.textContent = '/' + valor;
        });
        statusCupo.className = "estado-limite ok";
      } else {
        statusCupo.className = "estado-limite error";
      }
    } catch {
      statusCupo.className = "estado-limite error";
    }
    setTimeout(() => { statusCupo.className = "estado-limite base"; }, 2000);
  }
});



	// COPIAR TEXTO
$('#tablaAdmin').on('click', '.copy-icon', function() {
    const texto = $(this).data('copy');

    navigator.clipboard.writeText(texto).then(() => {
        // efecto visual
        $(this).css('opacity', '1');
        setTimeout(() => $(this).css('opacity', '0.6'), 400);

        // mensaje opcional discreto
        Swal.fire({
            toast: true,
            position: 'top-end',
            timer: 1200,
            showConfirmButton: false,
            icon: 'success',
            title: 'Copiado'
        });
    });
});

	

	
	// ============================================
// CONTROL DE GÉNEROS BLOQUEADOS
// ============================================
$(document).ready(function() {
  
  // Actualizar contador de géneros bloqueados
  function actualizarContadorGeneros() {
    const bloqueados = $('.bloqueo-genero:checked').length;
    const contador = $('#contadorGenerosBloqueados');
    
    if (bloqueados === 0) {
      contador.hide();
    } else {
      contador.text(bloqueados).show();
    }
  }

  // Evento change en los checkboxes de géneros
  $('.bloqueo-genero').on('change', function() {
    actualizarContadorGeneros();
  });

  // Botón "Bloquear todos los géneros"
  $('#btnBloquearTodosGeneros').on('click', function(e) {
    e.preventDefault();
    $('.bloqueo-genero').prop('checked', true);
    actualizarContadorGeneros();
  });

  // Botón "Habilitar todos los géneros"
  $('#btnDesbloquearTodosGeneros').on('click', function(e) {
    e.preventDefault();
    $('.bloqueo-genero').prop('checked', false);
    actualizarContadorGeneros();
  });

  // Botón "Guardar cambios"
  $('#btnGuardarGeneros').on('click', function(e) {
    e.preventDefault();
    
    // Obtener géneros bloqueados (checked)
    const generosBloqueados = $('.bloqueo-genero:checked').map(function() {
      return $(this).val();
    }).get();

    // Convertir a JSON para enviar al servidor
    const generosJson = JSON.stringify(generosBloqueados);

    // Mostrar indicador de carga
    const btnOriginal = $(this).html();
    $(this).html('<i class="fas fa-spinner fa-spin"></i> Guardando...').prop('disabled', true);

    // Enviar al servidor
    $.ajax({
      url: 'update_generos.php',
      method: 'POST',
      data: {
        generos_bloqueados: generosJson
      },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'ok') {
          Swal.fire({
            icon: 'success',
            title: 'Géneros actualizados',
            text: res.message,
            timer: 2000,
            showConfirmButton: false
          });
          
          // Cerrar el dropdown
          $('#controlGenerosBtn').dropdown('hide');
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: res.message || 'No se pudieron actualizar los géneros'
          });
        }
      },
      error: function() {
        Swal.fire({
          icon: 'error',
          title: 'Error de conexión',
          text: 'No se pudo conectar con el servidor'
        });
      },
      complete: function() {
        // Restaurar botón
        $('#btnGuardarGeneros').html(btnOriginal).prop('disabled', false);
      }
    });
  });

  // Inicializar contador al cargar
  actualizarContadorGeneros();
});
	
</script>

</body>
</html>



