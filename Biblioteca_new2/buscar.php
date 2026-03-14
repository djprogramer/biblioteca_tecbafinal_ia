<?php
session_start();
require_once 'includes/functions.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageTitle = $query ? 'Resultados de búsqueda' : 'Búsqueda';

require_once 'includes/header.php';
?>

<style>
/* Fondo de búsqueda con imagen y opacidad */
.buscar-background {
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
.buscar-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con efecto glassmorphism */
.search-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.search-card:hover {
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

.search-card {
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

.search-card::after {
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

.search-card:hover::after {
    opacity: 1;
}
</style>

<div class="buscar-background"></div>

<div class="buscar-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item active">Búsqueda</li>
        </ol>
    </nav>
    
    <!-- Formulario de búsqueda -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-4">
                        <i class="fas fa-search me-2 text-primary"></i>
                        Buscar Libros
                    </h1>
                    <form method="GET" action="buscar.php" class="search-form">
                        <div class="input-group input-group-lg">
                            <input type="text" class="form-control" name="q" 
                                   placeholder="Buscar por título, autor o descripción..." 
                                   value="<?php echo htmlspecialchars($query); ?>" required>
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search me-2"></i>Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($query): ?>
        <!-- Resultados de búsqueda -->
        <div class="row">
            <div class="col-12">
                <h2 class="h4 mb-4">
                    <i class="fas fa-book me-2 text-primary"></i>
                    Resultados para: "<span class="text-primary"><?php echo htmlspecialchars($query); ?></span>"
                </h2>
            </div>
        </div>
        
        <div id="resultados-container">
            <div class="text-center py-5">
                <div class="loading mx-auto mb-3"></div>
                <p class="text-muted">Buscando libros...</p>
            </div>
        </div>
        
        <div id="pagination-container"></div>
        
        <!-- Sugerencias -->
        <div id="sugerencias-container" class="mt-5"></div>
    <?php else: ?>
        <!-- Mensaje inicial -->
        <div class="row">
            <div class="col-12">
                <div class="text-center py-5">
                    <i class="fas fa-search fa-4x text-muted mb-4"></i>
                    <h3 class="mb-3">¿Qué estás buscando?</h3>
                    <p class="text-muted mb-4">
                        Usa el buscador para encontrar libros por título, autor o palabras clave en la descripción.
                    </p>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body text-center">
                                    <i class="fas fa-lightbulb fa-3x text-warning mb-3"></i>
                                    <h6>Consejos de búsqueda</h6>
                                    <p class="small text-muted">Usa términos específicos como "administración", "marketing" o "contabilidad"</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body text-center">
                                    <i class="fas fa-user fa-3x text-info mb-3"></i>
                                    <h6>Buscar por autor</h6>
                                    <p class="small text-muted">Escribe el nombre del autor como "Chiavenato" o "Kotler"</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm mb-3">
                                <div class="card-body text-center">
                                    <i class="fas fa-folder fa-3x text-success mb-3"></i>
                                    <h6>Explorar categorías</h6>
                                    <p class="small text-muted">Navega por categorías temáticas para descubrir nuevos recursos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="categorias.php" class="btn btn-outline-primary btn-lg me-3">
                            <i class="fas fa-list me-2"></i>Ver Categorías
                        </a>
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i>Ir al Inicio
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</div> <!-- Cierre de buscar-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const query = '<?php echo addslashes($query); ?>';
    const page = <?php echo $page; ?>;
    
    if (query) {
        performSearch(query, page);
    }
    
    // Auto-búsqueda en tiempo real (opcional)
    initLiveSearch();
});

function performSearch(query, page) {
    const container = document.getElementById('resultados-container');
    const paginationContainer = document.getElementById('pagination-container');
    const sugerenciasContainer = document.getElementById('sugerencias-container');
    
    // Mostrar loading
    container.innerHTML = `
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Buscando libros...</p>
        </div>
    `;
    
    fetch(`api/buscar.php?q=${encodeURIComponent(query)}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data.libros && data.libros.length > 0) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Se encontraron <strong>${data.pagination.total_items}</strong> resultado${data.pagination.total_items !== 1 ? 's' : ''}
                        ${data.pagination.total_pages > 1 ? `(página ${data.pagination.current_page} de ${data.pagination.total_pages})` : ''}
                    </div>
                `;
                container.innerHTML += generateBooksGrid(data.libros);
                
                // Paginación
                if (data.pagination.total_pages > 1) {
                    paginationContainer.innerHTML = generatePagination(data.pagination, query);
                } else {
                    paginationContainer.innerHTML = '';
                }
                
                // Sugerencias
                if (data.suggestions && data.suggestions.length > 0) {
                    sugerenciasContainer.innerHTML = generateSuggestions(data.suggestions);
                } else {
                    sugerenciasContainer.innerHTML = '';
                }
            } else {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <h4>No se encontraron resultados</h4>
                        <p class="text-muted mb-4">
                            No hay libros que coincidan con "<strong>${query}</strong>"
                        </p>
                        <div class="mb-4">
                            <p class="text-muted">Sugerencias:</p>
                            <ul class="list-unstyled">
                                <li>• Verifica la ortografía de las palabras</li>
                                <li>• Usa términos más generales</li>
                                <li>• Intenta con diferentes palabras clave</li>
                                <li>• Explora nuestras categorías</li>
                            </ul>
                        </div>
                        <div>
                            <a href="categorias.php" class="btn btn-outline-primary me-3">
                                <i class="fas fa-list me-2"></i>Ver Categorías
                            </a>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Ir al Inicio
                            </a>
                        </div>
                    </div>
                `;
                paginationContainer.innerHTML = '';
                sugerenciasContainer.innerHTML = '';
            }
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al realizar la búsqueda. Por favor, intenta nuevamente.
                </div>
            `;
            paginationContainer.innerHTML = '';
            sugerenciasContainer.innerHTML = '';
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

function generatePagination(pagination, query) {
    let html = '<nav aria-label="Paginación de resultados"><ul class="pagination justify-content-center">';
    
    // Botón anterior
    if (pagination.current_page > 1) {
        html += `
            <li class="page-item">
                <a class="page-link" href="?q=${encodeURIComponent(query)}&page=${pagination.current_page - 1}">
                    <i class="fas fa-chevron-left"></i> Anterior
                </a>
            </li>
        `;
    }
    
    // Páginas
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="?q=${encodeURIComponent(query)}&page=1">1</a></li>`;
        if (startPage > 2) {
            html += `<li class="page-item disabled"><a class="page-link">...</a></li>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            html += `<li class="page-item active"><a class="page-link" href="#">${i}</a></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="?q=${encodeURIComponent(query)}&page=${i}">${i}</a></li>`;
        }
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += `<li class="page-item disabled"><a class="page-link">...</a></li>`;
        }
        html += `<li class="page-item"><a class="page-link" href="?q=${encodeURIComponent(query)}&page=${pagination.total_pages}">${pagination.total_pages}</a></li>`;
    }
    
    // Botón siguiente
    if (pagination.current_page < pagination.total_pages) {
        html += `
            <li class="page-item">
                <a class="page-link" href="?q=${encodeURIComponent(query)}&page=${pagination.current_page + 1}">
                    Siguiente <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;
    }
    
    html += '</ul></nav>';
    return html;
}

function generateSuggestions(suggestions) {
    let html = `
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="card-title">
                    <i class="fas fa-lightbulb me-2 text-warning"></i>
                    Sugerencias de búsqueda
                </h6>
                <div class="d-flex flex-wrap gap-2">
    `;
    
    suggestions.forEach(suggestion => {
        html += `
            <a href="?q=${encodeURIComponent(suggestion)}" class="badge bg-light text-dark text-decoration-none p-2">
                ${suggestion}
            </a>
        `;
    });
    
    html += `
                </div>
            </div>
        </div>
    `;
    
    return html;
}

function initLiveSearch() {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 3) {
            return;
        }
        
        searchTimeout = setTimeout(() => {
            // Aquí podrías implementar sugerencias en tiempo real
            console.log('Búsqueda en tiempo real:', query);
        }, 300);
    });
}
</script>
