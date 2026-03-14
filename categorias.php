<?php
session_start();
require_once 'includes/functions.php';

$pageTitle = 'Categorías';
require_once 'includes/header.php';
?>

<style>
/* Fondo de categorías con imagen y opacidad */
.categorias-background {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: url('images/wallpaperbetter3.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    opacity: 0.25;
    z-index: -1000;
    pointer-events: none;
}

/* Contenido principal */
.categorias-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas de categorías con efecto glassmorphism */
.category-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
    position: relative;
    z-index: 10;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(255, 113, 0, 0.3);
    background: rgba(255, 255, 255, 0.95);
}

/* Asegurar que los botones dentro de tarjetas funcionen */
.category-card .btn {
    position: relative !important;
    z-index: 20 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
}

.category-card .btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 5px 15px rgba(255, 113, 0, 0.4) !important;
}

.category-card .btn-outline-primary {
    color: rgb(255, 113, 0) !important;
    border-color: rgb(255, 113, 0) !important;
    background-color: transparent !important;
}

.category-card .btn-outline-primary:hover {
    background-color: rgb(255, 113, 0) !important;
    color: white !important;
}

/* Estilos para botones primarios negros */
.category-card .btn-primary {
    background-color: #000000 !important;
    border-color: #000000 !important;
    background-image: none !important;
    color: #ffffff !important;
}

.category-card .btn-primary:hover {
    background-color: #333333 !important;
    border-color: #333333 !important;
    color: #ffffff !important;
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.category-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Efecto shimmer para categorías */
@keyframes shimmer {
    0% {
        background-position: -200px 0;
    }
    100% {
        background-position: calc(200px + 100%) 0;
    }
}

.category-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        105deg,
        transparent 40%,
        rgba(255, 255, 255, 0.3) 50%,
        transparent 60%
    );
    background-size: 200px 100%;
    background-repeat: no-repeat;
    animation: shimmer 3s infinite;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.category-card:hover::after {
    opacity: 1;
}
</style>

<div class="categorias-background"></div>

<div class="categorias-content">

<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4">
                <i class="fas fa-list me-2 text-primary"></i>
                Categorías de Libros
            </h1>
            <p class="lead text-muted mb-5">
                Explora nuestra colección organizada por áreas temáticas. 
                Cada categoría contiene recursos especializados para tu formación.
            </p>
        </div>
    </div>
    
    <div id="categorias-container">
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando categorías...</p>
        </div>
    </div>
</div>

</div> <!-- Cierre de categorias-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadCategories();
});

function loadCategories() {
    const container = document.getElementById('categorias-container');
    
    fetch('api/categorias.php?action=index')
        .then(response => response.json())
        .then(data => {
            if (data.categorias && data.categorias.length > 0) {
                container.innerHTML = generateCategoriesList(data.categorias);
            } else {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-folder fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay categorías disponibles en este momento.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando categorías:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar las categorías. Por favor, intenta nuevamente.
                </div>
            `;
        });
}

function generateCategoriesList(categorias) {
    let html = '<div class="row">';
    
    categorias.forEach(categoria => {
        html += `
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card category-card h-100 border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="category-icon mb-3" style="color: ${categoria.color};">
                            <i class="${categoria.icon}"></i>
                        </div>
                        <h5 class="card-title">${categoria.nombre}</h5>
                        <p class="card-text text-muted">
                            <i class="fas fa-book me-1"></i>
                            ${categoria.total_libros} libro${categoria.total_libros !== 1 ? 's' : ''}
                        </p>
                        <div class="d-grid">
                            <a href="categoria.php?id=${categoria.id}" class="btn btn-primary">
                                <i class="fas fa-arrow-right me-1"></i>
                                Explorar Categoría
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}
</script>
