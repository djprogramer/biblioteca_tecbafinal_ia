<?php
/**
 * Funciones reutilizables para Biblioteca TECBA
 */

// Limpiar y sanitizar datos de entrada
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Formatear fecha
function formatDate($date) {
    if (!$date) return 'No especificado';
    return date('d/m/Y', strtotime($date));
}

// Limitar texto
function limitText($text, $limit = 100) {
    if (strlen($text) <= $limit) {
        return $text;
    }
    return substr($text, 0, $limit) . '...';
}

// Obtener URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    return "$protocol://$host$path";
}

// Redireccionar
function redirect($url) {
    error_log("Intentando redirigir a: $url");
    
    // Verificar si los headers ya fueron enviados
    if (headers_sent()) {
        error_log("Headers ya enviados, usando JavaScript redirect");
        echo "<script>window.location.href = '" . htmlspecialchars($url) . "';</script>";
        exit();
    } else {
        error_log("Headers disponibles, usando PHP redirect");
        header("Location: $url");
        error_log("Header enviado, ejecutando exit()");
        exit();
    }
}

// Mostrar mensaje de alerta
function showAlert($message, $type = 'info') {
    $alertClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    return "<div class='alert {$alertClass[$type]} alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Paginación
function getPagination($total, $page, $limit = 12) {
    $totalPages = ceil($total / $limit);
    $pagination = [];
    
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            $pagination[] = ['page' => $i, 'active' => true];
        } else {
            $pagination[] = ['page' => $i, 'active' => false];
        }
    }
    
    return $pagination;
}
?>
