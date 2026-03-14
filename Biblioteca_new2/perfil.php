<?php
session_start();
require_once 'includes/functions.php';

// Si no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    redirect('login.php');
}

$pageTitle = 'Mi Perfil';
require_once 'includes/header.php';
?>

<style>
/* Fondo de perfil con imagen y opacidad */
.perfil-background {
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
.perfil-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con efecto glassmorphism */
.perfil-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.perfil-card:hover {
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

.perfil-card {
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

.btn-danger {
    background-color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-danger:hover {
    background-color: #c82333 !important;
    border-color: #c82333 !important;
}

.btn-success {
    background-color: #28a745 !important;
    border-color: #28a745 !important;
}

.btn-success:hover {
    background-color: #218838 !important;
    border-color: #218838 !important;
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

/* Estilos para badges de rol */
.rol-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
    border-radius: 50px;
    font-weight: 600;
}

.rol-admin {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.rol-usuario {
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
}

.rol-empleado {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
}

/* Estilos para formulario de contraseña */
.form-control:focus {
    border-color: rgb(255, 113, 0) !important;
    box-shadow: 0 0 0 0.2rem rgba(255, 113, 0, 0.25) !important;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:hover {
    border-color: rgb(255, 113, 0);
}

/* Estilos para avatar */
.avatar-container {
    position: relative;
    display: inline-block;
}

.avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid rgb(255, 113, 0);
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: rgb(255, 113, 0);
    font-weight: bold;
}

.avatar-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: #28a745;
    border: 3px solid white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    color: white;
}

/* Estilos para sugerencias */
.sugerencia-card {
    transition: all 0.3s ease;
    border-left: 4px solid rgb(255, 113, 0);
}

.sugerencia-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(255, 113, 0, 0.15);
}

.sugerencia-card .card-title {
    color: rgb(255, 113, 0);
    font-weight: 600;
    font-size: 0.9rem;
}

.sugerencia-card .card-text {
    line-height: 1.4;
}

/* Estados de sugerencias */
.bg-warning {
    background-color: #ffc107 !important;
}

.bg-info {
    background-color: #17a2b8 !important;
}

.bg-success {
    background-color: #28a745 !important;
}

.bg-danger {
    background-color: #dc3545 !important;
}

/* Animaciones */
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

.position-fixed {
    position: fixed !important;
    z-index: 9999 !important;
}

/* Estilos para corregir problemas del modal */
.modal {
    z-index: 9999 !important;
}

.modal-backdrop {
    z-index: 9998 !important;
    background-color: rgba(0, 0, 0, 0.3) !important;
    pointer-events: none !important;
}

.modal-dialog {
    z-index: 10000 !important;
    pointer-events: auto !important;
}

.modal-content {
    z-index: 10001 !important;
    background: white !important;
    border-radius: 15px !important;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3) !important;
    pointer-events: auto !important;
}

.modal-header {
    border-radius: 15px 15px 0 0 !important;
    pointer-events: auto !important;
}

.modal-body {
    background: white !important;
    color: #333 !important;
    pointer-events: auto !important;
}

.modal-footer {
    border-radius: 0 0 15px 15px !important;
    background: white !important;
    pointer-events: auto !important;
}

/* Forzar visibilidad del contenido del modal */
.modal * {
    color: inherit !important;
}

.modal .text-muted {
    color: #6c757d !important;
}

.modal .alert {
    color: #333 !important;
}

.modal button,
.modal input,
.modal textarea,
.modal select,
.modal a {
    pointer-events: auto !important;
}

/* Evitar que el backdrop bloquee el modal */
.modal.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.modal-body {
    z-index: 1052 !important;
    pointer-events: auto !important;
}

.modal-body input,
.modal-body textarea,
.modal-body select,
.modal-body button {
    pointer-events: auto !important;
    z-index: 1053 !important;
}

/* Asegurar que el textarea sea editable */
#comentario {
    pointer-events: auto !important;
    user-select: text !important;
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
}

/* Corregir problemas de foco */
.modal.show .modal-content {
    pointer-events: auto !important;
}

.modal.show .modal-body {
    pointer-events: auto !important;
}
</style>

<div class="perfil-background"></div>

<div class="perfil-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item active">Mi Perfil</li>
        </ol>
    </nav>
    
    <!-- Información del perfil -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white perfil-card" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <div class="avatar-container">
                                <div class="avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="avatar-badge">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h2 class="mb-2">
                                <?php echo htmlspecialchars($_SESSION['nombre']); ?>
                            </h2>
                            <p class="mb-2">
                                <i class="fas fa-envelope me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['email']); ?>
                            </p>
                            <div class="mb-2">
                                <span class="rol-badge <?php echo 'rol-' . ($_SESSION['rol'] ?? 'usuario'); ?>">
                                    <?php 
                                    switch($_SESSION['rol'] ?? 'usuario') {
                                        case 'admin':
                                            echo '<i class="fas fa-crown me-1"></i>Administrador';
                                            break;
                                        case 'empleado':
                                            echo '<i class="fas fa-user-tie me-1"></i>Empleado';
                                            break;
                                        default:
                                            echo '<i class="fas fa-user me-1"></i>Usuario';
                                            break;
                                    }
                                    ?>
                                </span>
                            </div>
                            <p class="mb-0">
                                <small>
                                    <i class="fas fa-calendar me-1"></i>
                                    Miembro desde: <?php echo date('d/m/Y', strtotime($_SESSION['fecha_registro'] ?? 'now')); ?>
                                </small>
                            </p>
                        </div>
                        <div class="col-md-3 text-center">
                            <img src="images/tecba-logo.png" alt="TECBA" style="height: 80px; opacity: 0.8;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sección de acciones -->
    <div class="row">
        <!-- Cambiar contraseña -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 h-100 perfil-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-key me-2 text-warning"></i>
                        Cambiar Contraseña
                    </h5>
                    <form id="cambiarPasswordForm">
                        <div class="mb-3">
                            <label for="password_actual" class="form-label">Contraseña Actual</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password_nueva" class="form-label">Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password_nueva" name="password_nueva" required minlength="6">
                            </div>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        <div class="mb-3">
                            <label for="password_confirmar" class="form-label">Confirmar Nueva Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="password_confirmar" name="password_confirmar" required minlength="6">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-2"></i>Cambiar Contraseña
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="col-md-6 mb-4">
            <div class="card border-0 h-100 perfil-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-chart-bar me-2 text-info"></i>
                        Mis Estadísticas
                    </h5>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <i class="fas fa-heart fa-2x text-danger mb-2"></i>
                                <h4 id="total-favoritos">-</h4>
                                <small class="text-muted">Favoritos</small>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="p-3 border rounded">
                                <i class="fas fa-book fa-2x text-primary mb-2"></i>
                                <h4 id="total-accesos">-</h4>
                                <small class="text-muted">Libros Accedidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <a href="favoritos.php" class="btn btn-success w-100">
                            <i class="fas fa-heart me-2"></i>Ver Mis Favoritos
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sección de Sugerencias -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 perfil-card">
                <div class="card-body">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-8">
                            <h4 class="mb-0">
                                <i class="fas fa-lightbulb me-2"></i>
                                Sugerencias y Solicitudes de Subida
                            </h4>
                            <p class="mb-0 text-muted">
                                Envía tus sugerencias y solicita que se suban nuevos libros a la biblioteca
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sugerenciaModal">
                                    <i class="fas fa-lightbulb me-2"></i>Nueva Sugerencia
                                </button>
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#solicitudLibroModal">
                                    <i class="fas fa-upload me-2"></i>Solicitar Subida
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tabs para diferenciar sugerencias y solicitudes -->
                    <ul class="nav nav-tabs mb-3" id="solicitudesTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="sugerencias-tab" data-bs-toggle="tab" data-bs-target="#sugerencias-tab-pane" type="button" role="tab">
                                <i class="fas fa-lightbulb me-2"></i>Mis Sugerencias
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="solicitudes-tab" data-bs-toggle="tab" data-bs-target="#solicitudes-tab-pane" type="button" role="tab">
                                <i class="fas fa-upload me-2"></i>Solicitudes de Subida
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content" id="solicitudesTabContent">
                        <!-- Tab de Sugerencias -->
                        <div class="tab-pane fade show active" id="sugerencias-tab-pane" role="tabpanel">
                            <div id="sugerenciasContainer">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">Cargando tus sugerencias...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab de Solicitudes de Subida -->
                        <div class="tab-pane fade" id="solicitudes-tab-pane" role="tabpanel">
                            <div id="solicitudesContainer">
                                <div class="text-center py-4">
                                    <i class="fas fa-spinner fa-spin fa-2x text-muted mb-3"></i>
                                    <p class="text-muted">Cargando tus solicitudes de subida...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva Sugerencia -->
<div class="modal fade" id="sugerenciaModal" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: white !important; z-index: 99999 !important; pointer-events: auto !important;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0)); color: white; pointer-events: auto !important;">
                <h5 class="modal-title">
                    <i class="fas fa-lightbulb me-2"></i>
                    Nueva Sugerencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background: white !important; pointer-events: auto !important; z-index: 99998 !important;">
                <form id="sugerenciaForm" style="pointer-events: auto !important;">
                    <div class="mb-3">
                        <label for="comentario" class="form-label">
                            <i class="fas fa-comment me-1"></i>
                            Tu Sugerencia
                        </label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="5" 
                                  placeholder="Describe tu sugerencia para mejorar la biblioteca..." required
                                  style="background: white !important; color: black !important; pointer-events: auto !important; user-select: text !important; -webkit-user-select: text !important; -moz-user-select: text !important; z-index: 99999 !important;"></textarea>
                        <div class="form-text">
                            <i class="fas fa-info-circle me-1"></i>
                            Las sugerencias son revisadas por los administradores y recibirás una respuesta pronto.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0" style="background: white !important; pointer-events: auto !important;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="pointer-events: auto !important;">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="submit" form="sugerenciaForm" class="btn btn-primary" style="pointer-events: auto !important;">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Sugerencia
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Solicitud de Subida de Libro -->
<div class="modal fade" id="solicitudLibroModal" tabindex="-1" data-bs-backdrop="false" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: white !important; z-index: 99999 !important; pointer-events: auto !important;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(40, 167, 69), rgb(34, 139, 34)); color: white; pointer-events: auto !important;">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>
                    Solicitar Subida de Libro
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="background: white !important; pointer-events: auto !important; z-index: 99998 !important;">
                <form id="solicitudLibroForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="tipo_solicitud" class="form-label">
                                    <i class="fas fa-list me-1"></i>
                                    Tipo de Solicitud
                                </label>
                                <select class="form-select" id="tipo_solicitud" name="tipo_solicitud" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="sugerencia_compra">Sugerencia de Compra</option>
                                    <option value="donacion">Donación</option>
                                    <option value="digitalizacion">Digitalización</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="libro_id" class="form-label">
                                    <i class="fas fa-book-open me-1"></i>
                                    Libro Existente (si aplica)
                                </label>
                                <select class="form-select" id="libro_id" name="libro_id">
                                    <option value="">Seleccionar libro existente (opcional)...</option>
                                    <!-- Se cargarán dinámicamente -->
                                </select>
                                <small class="text-muted">Opcional: si es una versión o edición de un libro existente</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="titulo_solicitado" class="form-label">
                            <i class="fas fa-heading me-1"></i>
                            Título del Libro a Subir
                        </label>
                        <input type="text" class="form-control" id="titulo_solicitado" name="titulo_solicitado" 
                               placeholder="Escribe el título del libro que quieres que se suba" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-align-left me-1"></i>
                            Descripción y Justificación
                        </label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                  placeholder="Describe por qué este libro debería subirse a la biblioteca (autor, año, importancia, etc.)"></textarea>
                        <small class="text-muted">Proporciona información detallada para ayudar a evaluar la solicitud</small>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Información importante:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Las solicitudes serán evaluadas por el administrador</li>
                            <li>Recibirás una respuesta sobre la viabilidad de la subida</li>
                            <li>El tiempo de respuesta depende del tipo de solicitud</li>
                            <li>Proporciona la mayor información posible para agilizar el proceso</li>
                        </ul>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0" style="background: white !important; pointer-events: auto !important;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="pointer-events: auto !important;">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="submit" form="solicitudLibroForm" class="btn btn-success" style="pointer-events: auto !important;">
                    <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Solicitud -->
<div class="modal fade" id="detalleSolicitudModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(40, 167, 69), rgb(34, 139, 34)); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>
                    Detalle de Solicitud de Subida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleSolicitudContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle Sugerencia -->
<div class="modal fade" id="detalleSugerenciaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0)); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-ticket-alt me-2"></i>
                    Detalle de Sugerencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleSugerenciaContent">
                <!-- Contenido cargado dinámicamente -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

</div> <!-- Cierre de perfil-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Cargar estadísticas del usuario
    loadUserStats();
    
    // Cargar sugerencias del usuario
    cargarSugerencias();
    
    // Manejar formulario de cambio de contraseña
    document.getElementById('cambiarPasswordForm').addEventListener('submit', function(e) {
        e.preventDefault();
        cambiarPassword();
    });
    
    // Manejar formulario de sugerencias
    const sugerenciaForm = document.getElementById('sugerenciaForm');
    if (sugerenciaForm) {
        sugerenciaForm.addEventListener('submit', function(e) {
            console.log('Formulario de sugerencias enviado'); // Debug
            e.preventDefault();
            e.stopPropagation();
            enviarSugerencia();
        });
        
        // También agregar listener al botón de submit por si acaso
        const submitButton = sugerenciaForm.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.addEventListener('click', function(e) {
                console.log('Botón de enviar clickeado'); // Debug
                e.preventDefault();
                e.stopPropagation();
                enviarSugerencia();
            });
        }
    } else {
        console.error('No se encontró el formulario de sugerencias');
    }
    
    // Manejar formulario de solicitudes de libros
    const solicitudLibroForm = document.getElementById('solicitudLibroForm');
    if (solicitudLibroForm) {
        solicitudLibroForm.addEventListener('submit', function(e) {
            console.log('Formulario de solicitud de libro enviado'); // Debug
            e.preventDefault();
            e.stopPropagation();
            enviarSolicitudLibro();
        });
        
        // Listener adicional para el botón
        const submitButtonSolicitud = solicitudLibroForm.querySelector('button[type="submit"]');
        if (submitButtonSolicitud) {
            submitButtonSolicitud.addEventListener('click', function(e) {
                console.log('Botón de enviar solicitud clickeado'); // Debug
                e.preventDefault();
                e.stopPropagation();
                enviarSolicitudLibro();
            });
        }
    } else {
        console.error('No se encontró el formulario de solicitud de libro');
    }
    
    // Cargar solicitudes de libros cuando se cambia a la tab de solicitudes
    const solicitudesTab = document.getElementById('solicitudes-tab');
    if (solicitudesTab) {
        solicitudesTab.addEventListener('shown.bs.tab', function () {
            console.log('Cargando solicitudes de libros...');
            cargarSolicitudesLibros();
        });
    }
    
    // Cargar libros para el select
    cargarLibros();
    
    // Cargar solicitudes iniciales (si la tab está activa)
    const solicitudesTabPane = document.getElementById('solicitudes-tab-pane');
    if (solicitudesTabPane && solicitudesTabPane.classList.contains('active')) {
        cargarSolicitudesLibros();
    }
    
    // Corregir problemas del modal de sugerencias
    const sugerenciaModal = document.getElementById('sugerenciaModal');
    if (sugerenciaModal) {
        sugerenciaModal.addEventListener('shown.bs.modal', function () {
            // Forzar que el textarea sea editable con múltiples métodos
            const textarea = document.getElementById('comentario');
            if (textarea) {
                // Eliminar cualquier atributo que pueda bloquear
                textarea.removeAttribute('disabled');
                textarea.removeAttribute('readonly');
                textarea.removeAttribute('disabled');
                
                // Forzar estilos inline
                textarea.style.cssText = `
                    background: white !important;
                    color: black !important;
                    pointer-events: auto !important;
                    user-select: text !important;
                    -webkit-user-select: text !important;
                    -moz-user-select: text !important;
                    -ms-user-select: text !important;
                    z-index: 99999 !important;
                    position: relative !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    display: block !important;
                `;
                
                // Forzar clases
                textarea.className = 'form-control';
                
                // Dar foco después de un pequeño delay
                setTimeout(() => {
                    textarea.focus();
                    textarea.click();
                    textarea.select();
                }, 100);
                
                // Añadir evento para asegurar que siempre sea editable
                textarea.addEventListener('click', function() {
                    this.focus();
                });
                
                textarea.addEventListener('focus', function() {
                    this.style.pointerEvents = 'auto';
                    this.style.userSelect = 'text';
                });
            }
            
            // Asegurar que todo el modal sea interactivo
            const modalContent = this.querySelector('.modal-content');
            const modalBody = this.querySelector('.modal-body');
            const modalForm = this.querySelector('#sugerenciaForm');
            
            if (modalContent) {
                modalContent.style.pointerEvents = 'auto';
                modalContent.style.zIndex = '99999';
            }
            if (modalBody) {
                modalBody.style.pointerEvents = 'auto';
                modalBody.style.zIndex = '99998';
            }
            if (modalForm) {
                modalForm.style.pointerEvents = 'auto';
                modalForm.style.zIndex = '99997';
            }
            
            // Eliminar cualquier backdrop que pueda bloquear
            const backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) {
                backdrop.style.pointerEvents = 'none';
                backdrop.style.zIndex = '1040';
            }
        });
        
        sugerenciaModal.addEventListener('show.bs.modal', function () {
            // Limpiar el formulario cuando se abre el modal
            const form = document.getElementById('sugerenciaForm');
            if (form) form.reset();
        });
        
        // También forzar cuando el modal está visible
        setInterval(() => {
            if (sugerenciaModal.classList.contains('show')) {
                const textarea = document.getElementById('comentario');
                if (textarea) {
                    textarea.style.pointerEvents = 'auto';
                    textarea.style.userSelect = 'text';
                }
            }
        }, 500);
    }
});

// Función para enviar sugerencia
function enviarSugerencia() {
    console.log('Función enviarSugerencia llamada'); // Debug
    
    const comentario = document.getElementById('comentario').value.trim();
    console.log('Comentario:', comentario); // Debug
    
    if (!comentario) {
        console.log('Comentario vacío, mostrando advertencia'); // Debug
        showNotification('Por favor escribe tu sugerencia', 'warning');
        return;
    }
    
    console.log('Enviando sugerencia al API...'); // Debug
    
    const formData = new FormData();
    formData.append('comentario', comentario);
    
    fetch('api/sugerencias.php?action=enviar', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta del API recibida:', response); // Debug
        return response.json();
    })
    .then(data => {
        console.log('Datos procesados:', data); // Debug
        if (data.success) {
            console.log('Éxito, mostrando notificación'); // Debug
            // Cerrar modal primero
            const modal = bootstrap.Modal.getInstance(document.getElementById('sugerenciaModal'));
            modal.hide();
            
            // Eliminar completamente el backdrop y cualquier elemento oscuro
            setTimeout(() => {
                // Eliminar todos los backdrops
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                
                // Eliminar cualquier clase modal-open del body
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
                
                // Forzar estilos del body
                document.body.style.cssText += '; overflow: auto !important; padding-right: 0 !important;';
                
                // Ahora mostrar la notificación
                console.log('Mostrando notificación con ticket:', data.ticket_id); // Debug
                showNotification('Sugerencia enviada exitosamente', 'success', data.ticket_id);
            }, 200);
            
            // Limpiar formulario
            document.getElementById('sugerenciaForm').reset();
            
            // Recargar sugerencias
            cargarSugerencias();
        } else {
            console.log('Error del API:', data.message); // Debug
            showNotification('Error al enviar sugerencia: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error); // Debug
        showNotification('Error de conexión al enviar sugerencia', 'error');
    });
}

// Función para cargar sugerencias del usuario
function cargarSugerencias() {
    fetch('api/sugerencias.php?action=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarSugerencias(data.sugerencias);
            } else {
                document.getElementById('sugerenciasContainer').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar sugerencias: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('sugerenciasContainer').innerHTML = 
                '<div class="alert alert-danger">Error de conexión al cargar sugerencias</div>';
        });
}

// Función para mostrar sugerencias
function mostrarSugerencias(sugerencias) {
    const container = document.getElementById('sugerenciasContainer');
    
    if (sugerencias.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                <p class="text-muted">No tienes sugerencias enviadas aún</p>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#sugerenciaModal">
                    <i class="fas fa-plus me-2"></i>Enviar tu primera sugerencia
                </button>
            </div>
        `;
        return;
    }
    
    let html = '<div class="row">';
    
    sugerencias.forEach(sugerencia => {
        const estadoClass = getEstadoClass(sugerencia.estado);
        const estadoIcon = getEstadoIcon(sugerencia.estado);
        
        html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100 border-0 shadow-sm sugerencia-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-ticket-alt me-1"></i>
                                    ${sugerencia.ticket_id || 'SG-' + sugerencia.id}
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${formatDate(sugerencia.fecha)}
                                </small>
                            </div>
                            <span class="badge ${estadoClass}">
                                ${estadoIcon}
                                ${sugerencia.estado_formateado}
                            </span>
                        </div>
                        
                        <p class="card-text text-muted small">
                            ${sugerencia.comentario.substring(0, 150)}${sugerencia.comentario.length > 150 ? '...' : ''}
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="verDetalleSugerencia(${sugerencia.id})">
                                <i class="fas fa-eye me-1"></i>Ver Detalle
                            </button>
                            <small class="text-muted">
                                ID: ${sugerencia.id}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

// Función para obtener clase según estado
function getEstadoClass(estado) {
    const clases = {
        'pendiente': 'bg-warning',
        'revisada': 'bg-info',
        'aprobada': 'bg-success',
        'rechazada': 'bg-danger'
    };
    return clases[estado] || 'bg-secondary';
}

// Función para obtener icono según estado
function getEstadoIcon(estado) {
    const iconos = {
        'pendiente': '<i class="fas fa-clock me-1"></i>',
        'revisada': '<i class="fas fa-eye me-1"></i>',
        'aprobada': '<i class="fas fa-check me-1"></i>',
        'rechazada': '<i class="fas fa-times me-1"></i>'
    };
    return iconos[estado] || '<i class="fas fa-question me-1"></i>';
}

// Función para formatear fecha
function formatDate(fechaStr) {
    const fecha = new Date(fechaStr);
    return fecha.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Función para ver detalle de sugerencia
function verDetalleSugerencia(id) {
    console.log('Solicitando detalle para sugerencia ID:', id); // Debug
    
    fetch(`api/sugerencias.php?action=detalle&id=${id}`)
        .then(response => {
            console.log('Respuesta del API (detalle):', response); // Debug
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos (detalle):', data); // Debug
            
            if (data.success) {
                console.log('Mostrando modal con:', data.sugerencia, data.respuestas); // Debug
                mostrarDetalleSugerencia(data.sugerencia, data.respuestas);
            } else {
                console.error('Error del API (detalle):', data.message); // Debug
                showNotification('Error al cargar detalle: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error en fetch (detalle):', error); // Debug
            showNotification('Error de conexión al cargar detalle', 'danger');
        });
}

// Función para mostrar detalle de sugerencia
function mostrarDetalleSugerencia(sugerencia, respuestas) {
    console.log('Mostrando detalle:', { sugerencia, respuestas }); // Debug
    
    const modal = new bootstrap.Modal(document.getElementById('detalleSugerenciaModal'));
    
    let html = `
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <h6><i class="fas fa-ticket-alt me-2"></i>Ticket: ${sugerencia.ticket_id || 'SG-' + sugerencia.id}</h6>
                    <p><strong>Fecha:</strong> ${formatDate(sugerencia.fecha)}</p>
                    <p><strong>Estado:</strong> 
                        <span class="badge ${getEstadoClass(sugerencia.estado)}">${getEstadoIcon(sugerencia.estado)} ${sugerencia.estado_formateado}</span>
                    </p>
                </div>
                
                <div class="mb-4">
                    <h6><i class="fas fa-comment me-2"></i>Tu Sugerencia:</h6>
                    <div class="p-3 bg-light rounded border-start border-4 border-primary">
                        <p class="mb-0">${sugerencia.comentario}</p>
                    </div>
                </div>
    `;
    
    if (respuestas && respuestas.length > 0) {
        html += `
            <div class="mb-3">
                <h6><i class="fas fa-reply me-2"></i>Respuestas del Administrador:</h6>
        `;
        
        respuestas.forEach(respuesta => {
            html += `
                <div class="mb-3 p-3 border-start border-4 border-success bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><i class="fas fa-user-shield me-1"></i>${respuesta.nombre_admin || 'Administrador'}</strong>
                            <span class="badge bg-info ms-2">Staff</span>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            ${formatDate(respuesta.fecha_respuesta)}
                        </small>
                    </div>
                    <div class="p-2 bg-white rounded">
                        <p class="mb-0">${respuesta.respuesta}</p>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
    } else {
        html += `
            <div class="text-center py-4">
                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                <p class="text-muted">Esta sugerencia aún no tiene respuestas del administrador</p>
                <small class="text-muted">Te notificaremos cuando el administrador responda tu sugerencia.</small>
            </div>
        `;
    }
    
    html += '</div>';
    
    document.getElementById('detalleSugerenciaContent').innerHTML = html;
    modal.show();
}

// Función para cargar estadísticas del usuario
function loadUserStats() {
    // Cargar total de favoritos
    fetch('api/favoritos.php?action=get_favorites')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('total-favoritos').textContent = data.favorites.length;
            }
        })
        .catch(error => {
            console.error('Error cargando favoritos:', error);
            document.getElementById('total-favoritos').textContent = '0';
        });
    
    // Simular total de accesos (esto podría venir de la BD)
    document.getElementById('total-accesos').textContent = Math.floor(Math.random() * 50) + 10;
}

// Función para cambiar contraseña
function cambiarPassword() {
    const passwordActual = document.getElementById('password_actual').value;
    const passwordNueva = document.getElementById('password_nueva').value;
    const passwordConfirmar = document.getElementById('password_confirmar').value;
    
    // Validaciones
    if (passwordNueva.length < 6) {
        showNotification('La nueva contraseña debe tener al menos 6 caracteres', 'danger');
        return;
    }
    
    if (passwordNueva !== passwordConfirmar) {
        showNotification('Las contraseñas nuevas no coinciden', 'danger');
        return;
    }
    
    if (passwordActual === passwordNueva) {
        showNotification('La nueva contraseña debe ser diferente a la actual', 'warning');
        return;
    }
    
    // Enviar solicitud para cambiar contraseña
    fetch('api/usuario.php?action=change_password', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            password_actual: passwordActual,
            password_nueva: passwordNueva
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Contraseña cambiada exitosamente', 'success');
            // Limpiar formulario
            document.getElementById('cambiarPasswordForm').reset();
        } else {
            showNotification(data.message || 'Error al cambiar contraseña', 'danger');
        }
    })
    .catch(error => {
        console.error('Error cambiando contraseña:', error);
        showNotification('Error al cambiar contraseña', 'danger');
    });
}

// Función para mostrar notificaciones elegantes
function showNotification(message, type = 'info', ticketId = null) {
    // Eliminar agresivamente cualquier elemento que pueda bloquear
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Eliminar cualquier overlay oscuro
    const overlays = document.querySelectorAll('[style*="background"], [style*="rgba"]');
    overlays.forEach(overlay => {
        const style = window.getComputedStyle(overlay);
        if (style.backgroundColor.includes('rgba') && style.backgroundColor.includes('0, 0, 0')) {
            overlay.style.display = 'none';
        }
    });
    
    // Forzar que el body sea completamente normal
    document.body.classList.remove('modal-open');
    document.body.style.overflow = 'auto';
    document.body.style.paddingRight = '0';
    document.body.style.background = '';
    document.body.style.backgroundColor = '';
    
    // Crear contenedor principal con z-index máximo
    const notificationContainer = document.createElement('div');
    notificationContainer.className = 'notification-container';
    notificationContainer.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999999;
        min-width: 350px;
        max-width: 450px;
        animation: slideInRight 0.4s ease-out;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        border-radius: 12px;
        overflow: hidden;
        backdrop-filter: blur(15px);
        background: rgba(255, 255, 255, 1);
        border: 2px solid rgba(255, 255, 255, 0.8);
        pointer-events: auto;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    `;
    
    // Determinar colores e iconos según el tipo
    const config = {
        success: {
            bg: 'linear-gradient(135deg, #28a745, #20c997)',
            icon: 'fas fa-check-circle',
            title: '¡Éxito!'
        },
        error: {
            bg: 'linear-gradient(135deg, #dc3545, #c82333)',
            icon: 'fas fa-exclamation-triangle',
            title: 'Error'
        },
        warning: {
            bg: 'linear-gradient(135deg, #ffc107, #e0a800)',
            icon: 'fas fa-exclamation-circle',
            title: 'Atención'
        },
        info: {
            bg: 'linear-gradient(135deg, #17a2b8, #138496)',
            icon: 'fas fa-info-circle',
            title: 'Información'
        }
    };
    
    const currentConfig = config[type] || config.info;
    
    // Crear contenido HTML elegante
    notificationContainer.innerHTML = `
        <div style="background: ${currentConfig.bg}; color: white; padding: 15px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center;">
                <i class="${currentConfig.icon} me-2" style="font-size: 1.2rem;"></i>
                <div>
                    <div style="font-weight: 600; font-size: 0.9rem;">${currentConfig.title}</div>
                    ${ticketId ? `<div style="font-size: 0.8rem; opacity: 0.9;">Ticket: ${ticketId}</div>` : ''}
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" onclick="this.closest('.notification-container').remove()" style="background: none; border: none; font-size: 1.2rem;"></button>
        </div>
        <div style="padding: 15px; color: #333; background: white;">
            <div style="font-size: 0.95rem; line-height: 1.4;">${message}</div>
            ${ticketId ? `
                <div style="margin-top: 10px; padding: 8px 12px; background: rgba(40, 167, 69, 0.1); border-left: 3px solid #28a745; border-radius: 4px;">
                    <small style="color: #28a745; font-weight: 600;">
                        <i class="fas fa-ticket-alt me-1"></i>
                        Guarda tu número de ticket: <strong>${ticketId}</strong>
                    </small>
                </div>
            ` : ''}
        </div>
        <div style="background: rgba(0, 0, 0, 0.05); padding: 8px 15px; text-align: center;">
            <small style="color: #666; font-size: 0.75rem;">
                <i class="fas fa-clock me-1"></i>
                Esta notificación se cerrará automáticamente en 5 segundos
            </small>
        </div>
    `;
    
    // Agregar al DOM
    document.body.appendChild(notificationContainer);
    
    // Forzar que esté por encima de TODO con múltiples métodos
    notificationContainer.style.zIndex = '9999999';
    notificationContainer.style.position = 'fixed';
    notificationContainer.style.pointerEvents = 'auto';
    
    // Aplicar estilos inline adicionales para asegurar visibilidad
    notificationContainer.style.setProperty('z-index', '9999999', 'important');
    notificationContainer.style.setProperty('position', 'fixed', 'important');
    notificationContainer.style.setProperty('pointer-events', 'auto', 'important');
    notificationContainer.style.setProperty('display', 'block', 'important');
    notificationContainer.style.setProperty('visibility', 'visible', 'important');
    notificationContainer.style.setProperty('opacity', '1', 'important');
    
    // Forzar de nuevo después de un pequeño delay
    setTimeout(() => {
        notificationContainer.style.zIndex = '9999999';
        notificationContainer.style.position = 'fixed';
        notificationContainer.style.pointerEvents = 'auto';
        notificationContainer.style.display = 'block';
        notificationContainer.style.visibility = 'visible';
        notificationContainer.style.opacity = '1';
        
        // Eliminar cualquier backdrop residual
        const residualBackdrops = document.querySelectorAll('.modal-backdrop');
        residualBackdrops.forEach(backdrop => {
            backdrop.style.display = 'none';
            backdrop.style.visibility = 'hidden';
            backdrop.style.opacity = '0';
        });
    }, 50);
    
    // Auto eliminar después de 5 segundos
    setTimeout(() => {
        notificationContainer.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => {
            if (notificationContainer.parentNode) {
                notificationContainer.remove();
            }
        }, 300);
    }, 5000);
    
    // Agregar animación de salida
    const style = document.createElement('style');
    style.textContent = `
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
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .notification-container:hover {
            transform: scale(1.02);
            transition: transform 0.2s ease;
        }
    `;
    
    if (!document.querySelector('#notification-styles')) {
        style.id = 'notification-styles';
        document.head.appendChild(style);
    }
}

// Funciones para solicitudes de libros
function cargarSolicitudesLibros() {
    fetch('api/solicitudes_libros.php?action=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarSolicitudesLibros(data.solicitudes);
            } else {
                document.getElementById('solicitudesContainer').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar solicitudes: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('solicitudesContainer').innerHTML = 
                '<div class="alert alert-danger">Error de conexión al cargar solicitudes</div>';
        });
}

function mostrarSolicitudesLibros(solicitudes) {
    const container = document.getElementById('solicitudesContainer');
    
    if (solicitudes.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-upload fa-3x text-muted mb-3"></i>
                <p class="text-muted">No tienes solicitudes de subida aún</p>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#solicitudLibroModal">
                    <i class="fas fa-plus me-2"></i>Solicitar primer libro
                </button>
            </div>
        `;
        return;
    }
    
    let html = '<div class="row">';
    
    solicitudes.forEach(solicitud => {
        const estadoClass = getEstadoSolicitudClass(solicitud.estado);
        const tipoClass = getTipoSolicitudClass(solicitud.tipo_solicitud);
        
        html += `
            <div class="col-md-6 mb-3">
                <div class="card h-100 border-0 shadow-sm solicitud-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-1">
                                    <i class="fas fa-upload me-1"></i>
                                    ${solicitud.codigo_solicitud || 'SUL-' + solicitud.id}
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    ${formatDate(solicitud.fecha_solicitud)}
                                </small>
                            </div>
                            <div>
                                <span class="badge ${tipoClass} me-1">${solicitud.tipo_formateado}</span>
                                <span class="badge ${estadoClass}">${solicitud.estado_formateado}</span>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <h6 class="text-muted mb-1">
                                <i class="fas fa-heading me-1"></i>
                                ${solicitud.titulo_solicitado}
                            </h6>
                            ${solicitud.libro_titulo ? `<small class="text-muted">Relacionado con: ${solicitud.libro_titulo}</small><br>` : ''}
                            ${solicitud.descripcion ? `<small class="text-muted">${solicitud.descripcion.substring(0, 100)}${solicitud.descripcion.length > 100 ? '...' : ''}</small>` : ''}
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-success" 
                                    onclick="verDetalleSolicitud(${solicitud.id})">
                                <i class="fas fa-eye me-1"></i>Ver Detalle
                            </button>
                            <small class="text-muted">
                                ID: ${solicitud.id}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function getEstadoSolicitudClass(estado) {
    const clases = {
        'pendiente': 'bg-warning',
        'aprobada': 'bg-success',
        'rechazada': 'bg-danger',
        'completada': 'bg-info'
    };
    return clases[estado] || 'bg-secondary';
}

function getTipoSolicitudClass(tipo) {
    const clases = {
        'sugerencia_compra': 'bg-success',
        'donacion': 'bg-primary',
        'digitalizacion': 'bg-info',
        'otro': 'bg-secondary'
    };
    return clases[tipo] || 'bg-secondary';
}

function verDetalleSolicitud(id) {
    console.log('Solicitando detalle para solicitud ID:', id);
    
    fetch(`api/solicitudes_libros.php?action=detalle&id=${id}`)
        .then(response => {
            console.log('Respuesta del API (detalle solicitud):', response);
            return response.json();
        })
        .then(data => {
            console.log('Datos recibidos (detalle solicitud):', data);
            
            if (data.success) {
                console.log('Mostrando modal con:', data.solicitud);
                mostrarDetalleSolicitudLibro(data.solicitud);
            } else {
                console.error('Error del API (detalle solicitud):', data.message);
                showNotification('Error al cargar detalle: ' + data.message, 'danger');
            }
        })
        .catch(error => {
            console.error('Error en fetch (detalle solicitud):', error);
            showNotification('Error de conexión al cargar detalle', 'danger');
        });
}

function mostrarDetalleSolicitudLibro(solicitud) {
    console.log('Mostrando detalle de solicitud de subida:', solicitud);
    
    const modal = new bootstrap.Modal(document.getElementById('detalleSolicitudModal'));
    
    let html = `
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <h6><i class="fas fa-upload me-2"></i>Código: ${solicitud.codigo_solicitud || 'SUL-' + solicitud.id}</h6>
                    <p><strong>Fecha:</strong> ${formatDate(solicitud.fecha_solicitud)}</p>
                    <p><strong>Estado:</strong> 
                        <span class="badge ${getEstadoSolicitudClass(solicitud.estado)}">${solicitud.estado_formateado}</span>
                    </p>
                    <p><strong>Tipo:</strong> 
                        <span class="badge ${getTipoSolicitudClass(solicitud.tipo_solicitud)}">${solicitud.tipo_formateado}</span>
                    </p>
                </div>
                
                ${solicitud.libro_titulo ? `
                <div class="mb-3">
                    <h6><i class="fas fa-book-open me-2"></i>Libro Existente Relacionado:</h6>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-1"><strong>Título:</strong> ${solicitud.libro_titulo}</p>
                        ${solicitud.libro_autor ? `<p class="mb-1"><strong>Autor:</strong> ${solicitud.libro_autor}</p>` : ''}
                        ${solicitud.libro_isbn ? `<p class="mb-0"><strong>ISBN:</strong> ${solicitud.libro_isbn}</p>` : ''}
                    </div>
                </div>
                ` : ''}
                
                <div class="mb-4">
                    <h6><i class="fas fa-heading me-2"></i>Título del Libro Solicitado para Subida:</h6>
                    <div class="p-3 bg-light rounded border-start border-4 border-success">
                        <p class="mb-0">${solicitud.titulo_solicitado}</p>
                    </div>
                </div>
                
                ${solicitud.descripcion ? `
                <div class="mb-4">
                    <h6><i class="fas fa-align-left me-2"></i>Descripción y Justificación:</h6>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-0">${solicitud.descripcion}</p>
                    </div>
                </div>
                ` : ''}
                
                ${solicitud.fecha_respuesta ? `
                <div class="mb-3">
                    <h6><i class="fas fa-reply me-2"></i>Fecha de Respuesta:</h6>
                    <p>${formatDate(solicitud.fecha_respuesta)}</p>
                </div>
                ` : ''}
                
                ${solicitud.observaciones ? `
                <div class="mb-3">
                    <h6><i class="fas fa-sticky-note me-2"></i>Respuesta del Administrador:</h6>
                    <div class="p-3 bg-info bg-opacity-10 rounded border-start border-4 border-info">
                        <p class="mb-0">${solicitud.observaciones}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        </div>
    `;
    
    document.getElementById('detalleSolicitudContent').innerHTML = html;
    modal.show();
}

function enviarSolicitudLibro() {
    console.log('Función enviarSolicitudLibro llamada');
    
    const formData = new FormData(document.getElementById('solicitudLibroForm'));
    
    const titulo_solicitado = formData.get('titulo_solicitado');
    const tipo_solicitud = formData.get('tipo_solicitud');
    
    console.log('Datos de la solicitud de subida:', { titulo_solicitado, tipo_solicitud });
    
    if (!titulo_solicitado) {
        console.log('Título vacío, mostrando advertencia');
        showNotification('Por favor escribe el título del libro a subir', 'warning');
        return;
    }
    
    if (!tipo_solicitud) {
        console.log('Tipo de solicitud vacío, mostrando advertencia');
        showNotification('Por favor selecciona el tipo de solicitud', 'warning');
        return;
    }
    
    console.log('Enviando solicitud de subida al API...');
    
    fetch('api/solicitudes_libros.php?action=enviar', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Respuesta del API recibida:', response);
        return response.json();
    })
    .then(data => {
        console.log('Datos procesados:', data);
        if (data.success) {
            console.log('Éxito, mostrando notificación');
            showNotification(`Solicitud de subida enviada exitosamente. Código: ${data.codigo_solicitud}`, 'success');
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('solicitudLibroModal'));
            if (modal) {
                modal.hide();
            }
            
            // Resetear formulario
            document.getElementById('solicitudLibroForm').reset();
            
            // Recargar solicitudes
            cargarSolicitudesLibros();
            
        } else {
            console.log('Error del API:', data.message);
            showNotification('Error al enviar solicitud de subida: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error en fetch:', error);
        showNotification('Error de conexión al enviar solicitud de subida', 'error');
    });
}

// Cargar libros para el select
function cargarLibros() {
    fetch('api/libros.php?action=listar')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('libro_id');
                select.innerHTML = '<option value="">Seleccionar libro (opcional)...</option>';
                
                data.libros.forEach(libro => {
                    const option = document.createElement('option');
                    option.value = libro.id;
                    option.textContent = `${libro.titulo} - ${libro.autor}`;
                    select.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error al cargar libros:', error);
        });
}
</script>
