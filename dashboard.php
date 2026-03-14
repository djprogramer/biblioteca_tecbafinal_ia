<?php
session_start();
require_once 'includes/functions.php';

// Si no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    redirect('login.php');
}

$pageTitle = 'Panel Principal';
require_once 'includes/header.php';
?>

<style>
/* Fondo del dashboard con imagen y opacidad */
.dashboard-background {
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
.dashboard-content {
    position: relative;
    z-index: 1;
}

/* Animaciones sutiles para tarjetas */
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

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-5px);
    }
}

.card {
    animation: fadeInUp 0.6s ease-out;
    backdrop-filter: blur(5px);
    background: rgba(255, 255, 255, 0.95);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.2);
}

/* Animación para iconos */
.fa-book, .fa-folder, .fa-download {
    animation: float 3s ease-in-out infinite;
    animation-delay: calc(var(--i) * 0.2s);
}

.fa-book { --i: 0; }
.fa-folder { --i: 1; }
.fa-download { --i: 2; }

/* Efecto sutil en el texto */
.card-title {
    transition: color 0.3s ease;
}

.card:hover .card-title {
    color: rgb(255, 113, 0);
}

/* Animación para estadísticas */
@keyframes countUp {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

#total-libros, #total-categorias {
    animation: countUp 0.8s ease-out;
}

/* Estilos específicos para tarjetas de libros */
.book-card {
    transition: all 0.3s ease;
    overflow: hidden;
}

.book-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(255, 113, 0, 0.3);
}

.book-cover-container {
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
}

.book-cover {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.book-card:hover .book-cover {
    transform: scale(1.05);
}

.book-cover-placeholder {
    text-align: center;
    padding: 2rem;
}

.book-title {
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.2;
    min-height: 2.4rem;
    margin-bottom: 0.5rem;
}

.author-text {
    font-size: 0.8rem;
    min-height: 1.2rem;
    margin-bottom: 0.5rem;
}

.description-text {
    font-size: 0.75rem;
    line-height: 1.3;
    color: #6c757d;
    margin-bottom: 1rem;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Responsive para tarjetas de libros */
@media (max-width: 768px) {
    .book-cover-container {
        height: 150px;
    }
    
    .book-title {
        font-size: 0.85rem;
    }
    
    .description-text {
        font-size: 0.7rem;
        -webkit-line-clamp: 2;
    }
}

@media (max-width: 576px) {
    .book-cover-container {
        height: 120px;
    }
    
    .book-title {
        font-size: 0.8rem;
        min-height: 2rem;
    }
    
    .description-text {
        font-size: 0.65rem;
        -webkit-line-clamp: 2;
    }
}

.card::after {
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

.card:hover::after {
    opacity: 1;
}

/* Asegurar que los botones sean clickeables */
.btn {
    position: relative !important;
    z-index: 10 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
}

.btn:hover {
    transform: translateY(-2px) !important;
    box-shadow: 0 5px 15px rgba(255, 113, 0, 0.3) !important;
}

.btn-primary {
    background-color: #000000 !important;
    border-color: #000000 !important;
    background-image: none !important;
    color: #ffffff !important;
}

.btn-primary:hover {
    background-color: #333333 !important;
    border-color: #333333 !important;
    color: #ffffff !important;
}

.btn-info {
    background-color: #17a2b8 !important;
    border-color: #17a2b8 !important;
}

.btn-info:hover {
    background-color: #138496 !important;
    border-color: #138496 !important;
}

.btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}

.btn-success:hover {
    background-color: #218838 !important;
    border-color: #218838 !important;
}

.btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #c82333 !important;
}

.btn-outline-primary {
    color: rgb(255, 113, 0) !important;
    border-color: rgb(255, 113, 0) !important;
}

.btn-outline-primary:hover {
    background-color: rgb(255, 113, 0) !important;
    color: white !important;
}

/* Estilos para botones de favoritos */
.favorite-btn {
    transition: all 0.3s ease;
}

.favorite-btn:hover {
    transform: scale(1.1);
}

.favorite-btn.btn-danger {
    background-color: rgba(220, 53, 69, 0.8) !important;
    border-color: rgba(220, 53, 69, 0.8) !important;
}

.favorite-btn.btn-danger:hover {
    background-color: rgba(220, 53, 69, 1) !important;
    transform: scale(1.1);
}

.favorite-btn.btn-outline-danger {
    color: rgba(220, 53, 69, 0.8) !important;
    border-color: rgba(220, 53, 69, 0.8) !important;
}

.favorite-btn.btn-outline-danger:hover {
    background-color: rgba(220, 53, 69, 0.8) !important;
    color: white !important;
    transform: scale(1.1);
}

/* Animaciones para favoritos */
@keyframes heartBeat {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
}

.favorite-btn.btn-danger i {
    animation: heartBeat 1.5s ease-in-out infinite;
}

/* Notificaciones */
.position-fixed {
    position: fixed !important;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.alert-dismissible {
    padding-right: 3rem;
}

.btn-close {
    position: absolute;
    right: 10px;
    top: 5px;
    background: none;
    border: none;
    font-size: 1.5rem;
    opacity: 0.5;
    cursor: pointer;
}

.btn-close:hover {
    opacity: 1;
}

</style>

<div class="dashboard-background"></div>

<div class="dashboard-content">

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Panel Principal</li>
    </ol>
</nav>

<!-- Bienvenida -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 text-white" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="mb-1">
                            <i class="fas fa-user me-2"></i>
                            ¡Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?>!
                        </h2>
                        <p class="mb-0">Explora nuestro catálogo de recursos educativos y académicos</p>
                    </div>
                    <div class="col-md-4 text-center">
                        <img src="images/tecba-logo.png" alt="TECBA" style="height: 80px; opacity: 0.8;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estadísticas -->
<div class="row mb-5">
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-book fa-3x mb-3" style="color: rgb(255, 113, 0);"></i>
                <h3 class="card-title" id="total-libros">-</h3>
                <p class="card-text text-muted">Libros Disponibles</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-folder fa-3x mb-3" style="color: rgb(255, 113, 0);"></i>
                <h3 class="card-title" id="total-categorias">-</h3>
                <p class="card-text text-muted">Categorías</p>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-download fa-3x mb-3" style="color: rgb(255, 113, 0);"></i>
                <h3 class="card-title">24/7</h3>
                <p class="card-text text-muted">Acceso Online</p>
            </div>
        </div>
    </div>
</div>

<!-- Libros Recientes -->
<section id="libros-recientes" class="mb-5">
    <div class="row align-items-center mb-4">
        <div class="col">
            <h2 class="h3">
                <i class="fas fa-clock me-2 text-primary"></i>
                Libros Recientes
            </h2>
        </div>
        <div class="col-auto">
            <a href="buscar.php" class="btn btn-outline-primary">
                Ver Todos <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
    
    <div id="libros-recientes-container">
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando libros recientes...</p>
        </div>
    </div>
</section>

<!-- Acciones Rápidas -->
<section class="bg-light py-5">
    <div class="container">
        <h3 class="text-center mb-4">Acciones Rápidas</h3>
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-search fa-3x text-primary mb-3"></i>
                        <h5>Buscar Libros</h5>
                        <p class="text-muted small">Encuentra recursos por título o autor</p>
                        <a href="buscar.php" class="btn btn-primary btn-sm mt-2">
                            <i class="fas fa-search me-1"></i>Buscar
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-list fa-3x text-info mb-3"></i>
                        <h5>Explorar Categorías</h5>
                        <p class="text-muted small">Navega por áreas temáticas</p>
                        <a href="categorias.php" class="btn btn-info btn-sm mt-2">
                            <i class="fas fa-list me-1"></i>Explorar
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-heart fa-3x text-success mb-3"></i>
                        <h5>Mis Favoritos</h5>
                        <p class="text-muted small">Ver tus libros guardados</p>
                        <a href="favoritos.php" class="btn btn-success btn-sm mt-2">
                            <i class="fas fa-heart me-1"></i>Ver Favoritos
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card border-0 h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-sign-out-alt fa-3x text-danger mb-3"></i>
                        <h5>Cerrar Sesión</h5>
                        <p class="text-muted small">Salir del sistema</p>
                        <a href="logout.php" class="btn btn-danger btn-sm mt-2">
                            <i class="fas fa-sign-out-alt me-1"></i>Salir
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

</div> <!-- Cierre de dashboard-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas
    loadStatistics();
    
    // Cargar libros recientes
    loadRecentBooks();
    
    // Cargar favoritos guardados
    loadFavorites();
});

// Función para manejar favoritos con base de datos
function toggleFavorite(bookId, button) {
    // Verificar estado actual
    const isFavorite = button.classList.contains('btn-danger');
    
    if (isFavorite) {
        // Quitar de favoritos
        removeFromFavorites(bookId, button);
    } else {
        // Añadir a favoritos
        addToFavorites(bookId, button);
    }
}

// Función para añadir a favoritos
function addToFavorites(bookId, button) {
    fetch('api/favoritos.php?action=add_favorite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            libro_id: bookId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '<i class="fas fa-heart"></i>';
            button.classList.remove('btn-outline-danger');
            button.classList.add('btn-danger', 'favorite-btn');
            showNotification('Libro añadido a favoritos', 'success');
        } else {
            showNotification(data.message || 'Error al añadir a favoritos', 'danger');
        }
    })
    .catch(error => {
        console.error('Error añadiendo a favoritos:', error);
        showNotification('Error al añadir a favoritos', 'danger');
    });
}

// Función para quitar de favoritos
function removeFromFavorites(bookId, button) {
    fetch('api/favoritos.php?action=remove_favorite', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            libro_id: bookId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.innerHTML = '<i class="far fa-heart"></i>';
            button.classList.remove('btn-danger', 'favorite-btn');
            button.classList.add('btn-outline-danger');
            showNotification('Libro eliminado de favoritos', 'warning');
        } else {
            showNotification(data.message || 'Error al eliminar de favoritos', 'danger');
        }
    })
    .catch(error => {
        console.error('Error eliminando de favoritos:', error);
        showNotification('Error al eliminar de favoritos', 'danger');
    });
}

// Función para cargar estado de favoritos
function loadFavorites() {
    // Esta función ahora se maneja dinámicamente con loadFavoritesStatus()
    console.log('Sistema de favoritos inicializado');
}

// Función para mostrar notificaciones
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease-out;
    `;
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto eliminar después de 3 segundos
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function loadStatistics() {
    // Cargar total de libros
    fetch('api/libros.php?action=index&limit=1')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-libros').textContent = 
                data.pagination ? data.pagination.total_items.toLocaleString() : '0';
        })
        .catch(error => console.error('Error cargando libros:', error));
    
    // Cargar total de categorías
    fetch('api/categorias.php?action=index')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-categorias').textContent = 
                data.categorias ? data.categorias.length : '0';
        })
        .catch(error => console.error('Error cargando categorías:', error));
}

function loadRecentBooks() {
    const container = document.getElementById('libros-recientes-container');
    
    fetch('api/libros.php?action=recent')
        .then(response => response.json())
        .then(data => {
            if (data.libros && data.libros.length > 0) {
                container.innerHTML = generateBooksGrid(data.libros);
                // Cargar el estado de favoritos después de generar las tarjetas
                loadFavoritesStatus();
            } else {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No hay libros disponibles en este momento.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error cargando libros recientes:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los libros. Por favor, intenta nuevamente.
                </div>
            `;
        });
}

// Función para cargar el estado de favoritos de los libros visibles
function loadFavoritesStatus() {
    const favoriteButtons = document.querySelectorAll('.favorite-btn');
    
    if (favoriteButtons.length === 0) return;
    
    // Obtener todos los IDs de libros visibles
    const bookIds = Array.from(favoriteButtons).map(btn => btn.dataset.bookId);
    
    // Verificar cada libro individualmente
    bookIds.forEach(bookId => {
        fetch(`api/favoritos.php?action=check_favorite&libro_id=${bookId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.is_favorite) {
                    const button = document.querySelector(`.favorite-btn[data-book-id="${bookId}"]`);
                    if (button) {
                        button.innerHTML = '<i class="fas fa-heart"></i>';
                        button.classList.remove('btn-outline-danger');
                        button.classList.add('btn-danger');
                    }
                }
            })
            .catch(error => {
                console.error(`Error verificando favorito ${bookId}:`, error);
            });
    });
}

function generateBooksGrid(libros) {
    let html = '<div class="row">';
    
    libros.forEach(libro => {
        html += `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100 book-card">
                    <div class="book-cover-container">
                        ${libro.portada && libro.portada !== 'assets/images/default-book.jpg' 
                            ? `<img src="${libro.portada}" alt="${libro.titulo}" class="book-cover">`
                            : `<div class="book-cover-placeholder">
                                <i class="fas fa-book fa-3x text-muted"></i>
                               </div>`
                        }
                    </div>
                    <div class="card-body d-flex flex-column p-3">
                        <h6 class="card-title book-title text-truncate" title="${libro.titulo}">${libro.titulo}</h6>
                        <p class="card-text small text-muted mb-2 author-text text-truncate" title="${libro.autor}">${libro.autor}</p>
                        <p class="card-text small description-text flex-grow-1">${libro.descripcion ? libro.descripcion.substring(0, 80) + '...' : 'Sin descripción disponible'}</p>
                        <div class="d-flex gap-2 mt-auto">
                            <a href="${libro.link}" target="_blank" class="btn btn-primary btn-sm flex-fill">
                                <i class="fas fa-external-link-alt me-1"></i> Leer
                            </a>
                            <button class="btn btn-outline-danger btn-sm favorite-btn" data-book-id="${libro.id}" onclick="toggleFavorite(${libro.id}, this)" title="Añadir a favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}

function generateCategoriesGrid(categorias) {
    let html = '<div class="row">';
    
    categorias.slice(0, 8).forEach(categoria => {
        html += `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card category-card h-100 text-center">
                    <div class="card-body">
                        <div class="category-icon mb-3" style="color: ${categoria.color};">
                            <i class="${categoria.icon}"></i>
                        </div>
                        <h6 class="card-title">${categoria.nombre}</h6>
                        <p class="card-text small text-muted">
                            <i class="fas fa-book me-1"></i>
                            ${categoria.total_libros} libros
                        </p>
                        <a href="categoria_dashboard.php?id=${categoria.id}" class="btn btn-outline-primary btn-sm">
                            Explorar
                        </a>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    return html;
}
</script>
