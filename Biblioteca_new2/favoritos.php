<?php
session_start();
require_once 'includes/functions.php';

// Si no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    redirect('login.php');
}

$pageTitle = 'Mis Favoritos';
require_once 'includes/header.php';
?>

<style>
/* Fondo de favoritos con imagen y opacidad */
.favoritos-background {
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
.favoritos-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con efecto glassmorphism */
.favorito-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.favorito-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.2);
    background: rgba(255, 255, 255, 0.95);
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

.favorito-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Estilos para botones */
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

.btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #c82333 !important;
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

/* Estilos para favoritos */
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

.favorite-btn.btn-danger i {
    animation: heartBeat 1.5s ease-in-out infinite;
}

@keyframes heartBeat {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.2);
    }
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

<div class="favoritos-background"></div>

<div class="favoritos-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Panel Principal</a></li>
            <li class="breadcrumb-item active">Mis Favoritos</li>
        </ol>
    </nav>
    
    <!-- Encabezado -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">
                                <i class="fas fa-heart me-2"></i>
                                Mis Libros Favoritos
                            </h2>
                            <p class="mb-0">Aquí encontrarás todos los libros que has marcado como favoritos</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <img src="images/tecba-logo.png" alt="TECBA" style="height: 80px; opacity: 0.8;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contenedor de favoritos -->
    <div id="favoritos-container">
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando tus libros favoritos...</p>
        </div>
    </div>
</div>

</div> <!-- Cierre de favoritos-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadFavorites();
});

// Función para cargar favoritos desde la base de datos
function loadFavorites() {
    fetch('api/favoritos.php?action=get_favorites')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.favorites && data.favorites.length > 0) {
                displayFavorites(data.favorites);
            } else {
                displayEmptyFavorites();
            }
        })
        .catch(error => {
            console.error('Error cargando favoritos:', error);
            displayEmptyFavorites();
        });
}

// Función para mostrar favoritos
function displayFavorites(favorites) {
    const container = document.getElementById('favoritos-container');
    let html = '<div class="row">';
    
    favorites.forEach(favorito => {
        html += `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100 favorito-card">
                    <div class="book-cover-container">
                        ${favorito.portada && favorito.portada !== 'assets/images/default-book.jpg' 
                            ? `<img src="${favorito.portada}" alt="${favorito.titulo}" class="book-cover">`
                            : `<div class="book-cover-placeholder">
                                <i class="fas fa-book fa-3x text-muted"></i>
                               </div>`
                        }
                    </div>
                    <div class="card-body d-flex flex-column p-3">
                        <h6 class="card-title book-title text-truncate" title="${favorito.titulo}">${favorito.titulo}</h6>
                        <p class="card-text small text-muted mb-2 author-text text-truncate" title="${favorito.autor}">${favorito.autor}</p>
                        <p class="card-text small description-text flex-grow-1">${favorito.descripcion ? favorito.descripcion.substring(0, 80) + '...' : 'Sin descripción disponible'}</p>
                        <div class="d-flex gap-2 mt-auto">
                            <a href="${favorito.link}" target="_blank" class="btn btn-primary btn-sm flex-fill">
                                <i class="fas fa-external-link-alt me-1"></i> Leer
                            </a>
                            <button class="btn btn-danger btn-sm favorite-btn" data-book-id="${favorito.id}" onclick="removeFavorite(${favorito.id}, this)" title="Quitar de favoritos">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Función para mostrar estado vacío
function displayEmptyFavorites() {
    const container = document.getElementById('favoritos-container');
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
            <h4 class="text-muted mb-3">No tienes libros favoritos</h4>
            <p class="text-muted mb-4">Aún no has añadido ningún libro a tus favoritos. Explora nuestro catálogo y añade tus libros preferidos.</p>
            <a href="dashboard.php" class="btn btn-primary btn-lg">
                <i class="fas fa-book me-2"></i>
                Explorar Libros
            </a>
        </div>
    `;
}

// Función para quitar de favoritos
function removeFavorite(bookId, button) {
    if (!confirm('¿Estás seguro de que quieres quitar este libro de tus favoritos?')) {
        return;
    }
    
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
            // Eliminar la tarjeta del DOM
            const card = button.closest('.col-lg-3');
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.8)';
            
            setTimeout(() => {
                card.remove();
                
                // Verificar si no quedan favoritos
                const remainingCards = document.querySelectorAll('.favorito-card');
                if (remainingCards.length === 0) {
                    displayEmptyFavorites();
                }
            }, 500);
            
            showNotification('Libro eliminado de favoritos', 'warning');
        } else {
            showNotification('Error al eliminar de favoritos', 'danger');
        }
    })
    .catch(error => {
        console.error('Error eliminando favorito:', error);
        showNotification('Error al eliminar de favoritos', 'danger');
    });
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
</script>
