<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Biblioteca TECBA - Invitado</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Meta tags para SEO -->
    <meta name="description" content="Biblioteca digital TECBA - Acceso como invitado">
    <meta name="keywords" content="biblioteca, tecba, educación, invitado">
    <meta name="author" content="TECBA">
</head>
<body>
    <!-- Navegación Invitado -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
        <div class="container">
            <a href="../index.php" class="navbar-brand d-flex align-items-center text-white text-decoration-none">
                <img src="../images/tecba-logo.png" alt="TECBA" style="height: 40px; margin-right: 10px;">
                <h1 class="h4 mb-0">Biblioteca Virtual</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="../index.php">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                </ul>
                
                <!-- Badge de Invitado -->
                <div class="navbar-nav">
                    <span class="navbar-text text-white">
                        <i class="fas fa-user-tag me-2"></i>
                        <span class="badge bg-light text-dark">Invitado</span>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Banner de Invitado -->
    <div class="bg-light py-3 border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h4 class="mb-0 text-muted">
                        <i class="fas fa-eye me-2" style="color: rgb(255, 113, 0);"></i>
                        Navegando como Invitado
                    </h4>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../login.php" class="btn btn-sm" style="background-color: rgb(255, 113, 0); color: white;">
                        <i class="fas fa-sign-in-alt me-1"></i>
                        Iniciar Sesión
                    </a>
                </div>
            </div>
        </div>
    </div>
