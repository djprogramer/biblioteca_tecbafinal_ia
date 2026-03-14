// JavaScript principal para Biblioteca TECBA

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicializar popovers de Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Animación de entrada para las cards
    animateCards();
    
    // Búsqueda en tiempo real (opcional)
    initLiveSearch();
    
    // Lazy loading para imágenes
    initLazyLoading();
});

// Animar las cards cuando entran en viewport
function animateCards() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });

    document.querySelectorAll('.card').forEach(card => {
        observer.observe(card);
    });
}

// Búsqueda en tiempo real
function initLiveSearch() {
    const searchInput = document.querySelector('input[name="q"]');
    if (!searchInput) return;

    let searchTimeout;
    
    searchInput.addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length < 3) {
            clearSearchResults();
            return;
        }
        
        searchTimeout = setTimeout(() => {
            performLiveSearch(query);
        }, 300);
    });
}

// Realizar búsqueda en tiempo real
function performLiveSearch(query) {
    const url = `api/buscar.php?q=${encodeURIComponent(query)}&live=1`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Error en búsqueda:', error);
        });
}

// Mostrar resultados de búsqueda
function displaySearchResults(results) {
    const container = document.getElementById('search-results');
    if (!container) return;
    
    if (results.length === 0) {
        container.innerHTML = '<div class="alert alert-info">No se encontraron resultados</div>';
        return;
    }
    
    let html = '<div class="row">';
    results.forEach(book => {
        html += `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-img-top">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="card-body">
                        <h6 class="card-title">${book.titulo}</h6>
                        <p class="card-text small text-muted">${book.autor}</p>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Limpiar resultados de búsqueda
function clearSearchResults() {
    const container = document.getElementById('search-results');
    if (container) {
        container.innerHTML = '';
    }
}

// Lazy loading para imágenes
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Función para mostrar loading
function showLoading(element) {
    element.innerHTML = '<div class="text-center py-4"><div class="loading"></div> Cargando...</div>';
}

// Función para mostrar error
function showError(element, message) {
    element.innerHTML = `<div class="alert alert-danger">${message}</div>`;
}

// Función para copiar enlace al portapapeles
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('¡Enlace copiado al portapapeles!', 'success');
    }).catch(() => {
        showToast('Error al copiar el enlace', 'error');
    });
}

// Mostrar notificaciones tipo toast
function showToast(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'primary'} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    const toastElement = document.createElement('div');
    toastElement.innerHTML = toastHtml;
    toastContainer.appendChild(toastElement.firstElementChild);
    
    const toast = new bootstrap.Toast(toastContainer.lastElementChild);
    toast.show();
    
    // Eliminar el toast después de que se oculte
    toastContainer.lastElementChild.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Crear contenedor de toasts si no existe
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    document.body.appendChild(container);
    return container;
}

// Función para confirmar acciones
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Función para formatear fechas
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

// Función para validar formularios
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

// Manejo de errores global
window.addEventListener('error', function(e) {
    console.error('Error JavaScript:', e.error);
    // Aquí podrías enviar el error a un servicio de monitoreo
});

// Optimización: Debounce para eventos frecuentes
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Aplicar debounce a la búsqueda
const debouncedSearch = debounce(performLiveSearch, 300);
