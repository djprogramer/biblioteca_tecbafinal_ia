<?php
session_start();
require_once 'includes/functions.php';

$libroId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$pageTitle = 'Detalle del Libro';

require_once 'includes/header.php';
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="categorias.php">Categorías</a></li>
            <li class="breadcrumb-item active" id="breadcrumb-libro">Cargando...</li>
        </ol>
    </nav>
    
    <!-- Contenido del libro -->
    <div id="libro-container">
        <div class="text-center py-5">
            <div class="loading mx-auto mb-3"></div>
            <p class="text-muted">Cargando información del libro...</p>
        </div>
    </div>
    
    <!-- Libros relacionados -->
    <div id="relacionados-container" class="mt-5" style="display: none;">
        <h3 class="h4 mb-4">
            <i class="fas fa-book me-2 text-primary"></i>
            Libros Relacionados
        </h3>
        <div id="relacionados-grid"></div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const libroId = <?php echo $libroId; ?>;
    
    if (libroId > 0) {
        loadLibro(libroId);
    } else {
        showError('ID de libro inválido');
    }
});

function loadLibro(libroId) {
    const container = document.getElementById('libro-container');
    
    fetch(`api/libros.php?action=show&id=${libroId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            
            if (data) {
                displayLibro(data);
                loadRelatedBooks(data);
            } else {
                showError('Libro no encontrado');
            }
        })
        .catch(error => {
            console.error('Error cargando libro:', error);
            showError('Error al cargar la información del libro');
        });
}

function displayLibro(libro) {
    const container = document.getElementById('libro-container');
    
    // Actualizar breadcrumb
    document.getElementById('breadcrumb-libro').textContent = libro.titulo;
    
    // Actualizar título de la página
    document.title = `${libro.titulo} - Biblioteca TECBA`;
    
    container.innerHTML = `
        <div class="row">
            <!-- Portada del libro -->
            <div class="col-lg-4 col-md-5 mb-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-img-top">
                        ${libro.portada && libro.portada !== 'assets/images/default-book.jpg' 
                            ? `<img src="${libro.portada}" alt="${libro.titulo}" class="img-fluid libro-cover">`
                            : `<div class="text-center py-5" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <i class="fas fa-book" style="font-size: 5rem;"></i>
                                <p class="mt-3 mb-0">Sin portada disponible</p>
                            </div>`
                        }
                    </div>
                </div>
            </div>
            
            <!-- Información del libro -->
            <div class="col-lg-8 col-md-7">
                <div class="book-info">
                    <h1 class="mb-3">${libro.titulo}</h1>
                    
                    <!-- Metadatos -->
                    <div class="book-meta mb-4">
                        <div class="row">
                            <div class="col-sm-6 mb-2">
                                <strong><i class="fas fa-user me-2"></i>Autor:</strong>
                                <span>${libro.autor}</span>
                            </div>
                            ${libro.anio ? `
                            <div class="col-sm-6 mb-2">
                                <strong><i class="fas fa-calendar me-2"></i>Año:</strong>
                                <span>${libro.anio}</span>
                            </div>
                            ` : ''}
                        </div>
                        ${libro.categorias && libro.categorias.length > 0 ? `
                        <div class="mt-2">
                            <strong><i class="fas fa-folder me-2"></i>Categorías:</strong>
                            <div class="mt-2">
                                ${libro.categorias.map(cat => `<span class="badge bg-primary me-2">${cat}</span>`).join('')}
                            </div>
                        </div>
                        ` : ''}
                    </div>
                    
                    <!-- Descripción -->
                    <div class="mb-4">
                        <h4><i class="fas fa-info-circle me-2 text-primary"></i>Descripción</h4>
                        <p class="lead">${libro.descripcion || 'No hay descripción disponible para este libro.'}</p>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="${libro.link}" target="_blank" class="btn btn-primary btn-lg">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Acceder al Libro
                        </a>
                        <button onclick="copyToClipboard('${libro.link}')" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-copy me-2"></i>
                            Copiar Enlace
                        </button>
                        <button onclick="window.history.back()" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>
                            Volver
                        </button>
                    </div>
                    
                    <!-- Información adicional -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Este libro está disponible a través de Google Drive. Al hacer clic en "Acceder al Libro", 
                            serás redirigido a la plataforma externa donde podrás ver o descargar el contenido.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function loadRelatedBooks(libro) {
    // Buscar libros relacionados por categorías o autor
    const container = document.getElementById('relacionados-container');
    const grid = document.getElementById('relacionados-grid');
    
    // Mostrar loading
    grid.innerHTML = `
        <div class="text-center py-3">
            <div class="loading mx-auto mb-2"></div>
            <p class="text-muted small">Cargando libros relacionados...</p>
        </div>
    `;
    
    // Buscar libros del mismo autor o categorías similares
    let searchQuery = libro.autor;
    if (libro.categorias && libro.categorias.length > 0) {
        searchQuery = libro.categorias[0]; // Usar la primera categoría
    }
    
    fetch(`api/buscar.php?q=${encodeURIComponent(searchQuery)}&limit=4`)
        .then(response => response.json())
        .then(data => {
            if (data.libros && data.libros.length > 0) {
                // Filtrar el libro actual
                const relacionados = data.libros.filter(l => l.id != libro.id).slice(0, 3);
                
                if (relacionados.length > 0) {
                    container.style.display = 'block';
                    grid.innerHTML = generateRelatedBooksGrid(relacionados);
                } else {
                    container.style.display = 'none';
                }
            } else {
                container.style.display = 'none';
            }
        })
        .catch(error => {
            console.error('Error cargando libros relacionados:', error);
            container.style.display = 'none';
        });
}

function generateRelatedBooksGrid(libros) {
    let html = '<div class="row">';
    
    libros.forEach(libro => {
        html += `
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-img-top">
                        ${libro.portada && libro.portada !== 'assets/images/default-book.jpg' 
                            ? `<img src="${libro.portada}" alt="${libro.titulo}" class="img-fluid" style="height: 200px; object-fit: cover;">`
                            : `<i class="fas fa-book" style="font-size: 2rem;"></i>`
                        }
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title">${libro.titulo}</h6>
                        <p class="card-text small text-muted mb-2">${libro.autor}</p>
                        <div class="mt-auto">
                            <a href="libro.php?id=${libro.id}" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-eye me-1"></i> Ver Detalles
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

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('¡Enlace copiado al portapapeles!', 'success');
    }).catch(() => {
        // Fallback para navegadores antiguos
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showToast('¡Enlace copiado al portapapeles!', 'success');
    });
}

function showError(message) {
    const container = document.getElementById('libro-container');
    container.innerHTML = `
        <div class="text-center py-5">
            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
            <h3>${message}</h3>
            <p class="text-muted mb-4">El libro que buscas no está disponible o ha sido eliminado.</p>
            <div>
                <a href="index.php" class="btn btn-primary me-3">
                    <i class="fas fa-home me-2"></i>Ir al Inicio
                </a>
                <a href="categorias.php" class="btn btn-outline-primary">
                    <i class="fas fa-list me-2"></i>Ver Categorías
                </a>
            </div>
        </div>
    `;
}
</script>

<style>
.libro-cover {
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.book-info h1 {
    color: #212529;
    font-weight: 700;
    line-height: 1.2;
}

.book-meta {
    background: #f8f9fa;
    border-left: 4px solid #0d6efd;
    padding: 1rem;
    border-radius: 0 8px 8px 0;
}

.book-meta strong {
    color: #495057;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5em 0.75em;
}

.btn-lg {
    border-radius: 8px;
    font-weight: 500;
}

.loading {
    width: 30px;
    height: 30px;
    border: 3px solid rgba(0,0,0,0.1);
    border-radius: 50%;
    border-top-color: #0d6efd;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>
