<?php
session_start();
require_once 'includes/functions.php';

// Destruir todas las variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();

// Redirigir al inicio
redirect('index.php');
?>
