<?php
session_start();
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    redirect('dashboard.php');
}

$pageTitle = 'Bienvenido';
require_once 'includes/header_simple.php';
?>

<!-- Hero Section -->
<div class="hero-section text-white py-5" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4 d-flex align-items-center" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);">
                    <img src="images/tecba-logo.png" alt="TECBA" style="height: 60px; margin-right: 15px;">
                    Biblioteca TECBA
                </h1>
                <p class="lead mb-4">
                    Accede a nuestra colección digital de recursos educativos y académicos. 
                    Descubre libros, proyectos y materiales de estudio para tu formación profesional.
                </p>
                <div class="d-flex gap-3">
                    <a href="login.php" class="btn btn-light btn-lg">
                        <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                    </a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-graduation-cap" style="font-size: 12rem; opacity: 0.8; color: white; text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);"></i>
            </div>
        </div>
    </div>
</div>

<!-- Features Section -->
<section class="py-5 features-section">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="text-center feature-card">
                    <i class="fas fa-book fa-3x" style="color: rgb(255, 113, 0);"></i>
                    <h4>Catálogo Digital</h4>
                    <p class="text-muted">Acceso a cientos de libros académicos y recursos educativos</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center feature-card">
                    <i class="fas fa-search fa-3x" style="color: rgb(255, 113, 0);"></i>
                    <h4>Búsqueda Avanzada</h4>
                    <p class="text-muted">Encuentra rápidamente los recursos que necesitas</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="text-center feature-card">
                    <i class="fas fa-users fa-3x" style="color: rgb(255, 113, 0);"></i>
                    <h4>Acceso 24/7</h4>
                    <p class="text-muted">Disponible cuando lo necesites, desde cualquier lugar</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-5 text-center cta-section">
    <div class="container">
        <h3 class="mb-4">¿Listo para comenzar?</h3>
        <p class="lead text-muted mb-4">
            Inicia sesión para acceder al catálogo completo de recursos educativos
        </p>
        <a href="login.php" class="btn btn-lg" style="background-color: rgb(255, 113, 0); border-color: rgb(255, 113, 0);">
            <i class="fas fa-rocket me-2"></i>
            Comenzar Ahora
        </a>
    </div>
</section>

<?php require_once 'includes/footer_simple.php'; ?>

<!-- Forzar recarga de estilos -->
<script>
    // Forzar recarga de CSS
    document.addEventListener('DOMContentLoaded', function() {
        // Agregar timestamp a todos los enlaces CSS para evitar cache
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => {
            if (link.href.includes('style.css')) {
                link.href = link.href.split('?')[0] + '?v=' + Date.now();
            }
        });
    });
</script>
