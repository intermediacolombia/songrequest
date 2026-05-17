<?php require_once('../../inc/config.php');

// Definir el permiso requerido para esta página


// Verificar si el usuario tiene permisos para acceder a esta página
if (!isset($_SESSION["user_permissions"]) || !in_array($permisopage, $_SESSION["user_permissions"])) {
    // Si el usuario no tiene permisos, establecer mensaje de error y redirigir
    $_SESSION['error'] = "<center>No tiene permisos para ver esta página.<br> Permiso necesario:<strong> '$permisopage'</strong><center>";
    header("Location: $url/admin/"); // Cambiar a la URL del dashboard
    exit();
}

// Si llega aquí, el usuario tiene permisos y puede continuar
?>