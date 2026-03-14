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
    <!-- Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
        <div class="container">
            <a href="index.php" class="navbar-brand d-flex align-items-center text-white text-decoration-none">
                <img src="images/tecba-logo.png" alt="TECBA" style="height: 40px; margin-right: 10px;">
                <h1 class="h4 mb-0">Biblioteca Virtual</h1>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link text-white" href="index.php">
                            <i class="fas fa-home me-1"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="categorias.php">
                            <i class="fas fa-list me-1"></i> Categorías
                        </a>
                    </li>
                </ul>
                
                <!-- Barra de búsqueda (oculta en perfil.php) -->
                <?php if (!str_contains($_SERVER['PHP_SELF'], 'perfil.php')): ?>
                <form class="d-flex me-3" method="GET" action="buscar.php">
                    <input class="form-control" type="search" name="q" placeholder="Buscar libros..." 
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <?php endif; ?>
                
                <!-- Usuario -->
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-2" style="font-size: 1.2rem;"></i>
                                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="perfil.php">
                                    <i class="fas fa-user me-2"></i>Mi Perfil
                                </a></li>
                                <li><a class="dropdown-item" href="favoritos.php">
                                    <i class="fas fa-heart me-2"></i>Mis Favoritos
                                </a></li>
                                <li><a class="dropdown-item" href="ai_assistant.php">
                                    <i class="fas fa-robot me-2"></i>Asistente IA
                                </a></li>
                                <?php if ($_SESSION['rol'] === 'Super Admin'): ?>
                                <li><a class="dropdown-item" href="gestion_usuarios.php">
                                    <i class="fas fa-users-cog me-2"></i>Gestión de Usuarios
                                </a></li>
                                <li><a class="dropdown-item" href="gestion_libros.php">
                                    <i class="fas fa-books me-2"></i>Gestión de Libros
                                </a></li>
                                <li><a class="dropdown-item" href="gestion_sugerencias.php">
                                    <i class="fas fa-comments me-2"></i>Gestión de Sugerencias
                                </a></li>
                                <li><a class="dropdown-item" href="subir_libro.php">
                                    <i class="fas fa-book-medical me-2"></i>Subir Libro
                                </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Iniciar Sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="registro.php">
                                <i class="fas fa-user-plus me-1"></i> Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
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
    
    <!-- Botón flotante del Asistente IA (solo para usuarios logueados y no en ai_assistant.php) -->
    <?php if (isset($_SESSION['usuario_id']) && basename($_SERVER['PHP_SELF']) != 'ai_assistant.php'): ?>
    <div class="ai-assistant-float" onclick="window.location.href='ai_assistant.php'" title="Asistente de IA">
        <img src="images/Logo IA.png" alt="Asistente IA" class="ai-float-logo">
        <span class="ai-float-text">IA</span>
    </div>
    
    <style>
    .ai-assistant-float {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
        border-radius: 50px;
        padding: 15px;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(255, 113, 0, 0.3);
        transition: all 0.3s ease;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        border: 2px solid rgba(255, 255, 255, 0.2);
    }
    
    .ai-assistant-float:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(255, 113, 0, 0.4);
        background: linear-gradient(135deg, rgb(255, 130, 0), rgb(220, 100, 0));
    }
    
    .ai-float-logo {
        width: 50px;
        height: 50px;
        object-fit: contain;
        filter: none;
    }
    
    .ai-float-text {
        color: white;
        font-weight: bold;
        font-size: 14px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .ai-assistant-float {
            bottom: 15px;
            right: 15px;
            padding: 12px;
            gap: 8px;
        }
        
        .ai-float-logo {
            width: 40px;
            height: 40px;
        }
        
        .ai-float-text {
            font-size: 12px;
        }
    }
    </style>
    <?php endif; ?>
