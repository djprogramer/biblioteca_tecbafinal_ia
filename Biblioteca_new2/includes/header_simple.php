<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Biblioteca TECBA</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Personalizado con versión para evitar cache -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="Biblioteca digital TECBA - Acceso a recursos educativos y académicos">
    <meta name="keywords" content="biblioteca, tecba, educación, libros, recursos académicos">
    <meta name="author" content="TECBA">
    
    <!-- Forzar no cache -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    
    <style>
    .navbar-brand {
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8) !important;
        font-weight: bold !important;
        color: white !important;
    }
    .navbar-brand:hover {
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 1) !important;
        color: white !important;
    }
    </style>
</head>
<body>
