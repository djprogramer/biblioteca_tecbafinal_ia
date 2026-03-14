<?php
require_once 'includes/functions.php';
require_once 'includes/auto_cleanup.php'; // Sistema de limpieza automática

session_start();

// Si no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    redirect('login.php');
}

$pageTitle = 'Asistente de IA';
require_once 'includes/header.php';

// Mostrar notificación de limpieza automática si es Super Admin
if (isset($_SESSION['cleanup_notification']) && $_SESSION['rol'] === 'Super Admin') {
    $notification = $_SESSION['cleanup_notification'];
    echo "
    <div class='alert alert-{$notification['type']} alert-dismissible fade show' role='alert'>
        <i class='fas fa-broom me-2'></i>
        {$notification['message']}
        <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
    </div>";
    unset($_SESSION['cleanup_notification']);
}

// Mostrar panel de estado de limpieza para Super Admin
if ($_SESSION['rol'] === 'Super Admin') {
    $cleanupManager = new AutoCleanupManager();
    $status = $cleanupManager->getLastCleanupStatus();
    
    echo "
    <div class='row mb-3'>
        <div class='col-12'>
            <div class='card border-0 ai-card'>
                <div class='card-body'>
                    <div class='d-flex justify-content-between align-items-center'>
                        <div>
                            <h6 class='mb-2'>
                                <i class='fas fa-clock me-2'></i>
                                Estado de Limpieza Automática
                            </h6>
                            <small class='text-muted'>
                                Próxima limpieza: {$status['next_cleanup']['datetime']} 
                                (en {$status['next_cleanup']['days_remaining']} días)
                            </small>
                        </div>
                        <div class='text-end'>
                            <button class='btn btn-sm btn-outline-primary' onclick='showCleanupDetails()'>
                                <i class='fas fa-info-circle me-1'></i>Detalles
                            </button>
                            <button class='btn btn-sm btn-outline-warning' onclick='forceCleanup()'>
                                <i class='fas fa-broom me-1'></i>Forzar Limpieza
                            </button>
                        </div>
                    </div>";
    
    if ($status['is_sunday_night']) {
        echo "
        <div class='alert alert-info mt-2 mb-0'>
            <i class='fas fa-exclamation-triangle me-2'></i>
            <strong>Atención:</strong> Es domingo después de 23:55. La limpieza automática se ejecutará pronto.
        </div>";
    }
    
    echo "
                </div>
            </div>
        </div>
    </div>";
}
?>

<style>
/* Fondo del asistente con imagen y opacidad */
.ai-background {
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
.ai-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con efecto glassmorphism */
.ai-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.ai-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.2);
}

/* Animaciones */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Optimización de modales */
.modal {
    z-index: 1050 !important;
}

.modal-backdrop {
    z-index: 1040 !important;
    background-color: rgba(0, 0, 0, 0.3) !important;
}

.modal-backdrop.show {
    opacity: 0.3 !important;
}

/* Asegurar que el backdrop no bloquee clics */
.modal-backdrop {
    pointer-events: none;
}

.modal.show .modal-backdrop {
    pointer-events: auto;
}

.modal-dialog {
    margin: 1rem auto;
    max-width: 1140px; /* Tamaño para modal-xl */
    z-index: 1051 !important;
    position: relative;
}

.modal-dialog.modal-lg {
    max-width: 800px;
}

.modal-dialog.modal-xl {
    max-width: 1140px;
}

.modal-body {
    padding: 1.5rem !important;
    max-height: 60vh;
    overflow-y: auto;
    position: relative;
    z-index: 1052 !important;
}

.modal-content {
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    background: white !important;
    position: relative;
    z-index: 1051 !important;
}

.modal-header {
    position: relative;
    z-index: 1053 !important;
}

.modal-footer {
    position: relative;
    z-index: 1053 !important;
}

/* Asegurar que los modales no se vean afectados por el layout */
.modal-open {
    overflow: hidden;
}

.modal-open .modal {
    overflow-x: hidden;
    overflow-y: auto;
}

/* Textareas optimizadas */
.form-control {
    resize: vertical;
    position: relative;
    z-index: 1054 !important;
}

.form-select {
    position: relative;
    z-index: 1054 !important;
}

.form-label {
    position: relative;
    z-index: 1054 !important;
}

.btn {
    position: relative;
    z-index: 1054 !important;
}

.form-control:focus {
    border-color: #ff7100;
    box-shadow: 0 0 0 0.2rem rgba(255, 113, 0, 0.25);
    position: relative;
    z-index: 1055 !important;
}

/* Limitar altura del panel de estadísticas */
#userStats {
    max-height: 300px;
    overflow-y: auto;
}

/* Asegurar que el sidebar no afecte el layout principal */
.col-md-4 {
    min-height: 0;
}

/* Evitar que el contenido del sidebar se expanda demasiado */
#userStats .card-body {
    max-height: 400px;
    overflow-y: auto;
}

/* Estilos para el Logo IA */
.logo-ia-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.logo-ia-image {
    width: 150px;
    height: 150px;
    object-fit: contain;
    filter: none; /* Eliminar filtro para mostrar colores originales */
    transition: transform 0.3s ease;
    background: transparent;
    border-radius: 8px;
    padding: 5px;
}

.logo-ia-image:hover {
    transform: scale(1.03);
}

/* Para asegurar que el texto se vea bien */
.logo-ia-container .small {
    color: white !important;
    font-weight: 500;
}

/* Logo IA para el área del chat */
.logo-ia-chat {
    width: 100px;
    height: 100px;
    object-fit: contain;
    filter: none; /* Eliminar filtro para mostrar colores originales */
    transition: transform 0.3s ease;
    background: transparent;
    border-radius: 6px;
    padding: 3px;
}

.logo-ia-chat:hover {
    transform: scale(1.03);
}

/* Alternativa si la imagen tiene fondo blanco */
.logo-ia-image.fallback {
    filter: drop-shadow(0 0 3px rgba(255,255,255,0.8));
}

.logo-ia-chat.fallback {
    filter: drop-shadow(0 0 2px rgba(0,0,0,0.3));
}

.ai-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Estilos para el chat */
.chat-container {
    height: 500px;
    overflow-y: auto;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 10px;
    padding: 20px;
}

.chat-message {
    margin-bottom: 15px;
    padding: 12px 15px;
    border-radius: 15px;
    max-width: 80%;
    word-wrap: break-word;
}

.chat-message.user {
    background: #000000;
    color: white;
    margin-left: auto;
    text-align: right;
}

.chat-message.ai {
    background: rgba(255, 113, 0, 0.1);
    border: 1px solid rgba(255, 113, 0, 0.3);
    margin-right: auto;
}

.chat-message .time {
    font-size: 0.75rem;
    opacity: 0.7;
    margin-top: 5px;
}

/* Indicador de escritura */
.typing-indicator {
    display: none;
    padding: 10px 15px;
    background: rgba(255, 113, 0, 0.1);
    border-radius: 15px;
    margin-bottom: 15px;
    max-width: 80px;
}

.typing-indicator.show {
    display: block;
}

.typing-indicator span {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgb(255, 113, 0);
    margin: 0 2px;
    animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) {
    animation-delay: 0.2s;
}

.typing-indicator span:nth-child(3) {
    animation-delay: 0.4s;
}

@keyframes typing {
    0%, 60%, 100% {
        transform: translateY(0);
    }
    30% {
        transform: translateY(-10px);
    }
}

/* Botones de acción rápida */
.quick-action-btn {
    border: 2px solid rgb(255, 113, 0);
    background: white;
    color: rgb(255, 113, 0);
    padding: 15px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 100%;
}

.quick-action-btn:hover {
    background: rgb(255, 113, 0);
    color: white;
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
}

/* Formularios */
.form-control, .form-select {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: rgb(255, 113, 0);
    box-shadow: 0 0 0 0.2rem rgba(255, 113, 0, 0.25);
}

/* Botones primarios negros */
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

/* Estadísticas */
.stat-card {
    text-align: center;
    padding: 20px;
    background: rgba(248, 249, 250, 0.8);
    border-radius: 10px;
    margin-bottom: 15px;
}

.stat-card h4 {
    color: rgb(255, 113, 0);
    font-weight: bold;
    margin: 10px 0 5px 0;
}

.stat-card p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 768px) {
    .chat-container {
        height: 400px;
    }
    
    .chat-message {
        max-width: 90%;
    }
}
</style>

<div class="ai-background"></div>

<div class="ai-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Panel Principal</a></li>
            <li class="breadcrumb-item active">Asistente de IA</li>
        </ol>
    </nav>
    
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white ai-card" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1">
                                Asistente de IA Bibliotecario
                            </h2>
                            <p class="mb-0">Tu ayudante inteligente para recomendaciones, resúmenes y aprendizaje</p>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="text-white">
                                <i class="fas fa-brain fa-3x mb-2"></i>
                                <div class="small">IA Implementada por Grupo ZIS</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Acciones Rápidas -->
    <div class="row mb-4">
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="quick-action-btn" onclick="openRecommendationsModal()">
                <i class="fas fa-book-reader"></i>
                <h6>Recomendaciones</h6>
                <small>Libros personalizados para ti</small>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="quick-action-btn" onclick="openSummaryModal()">
                <i class="fas fa-compress-alt"></i>
                <h6>Resumir</h6>
                <small>Resume textos y libros</small>
            </div>
        </div>
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="quick-action-btn" onclick="openAcademicModal()">
                <i class="fas fa-graduation-cap"></i>
                <h6>Ayuda Académica</h6>
                <small>Asistencia con tareas</small>
            </div>
        </div>
    </div>
    
    <!-- Configuración de Límites (solo para administradores) -->
    <?php if ($_SESSION['rol'] === 'Super Admin'): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 ai-card" style="background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">
                                <i class="fas fa-cog me-2"></i>
                                Configuración de Límites por Rol
                            </h5>
                            <p class="mb-0 small opacity-75">Administra los límites de uso del asistente de IA según el rol de usuario</p>
                        </div>
                        <button class="btn btn-light btn-sm" onclick="toggleLimitsConfig()">
                            <i class="fas fa-sliders-h me-1"></i>
                            Configurar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Panel de Configuración de Límites (oculto por defecto) -->
    <div class="row mb-4" id="limitsConfigPanel" style="display: none;">
        <div class="col-12">
            <div class="card border-0 ai-card">
                <div class="card-body">
                    <h6 class="card-title mb-4">
                        <i class="fas fa-user-shield me-2"></i>
                        Límites de Uso por Rol
                    </h6>
                    
                    <div id="rolesLimitsContainer">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="text-muted mt-2">Cargando configuración de límites...</p>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" onclick="saveLimitsConfig()">
                            <i class="fas fa-save me-2"></i>
                            Guardar Cambios
                        </button>
                        <button class="btn btn-secondary ms-2" onclick="toggleLimitsConfig()">
                            <i class="fas fa-times me-2"></i>
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Chat Principal -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 ai-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-comments me-2"></i>
                            Chat con Asistente
                        </h5>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearChat()">
                            <i class="fas fa-trash me-1"></i>Limpiar
                        </button>
                    </div>
                    
                    <div class="chat-container" id="chatContainer">
                        <div class="text-center text-muted py-5">
                            <img src="images/Logo IA.png" alt="Logo IA" class="logo-ia-chat mb-3">
                            <p>¡Hola! Soy tu asistente bibliotecario. ¿En qué puedo ayudarte hoy?</p>
                            <small>Puedes pedirme recomendaciones de libros, solicitar resúmenes de textos o pedir ayuda académica.</small>
                        </div>
                    </div>
                    
                    <div class="typing-indicator" id="typingIndicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    
                    <div class="input-group mt-3">
                        <input type="text" class="form-control" id="chatInput" placeholder="Escribe tu pregunta aquí..." onkeypress="handleChatKeyPress(event)">
                        <button class="btn btn-primary" onclick="sendChatMessage()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Estadísticas de Uso -->
            <div class="card border-0 ai-card">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-chart-bar me-2"></i>
                        Tu Uso de IA
                    </h5>
                    
                    <div id="userStats">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="text-muted mt-2">Cargando estadísticas...</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-info small mt-3" id="userLimitsInfo">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>Tus límites de uso:</strong><br>
                        <span id="dailyLimit">• Cargando...</span><br>
                        <span id="hourlyLimit">• Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Cierre de ai-content -->

<!-- Modal de Recomendaciones -->
<div class="modal fade" id="recommendationsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #28a745, #218838); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-book-reader me-2"></i>
                    Recomendaciones Personalizadas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="recommendationsForm">
                    <div class="mb-3">
                        <label for="userInterests" class="form-label">
                            <i class="fas fa-heart me-1"></i>
                            Tus Intereses
                        </label>
                        <textarea class="form-control" id="userInterests" rows="3" 
                                  placeholder="Ej: Psicología educativa, tecnología en educación, métodos de enseñanza..." style="min-height: 80px; max-height: 150px;"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="academicLevel" class="form-label">
                            <i class="fas fa-graduation-cap me-1"></i>
                            Nivel Académico
                        </label>
                        <select class="form-select" id="academicLevel">
                            <option value="secundario">Secundario</option>
                            <option value="universitario" selected>Universitario</option>
                            <option value="posgrado">Posgrado</option>
                            <option value="investigacion">Investigación</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitRecommendations()">
                    <i class="fas fa-magic me-2"></i>Obtener Recomendaciones
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Resumen -->
<div class="modal fade" id="summaryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #17a2b8, #138496); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-compress-alt me-2"></i>
                    Resumir Contenido
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="summaryForm">
                    <div class="mb-3">
                        <label for="contentType" class="form-label">
                            <i class="fas fa-tag me-1"></i>
                            Tipo de Contenido
                        </label>
                        <select class="form-select" id="contentType">
                            <option value="libro">Libro</option>
                            <option value="articulo">Artículo</option>
                            <option value="tesis">Tesis</option>
                            <option value="documento">Documento</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="contentToSummarize" class="form-label">
                            <i class="fas fa-file-alt me-1"></i>
                            Contenido a Resumir
                        </label>
                        <textarea class="form-control" id="contentToSummarize" rows="6" 
                                  placeholder="Pega aquí el texto que quieres resumir..." style="min-height: 120px; max-height: 300px;"></textarea>
                        <div class="form-text">Máximo 2000 caracteres para mejores resultados.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitSummary()">
                    <i class="fas fa-compress-alt me-2"></i>Resumir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Ayuda Académica -->
<div class="modal fade" id="academicModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, #6f42c1, #5a32a3); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-graduation-cap me-2"></i>
                    Ayuda Académica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="academicForm">
                    <div class="mb-3">
                        <label for="subject" class="form-label">
                            <i class="fas fa-book me-1"></i>
                            Materia
                        </label>
                        <input type="text" class="form-control" id="subject" 
                               placeholder="Ej: Matemáticas, Historia, Biología...">
                    </div>
                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">
                            <i class="fas fa-tasks me-1"></i>
                            Descripción de la Tarea
                        </label>
                        <textarea class="form-control" id="taskDescription" rows="5" 
                                  placeholder="Describe la tarea o problema que necesitas resolver..." style="min-height: 100px; max-height: 200px;"></textarea>
                        <div class="form-text">El asistente te guiará paso a paso, pero no hará la tarea por ti.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="submitAcademicHelp()">
                    <i class="fas fa-lightbulb me-2"></i>Obtener Ayuda
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Respuesta -->
<div class="modal fade" id="responseModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0)); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-robot me-2"></i>
                    Respuesta del Asistente
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="responseContent" class="alert alert-info">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <p class="mt-2">Procesando tu solicitud...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="copyResponse()">
                    <i class="fas fa-copy me-2"></i>Copiar Respuesta
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
let currentResponse = '';

// Cargar estadísticas del usuario
document.addEventListener('DOMContentLoaded', function() {
    loadUserStats();
});

function loadUserStats() {
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'stats'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayUserStats(data.stats);
        }
    })
    .catch(error => {
        console.error('Error cargando estadísticas:', error);
    });
}

function displayUserStats(stats) {
    const statsContainer = document.getElementById('userStats');
    
    if (stats.length === 0) {
        statsContainer.innerHTML = `
            <div class="text-center py-3">
                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                <p class="text-muted">Aún no has usado el asistente</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    let totalRequests = 0;
    
    stats.forEach(stat => {
        totalRequests += stat.requests_by_type;
        
        const icon = getStatIcon(stat.type);
        const label = getStatLabel(stat.type);
        
        html += `
            <div class="stat-card">
                <i class="${icon}"></i>
                <h4>${stat.requests_by_type}</h4>
                <p>${label}</p>
            </div>
        `;
    });
    
    html += `
        <div class="stat-card bg-light">
            <i class="fas fa-calculator"></i>
            <h4>${totalRequests}</h4>
            <p>Total de peticiones</p>
        </div>
    `;
    
    statsContainer.innerHTML = html;
}

function getStatIcon(type) {
    const icons = {
        'research': 'fas fa-search',
        'recommendations': 'fas fa-book-reader',
        'summary': 'fas fa-compress-alt',
        'academic': 'fas fa-graduation-cap'
    };
    return icons[type] || 'fas fa-robot';
}

function getStatLabel(type) {
    const labels = {
        'research': 'Investigaciones',
        'recommendations': 'Recomendaciones',
        'summary': 'Resúmenes',
        'academic': 'Ayuda académica'
    };
    return labels[type] || 'Otras';
}

// Funciones para abrir modales
function openRecommendationsModal() {
    const modal = new bootstrap.Modal(document.getElementById('recommendationsModal'));
    modal.show();
}

function openSummaryModal() {
    const modal = new bootstrap.Modal(document.getElementById('summaryModal'));
    modal.show();
}

function openAcademicModal() {
    const modal = new bootstrap.Modal(document.getElementById('academicModal'));
    modal.show();
}

// Funciones para enviar peticiones
function submitRecommendations() {
    const interests = document.getElementById('userInterests').value.trim();
    const level = document.getElementById('academicLevel').value;
    
    if (!interests) {
        alert('Por favor, describe tus intereses.');
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('recommendationsModal')).hide();
    showResponseModal();
    
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'recommendations',
            interests: interests,
            level: level
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentResponse = data.response;
            displayResponse(data.response, data.cached);
            
            // Agregar al chat
            addChatMessage(`Recomendaciones para: ${interests} (Nivel: ${level})`, 'user');
            addChatMessage(data.response, 'ai');
        } else {
            displayError(data.message);
        }
    })
    .catch(error => {
        displayError('Error de conexión. Intenta nuevamente.');
        console.error('Error:', error);
    });
}

function submitSummary() {
    const content = document.getElementById('contentToSummarize').value.trim();
    const type = document.getElementById('contentType').value;
    
    if (!content) {
        alert('Por favor, pega el contenido que quieres resumir.');
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('summaryModal')).hide();
    showResponseModal();
    
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'summarize',
            content: content,
            type: type
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentResponse = data.response;
            displayResponse(data.response, data.cached);
            
            // Agregar al chat
            addChatMessage(`Resumir ${type}: ${content.substring(0, 100)}...`, 'user');
            addChatMessage(data.response, 'ai');
        } else {
            displayError(data.message);
        }
    })
    .catch(error => {
        displayError('Error de conexión. Intenta nuevamente.');
        console.error('Error:', error);
    });
}

function submitAcademicHelp() {
    const task = document.getElementById('taskDescription').value.trim();
    const subject = document.getElementById('subject').value.trim();
    
    if (!task || !subject) {
        alert('Por favor, completa todos los campos.');
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('academicModal')).hide();
    showResponseModal();
    
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'academic_help',
            task: task,
            subject: subject
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            currentResponse = data.response;
            displayResponse(data.response, data.cached);
            
            // Agregar al chat
            addChatMessage(`Ayuda con ${subject}: ${task.substring(0, 100)}...`, 'user');
            addChatMessage(data.response, 'ai');
        } else {
            displayError(data.message);
        }
    })
    .catch(error => {
        displayError('Error de conexión. Intenta nuevamente.');
        console.error('Error:', error);
    });
}

// Funciones del chat
function handleChatKeyPress(event) {
    if (event.key === 'Enter') {
        sendChatMessage();
    }
}

function sendChatMessage() {
    console.log('sendChatMessage() llamada');
    
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    console.log('Mensaje:', message);
    
    if (!message) {
        console.log('Mensaje vacío, retornando');
        return;
    }
    
    // Verificar si es una solicitud de continuación
    const isContinuation = /^continua|sigue|continuar|seguir|continue$/i.test(message);
    
    input.value = '';
    addChatMessage(message, 'user');
    showTypingIndicator();
    
    console.log('Enviando petición a API...');
    
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'research',
            question: message,
            is_continuation: isContinuation
        })
    })
    .then(response => {
        console.log('Respuesta recibida:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Datos recibidos:', data);
        hideTypingIndicator();
        
        if (data.success) {
            addChatMessage(data.response, 'ai');
        } else {
            addChatMessage('Error: ' + data.message, 'ai', true);
        }
    })
    .catch(error => {
        console.error('Error completo:', error);
        hideTypingIndicator();
        addChatMessage('Error de conexión. Intenta nuevamente.', 'ai', true);
    });
}

function showCleanupDetails() {
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'cleanup_status' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let details = `
                <h6>📊 Estadísticas de Almacenamiento</h6>
                <div class="row">
            `;
            
            data.stats.forEach(stat => {
                details += `
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h6 class="text-primary">${stat.rol}</h6>
                                <small class="text-muted">
                                    Peticiones: ${stat.requests_count}<br>
                                    Espacio: ${stat.size_mb} MB<br>
                                    Más antigua: ${stat.oldest_request || 'N/A'}
                                </small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            details += `</div>`;
            
            // Mostrar en modal
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Detalles de Limpieza</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${details}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            modal.addEventListener('hidden.bs.modal', () => {
                document.body.removeChild(modal);
            });
        }
    });
}

function forceCleanup() {
    if (confirm('¿Estás seguro de forzar la limpieza manualmente? Esta acción eliminará datos antiguos según las políticas de rol.')) {
        fetch('api/ai_assistant.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'force_cleanup' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`✅ Limpieza forzada completada: ${data.result.deleted} registros eliminados`);
                location.reload();
            } else {
                alert('❌ Error: ' + data.message);
            }
        });
    }
}

function addChatMessage(message, sender, isError = false) {
    const chatContainer = document.getElementById('chatContainer');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${sender}`;
    
    if (isError) {
        messageDiv.className += ' bg-danger text-white';
    }
    
    const time = new Date().toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    // Procesar markdown para mejor formato
    let formattedMessage = message;
    
    if (sender === 'ai') {
        // Convertir markdown a HTML básico
        formattedMessage = formattedMessage
            .replace(/###\s*(.+)/g, '<h6 class="mt-3 mb-2 text-primary">$1</h6>')
            .replace(/\*\*\*(.+?)\*\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/^- (.+)/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            .replace(/\n\n/g, '<br><br>')
            .replace(/\n/g, '<br>');
    }
    
    messageDiv.innerHTML = `
        <div class="message-content">${formattedMessage}</div>
        <div class="time">${time}</div>
    `;
    
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function showTypingIndicator() {
    document.getElementById('typingIndicator').classList.add('show');
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.scrollTop = chatContainer.scrollHeight;
}

function hideTypingIndicator() {
    document.getElementById('typingIndicator').classList.remove('show');
}

function clearChat() {
    if (confirm('¿Estás seguro de que quieres limpiar el chat?')) {
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.innerHTML = `
            <div class="text-center text-muted py-5">
                <img src="images/Logo IA.png" alt="Logo IA" class="logo-ia-chat mb-3">
                <p>Chat limpio. ¿En qué puedo ayudarte ahora?</p>
            </div>
        `;
    }
}

// Funciones del modal de respuesta
function showResponseModal() {
    const modal = new bootstrap.Modal(document.getElementById('responseModal'));
    modal.show();
}

function displayResponse(response, cached) {
    const responseContent = document.getElementById('responseContent');
    
    const cacheInfo = cached ? 
        '<div class="alert alert-warning small mb-3"><i class="fas fa-clock me-1"></i>Respuesta desde caché (entrega más rápida)</div>' : 
        '<div class="alert alert-success small mb-3"><i class="fas fa-check me-1"></i>Respuesta generada por IA</div>';
    
    responseContent.innerHTML = `
        ${cacheInfo}
        <div class="response-text">${response.replace(/\n/g, '<br>')}</div>
    `;
    
    loadUserStats(); // Actualizar estadísticas
}

function displayError(message) {
    const responseContent = document.getElementById('responseContent');
    responseContent.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

function copyResponse() {
    if (!currentResponse) return;
    
    navigator.clipboard.writeText(currentResponse).then(() => {
        // Mostrar notificación de éxito
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>¡Copiado!';
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
        }, 2000);
    });
}

// Funciones para configuración de límites (solo administradores)
function toggleLimitsConfig() {
    const panel = document.getElementById('limitsConfigPanel');
    const isVisible = panel.style.display !== 'none';
    
    if (!isVisible) {
        panel.style.display = 'block';
        loadRoleLimits();
    } else {
        panel.style.display = 'none';
    }
}

function loadRoleLimits() {
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_role_limits'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayRoleLimits(data.roles);
        } else {
            console.error('Error cargando límites:', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function displayRoleLimits(roles) {
    const container = document.getElementById('rolesLimitsContainer');
    container.innerHTML = '';

    roles.forEach(role => {
        const isUnlimited = role.daily_limit >= 999999 || role.hourly_limit >= 999999;
        
        const roleCard = document.createElement('div');
        roleCard.className = 'row mb-3 align-items-center';
        roleCard.innerHTML = `
            <div class="col-md-3">
                <strong>${role.role_name}</strong>
                ${isUnlimited ? '<span class="badge bg-success ms-2">ILIMITADO</span>' : '<span class="badge bg-warning ms-2">LIMITADO</span>'}
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">Día</span>
                    <input type="number" class="form-control" id="daily_${role.role_name}" 
                           value="${isUnlimited ? 999999 : role.daily_limit}" 
                           min="0" max="999999" ${isUnlimited ? 'readonly' : ''}>
                </div>
            </div>
            <div class="col-md-3">
                <div class="input-group">
                    <span class="input-group-text">Hora</span>
                    <input type="number" class="form-control" id="hourly_${role.role_name}" 
                           value="${isUnlimited ? 999999 : role.hourly_limit}" 
                           min="0" max="999999" ${isUnlimited ? 'readonly' : ''}>
                </div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">${role.description || ''}</small>
            </div>
        `;
        
        container.appendChild(roleCard);
    });
}

function saveLimitsConfig() {
    const roles = ['Super Admin', 'Administrativo', 'Docente', 'Estudiante'];
    const limits = [];

    roles.forEach(roleName => {
        const dailyLimit = parseInt(document.getElementById(`daily_${roleName}`).value);
        const hourlyLimit = parseInt(document.getElementById(`hourly_${roleName}`).value);
        
        limits.push({
            role_name: roleName,
            daily_limit: dailyLimit,
            hourly_limit: hourlyLimit
        });
    });

    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_role_limits',
            limits: limits
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Límites actualizados correctamente');
            loadUserStats(); // Actualizar estadísticas
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión. Intenta nuevamente.');
    });
}

// Modificar loadUserStats para incluir límites del usuario
function loadUserStats() {
    fetch('api/ai_assistant.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'stats_with_limits'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayUserStats(data.stats);
            
            // Actualizar información de límites
            if (data.user_limits) {
                const limits = data.user_limits;
                const dailyText = limits.daily_limit >= 999999 ? 
                    '• Límite diario: Ilimitado' : 
                    `• Límite diario: ${limits.daily_used}/${limits.daily_limit}`;
                const hourlyText = limits.hourly_limit >= 999999 ? 
                    '• Límite por hora: Ilimitado' : 
                    `• Límite por hora: ${limits.hourly_used}/${limits.hourly_limit}`;
                
                document.getElementById('dailyLimit').textContent = dailyText;
                document.getElementById('hourlyLimit').textContent = hourlyText;
            }
        }
    })
    .catch(error => {
        console.error('Error cargando estadísticas:', error);
    });
}

function copyResponse() {
    if (!currentResponse) return;
    
    navigator.clipboard.writeText(currentResponse).then(() => {
        // Mostrar notificación de éxito
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>¡Copiado!';
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
        }, 2000);
    }).catch(() => {
        alert('No se pudo copiar el texto. Copia manualmente.');
    });
}

// Limpiar formularios al cerrar modales
document.getElementById('recommendationsModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('recommendationsForm').reset();
});

document.getElementById('summaryModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('summaryForm').reset();
});

document.getElementById('academicModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('academicForm').reset();
});
</script>

<?php require_once 'includes/footer.php'; ?>
