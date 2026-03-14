<?php
session_start();
require_once 'includes/functions.php';

$categoriaId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = 'Categoría';

require_once 'includes/header.php';
?>

<style>
/* Fondo de categoría con imagen y opacidad */
.categoria-background {
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
.categoria-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con efecto glassmorphism */
.categoria-detail-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.categoria-detail-card:hover {
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

.categoria-detail-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Efecto shimmer */
@keyframes shimmer {
    0% {
        background-position: -200px 0;
    }
    100% {
        background-position: calc(200px + 100%) 0;
    }
}

.categoria-detail-card::after {
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

.categoria-detail-card:hover::after {
    opacity: 1;
}
</style>

<div class="categoria-background"></div>

<div class="categoria-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="categorias.php">Categorías</a></li>
            <li class="breadcrumb-item active" id="categoria-nombre">Cargando...</li>
        </ol>
    </nav>
    
    <!-- Información de la categoría -->
    <div class="row mb-5" id="categoria-info">
        <div class="col-12 text-center">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando información de la categoría...</p>
        </div>
    </div>
    
    <!-- Libros de la categoría -->
    <div class="row">
        <div class="col-12">
            <h2 class="h4 mb-4">
                <i class="fas fa-book me-2 text-primary"></i>
                Libros en esta categoría
            </h2>
        </div>
    </div>
    
    <div id="libros-container">
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando libros...</p>
        </div>
    </div>
    
    <!-- Paginación -->
    <div id="pagination-container"></div>
</div>

</div> <!-- Cierre de categoria-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const categoriaId = <?php echo $categoriaId; ?>;
    
    if (categoriaId > 0) {
        loadCategoriaInfo(categoriaId);
        loadLibros(categoriaId, 1);
    } else {
        showError('ID de categoría inválido');
    }
});

function loadCategoriaInfo(categoriaId) {
    fetch(`api/categorias.php?action=show&id=${categoriaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.categoria) {
                const categoria = data.categoria;
                
                // Actualizar breadcrumb
                document.getElementById('categoria-nombre').textContent = categoria.nombre;
                
                // Actualizar información de la categoría
                document.getElementById('categoria-info').innerHTML = `
                    <div class="col-12">
                        <div class="card border-0 bg-primary text-white">
                            <div class="card-body text-center py-5">
                                <div class="category-icon mb-3" style="font-size: 4rem;">
                                    <i class="${categoria.icon}"></i>
                                </div>
                                <h1 class="display-5 fw-bold mb-3">${categoria.nombre}</h1>
                                <p class="lead mb-4">
                                    <i class="fas fa-book me-2"></i>
                                    ${categoria.total_libros} libro${categoria.total_libros !== 1 ? 's' : ''} disponibles
                                </p>
                                <div class="d-flex justify-content-center gap-3">
                                    <a href="#libros-container" class="btn btn-light btn-lg">
                                        <i class="fas fa-book-open me-2"></i>
                                        Ver Libros
                                    </a>
                                    <a href="categorias.php" class="btn btn-outline-light btn-lg">
                                        <i class="fas fa-arrow-left me-2"></i>
                                        Volver a Categorías
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                showError('Categoría no encontrada');
            }
        })
        .catch(error => {
            console.error('Error cargando categoría:', error);
            showError('Error al cargar la información de la categoría');
        });
}

function loadLibros(categoriaId, page) {
    const container = document.getElementById('libros-container');
    const paginationContainer = document.getElementById('pagination-container');
    
    // Mostrar loading
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando libros...</p>
        </div>
    `;
    
    fetch(`api/categorias.php?action=show&id=${categoriaId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.libros && data.libros.length > 0) {
                container.innerHTML = generateBooksGrid(data.libros);
                
                if (data.pagination.total_pages > 1) {
                    paginationContainer.innerHTML = generatePagination(data.pagination, categoriaId);
                } else {
                    paginationContainer.innerHTML = '';
                }
            } else {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-book fa-3x text-muted mb-3"></i>
                        <h4>No hay libros en esta categoría</h4>
                        <p class="text-muted">Esta categoría aún no tiene libros disponibles.</p>
                        <a href="categorias.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Explorar otras categorías
                        </a>
                    </div>
                `;
                paginationContainer.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error cargando libros:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los libros. Por favor, intenta nuevamente.
                </div>
            `;
        });
}

function generateBooksGrid(libros) {
    let html = '<div class="row">';
    
    libros.forEach(libro => {
        html += `
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="card h-100">
                    <div class="card-img-top">
                        ${libro.portada && libro.portada !== 'assets/images/default-book.jpg' 
                            ? `<img src="${libro.portada}" alt="${libro.titulo}" class="img-fluid" style="height: 280px; object-fit: cover;">`
                            : `<i class="fas fa-book" style="font-size: 3rem;"></i>`
                        }
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">${libro.titulo}</h6>
                        <p class="card-text small text-muted mb-2">
                            <i class="fas fa-user me-1"></i>${libro.autor}
                        </p>
                        ${libro.anio ? `<p class="card-text small text-muted mb-2">
                            <i class="fas fa-calendar me-1"></i>${libro.anio}
                        </p>` : ''}
                        <p class="card-text small">${libro.descripcion || 'Sin descripción disponible'}</p>
                        <div class="mt-auto">
                            <a href="${libro.link}" target="_blank" class="btn btn-primary btn-sm w-100">
                                <i class="fas fa-external-link-alt me-1"></i> Acceder al Libro
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

function generatePagination(pagination, categoriaId) {
    let html = '<nav aria-label="Paginación de libros"><ul class="pagination justify-content-center">';
    
    // Botón anterior
    if (pagination.current_page > 1) {
        html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadLibros(${categoriaId}, ${pagination.current_page - 1}); return false;">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            </li>
        `;
    }
    
    // Páginas
    for (let i = 1; i <= pagination.total_pages; i++) {
        if (i === pagination.current_page) {
            html += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
        } else {
            html += `
                <li class="page-item">
                    <a class="page-link" href="#" onclick="loadLibros(${categoriaId}, ${i}); return false;">${i}</a>
                </li>
            `;
        }
    }
    
    // Botón siguiente
    if (pagination.current_page < pagination.total_pages) {
        html += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadLibros(${categoriaId}, ${pagination.current_page + 1}); return false;">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    html += '</ul></nav>';
    return html;
}

function showError(message) {
    document.getElementById('categoria-info').innerHTML = `
        <div class="col-12">
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${message}
            </div>
        </div>
    `;
    
    document.getElementById('libros-container').innerHTML = '';
    document.getElementById('pagination-container').innerHTML = '';
}
</script>
