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
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="Biblioteca digital TECBA - Acceso a recursos educativos y académicos">
    <meta name="keywords" content="biblioteca, tecba, educación, libros, recursos académicos">
    <meta name="author" content="TECBA">
</head>
<body>
    <!-- Header con logo -->
    <nav class="navbar navbar-expand-lg" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
        <div class="container">
            <a href="../index.php" class="navbar-brand d-flex align-items-center text-white text-decoration-none">
                <img src="../images/tecba-logo.png" alt="TECBA" style="height: 40px; margin-right: 10px;" class="login-logo">
                <h1 class="h5 mb-0">Biblioteca Virtual</h1>
            </a>
        </div>
    </nav>
    
    <!-- Mensajes de alerta -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="container mt-3">
            <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Contenido principal -->
    <main class="container my-4">
