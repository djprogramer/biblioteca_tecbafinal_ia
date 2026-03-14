<?php
session_start();
require_once 'includes/functions.php';

// Si no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    redirect('login.php');
}

// Verificar si el usuario es Super Admin
if ($_SESSION['rol'] !== 'Super Admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">No tienes permisos para acceder a esta sección.</div>';
    redirect('dashboard.php');
}

$pageTitle = 'Gestión de Sugerencias y Solicitudes';
require_once 'includes/header.php';
require_once 'includes/database.php';

// Procesar acciones de sugerencias
$action = $_GET['action'] ?? '';

if ($action === 'responder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = sanitize($_POST['id'] ?? '');
    $respuesta = sanitize($_POST['respuesta'] ?? '');
    $nuevo_estado = sanitize($_POST['estado'] ?? '');
    
    if (empty($id) || !is_numeric($id)) {
        $_SESSION['message'] = '<div class="alert alert-warning">ID de sugerencia no válido.</div>';
        redirect('gestion_sugerencias.php');
    }
    
    if (empty($respuesta)) {
        $_SESSION['message'] = '<div class="alert alert-warning">La respuesta no puede estar vacía.</div>';
        redirect('gestion_sugerencias.php');
    }
    
    if (empty($nuevo_estado)) {
        $_SESSION['message'] = '<div class="alert alert-warning">Debes seleccionar un nuevo estado.</div>';
        redirect('gestion_sugerencias.php');
    }
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Insertar respuesta
        $stmt_respuesta = $pdo->prepare("
            INSERT INTO sugerencias_respuestas (sugerencia_id, admin_id, respuesta, fecha_respuesta) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt_respuesta->execute([
            $id,
            $_SESSION['usuario_id'],
            $respuesta
        ]);
        
        // Actualizar estado de la sugerencia
        $stmt_update = $pdo->prepare("
            UPDATE sugerencias 
            SET estado = ? 
            WHERE id = ?
        ");
        $stmt_update->execute([$nuevo_estado, $id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        $_SESSION['message'] = '<div class="alert alert-success">Respuesta enviada y estado actualizado exitosamente.</div>';
        
    } catch (Exception $e) {
        // Revertir transacción
        $pdo->rollBack();
        
        $_SESSION['message'] = '<div class="alert alert-danger">Error al procesar la respuesta: ' . $e->getMessage() . '</div>';
    }
    
    redirect('gestion_sugerencias.php');
}

// Procesar acciones de solicitudes de libros
if ($action === 'actualizar_estado_solicitud' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = sanitize($_POST['id'] ?? '');
    $nuevo_estado = sanitize($_POST['estado'] ?? '');
    $observaciones = sanitize($_POST['observaciones'] ?? '');
    
    if (empty($id) || !is_numeric($id)) {
        $_SESSION['message'] = '<div class="alert alert-warning">ID de solicitud no válido.</div>';
        redirect('gestion_sugerencias.php');
    }
    
    if (empty($nuevo_estado) || !in_array($nuevo_estado, ['pendiente', 'aprobada', 'rechazada', 'completada'])) {
        $_SESSION['message'] = '<div class="alert alert-warning">Estado no válido.</div>';
        redirect('gestion_sugerencias.php');
    }
    
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Actualizar estado de la solicitud
        $stmt_update = $pdo->prepare("
            UPDATE solicitudes_libros 
            SET estado = ?, observaciones = ?, fecha_respuesta = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        $stmt_update->execute([$nuevo_estado, $observaciones, $id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        $_SESSION['message'] = '<div class="alert alert-success">Estado de solicitud actualizado exitosamente.</div>';
        
    } catch (Exception $e) {
        // Revertir transacción
        $pdo->rollBack();
        
        $_SESSION['message'] = '<div class="alert alert-danger">Error al actualizar estado: ' . $e->getMessage() . '</div>';
    }
    
    redirect('gestion_sugerencias.php');
}

// Obtener página actual
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$sugerencias_por_pagina = 20;
$offset = ($pagina - 1) * $sugerencias_por_pagina;

// Obtener total de sugerencias para paginación
$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM sugerencias");
$total_sugerencias = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_sugerencias / $sugerencias_por_pagina);

// Obtener sugerencias con información de usuarios y respuestas
$stmt = $pdo->prepare("
    SELECT s.*, u.nombre as nombre_usuario, u.email as email_usuario,
           COUNT(r.id) as total_respuestas,
           MAX(r.fecha_respuesta) as ultima_respuesta
    FROM sugerencias s
    JOIN usuarios u ON s.usuario_id = u.id
    LEFT JOIN sugerencias_respuestas r ON s.id = r.sugerencia_id
    GROUP BY s.id
    ORDER BY s.fecha DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$sugerencias_por_pagina, $offset]);
$sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener solicitudes de libros
$stmt_solicitudes = $pdo->prepare("
    SELECT sl.*, u.nombre as nombre_usuario, u.email as email_usuario,
           l.titulo as libro_titulo, l.autor as libro_autor,
           CASE 
               WHEN sl.estado = 'pendiente' THEN 'Pendiente'
               WHEN sl.estado = 'aprobada' THEN 'Aprobada'
               WHEN sl.estado = 'rechazada' THEN 'Rechazada'
               WHEN sl.estado = 'completada' THEN 'Completada'
               ELSE 'Pendiente'
           END as estado_formateado,
           CASE 
               WHEN sl.tipo_solicitud = 'sugerencia_compra' THEN 'Sugerencia de Compra'
               WHEN sl.tipo_solicitud = 'donacion' THEN 'Donación'
               WHEN sl.tipo_solicitud = 'digitalizacion' THEN 'Digitalización'
               WHEN sl.tipo_solicitud = 'otro' THEN 'Otro'
               ELSE sl.tipo_solicitud
           END as tipo_formateado
    FROM solicitudes_libros sl
    LEFT JOIN usuarios u ON sl.usuario_id = u.id
    LEFT JOIN libros l ON sl.libro_id = l.id
    ORDER BY sl.fecha_solicitud DESC
    LIMIT 10
");
$stmt_solicitudes->execute([]);
$solicitudes_libros = $stmt_solicitudes->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
/* Fondo consistente */
.gestion-background {
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

.gestion-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con glassmorphism */
.gestion-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.gestion-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.2);
}

/* Tarjetas de sugerencias */
.sugerencia-card {
    height: 100%;
    transition: all 0.3s ease;
    border-left: 4px solid rgb(255, 113, 0);
}

.sugerencia-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(255, 113, 0, 0.3);
}

.sugerencia-header {
    background: linear-gradient(135deg, rgba(255, 113, 0, 0.1), rgba(220, 90, 0, 0.05));
    padding: 15px;
    border-bottom: 1px solid rgba(255, 113, 0, 0.1);
}

.sugerencia-content {
    padding: 15px;
}

.ticket-id {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

.estado-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.estado-pendiente {
    background-color: #ffc107;
    color: #000;
}

.estado-revisada {
    background-color: #17a2b8;
    color: white;
}

.estado-aprobada {
    background-color: #28a745;
    color: white;
}

.estado-rechazada {
    background-color: #dc3545;
    color: white;
}

/* Botones */
.btn-primary {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0)) !important;
    border: none !important;
}

.btn-primary:hover {
    background: linear-gradient(135deg, rgb(220, 90, 0), rgb(200, 80, 0)) !important;
    transform: translateY(-2px);
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #218838) !important;
    border: none !important;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
    border: none !important;
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

.fade-in-up {
    animation: fadeInUp 0.6s ease-out;
}

.gestion-card, .sugerencia-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Estilos para solicitudes de libros */
.solicitud-libro-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    margin-bottom: 20px;
}

.solicitud-libro-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.solicitud-libro-header {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(34, 139, 34, 0.05));
    padding: 15px;
    border-bottom: 1px solid rgba(40, 167, 69, 0.1);
    border-radius: 15px 15px 0 0;
}

.codigo-solicitud-libro {
    background: linear-gradient(135deg, rgb(40, 167, 69), rgb(34, 139, 34));
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
}

/* Estados para solicitudes de libros */
.estado-solicitud-pendiente { background: #ffc107; color: #000; }
.estado-solicitud-aprobada { background: #28a745; color: #fff; }
.estado-solicitud-rechazada { background: #dc3545; color: #fff; }
.estado-solicitud-completada { background: #17a2b8; color: #fff; }

/* Tipos para solicitudes de libros */
.tipo-solicitud-sugerencia_compra { background: #28a745; color: #fff; }
.tipo-solicitud-donacion { background: #007bff; color: #fff; }
.tipo-solicitud-digitalizacion { background: #17a2b8; color: #fff; }
.tipo-solicitud-otro { background: #6c757d; color: #fff; }

/* Tabs */
.nav-tabs .nav-link {
    color: #6c757d;
    border: 1px solid transparent;
    border-top-left-radius: 0.375rem;
    border-top-right-radius: 0.375rem;
}

.nav-tabs .nav-link.active {
    color: #495057;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
}

.nav-tabs .nav-link:hover {
    color: #007bff;
    border-color: transparent;
}

/* Paginación */
.pagination .page-link {
    color: rgb(255, 113, 0);
    border-color: #dee2e6;
}

.pagination .page-link:hover {
    color: rgb(220, 90, 0);
    background-color: rgba(255, 113, 0, 0.1);
}

/* Corrección para modales */
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

.pagination .page-item.active .page-link {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    border-color: rgb(255, 113, 0);
}
</style>

<div class="gestion-background"></div>

<div class="gestion-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Gestión de Sugerencias y Solicitudes</li>
        </ol>
    </nav>
    
    <!-- Mensajes de sesión -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="mb-4">
            <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']); // Limpiar mensaje después de mostrarlo
            ?>
        </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white gestion-card" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-comments me-2"></i>
                                Gestión de Sugerencias y Solicitudes de Libros
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-lightbulb me-1"></i>
                                Revisa y gestiona las sugerencias de mejora y solicitudes de subida de libros
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="dashboard.php" class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-2 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-comments fa-2x text-primary mb-2"></i>
                    <h4><?php echo $total_sugerencias; ?></h4>
                    <small class="text-muted">Sugerencias</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_pendientes = $pdo->prepare("SELECT COUNT(*) as count FROM sugerencias WHERE estado = 'pendiente'");
                        $stmt_pendientes->execute();
                        echo $stmt_pendientes->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Sug. Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-upload fa-2x text-success mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_solicitudes_total = $pdo->prepare("SELECT COUNT(*) as count FROM solicitudes_libros");
                        $stmt_solicitudes_total->execute();
                        echo $stmt_solicitudes_total->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Solicitudes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-hourglass-half fa-2x text-info mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_solicitudes_pendientes = $pdo->prepare("SELECT COUNT(*) as count FROM solicitudes_libros WHERE estado = 'pendiente'");
                        $stmt_solicitudes_pendientes->execute();
                        echo $stmt_solicitudes_pendientes->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Sol. Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-check fa-2x text-success mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_aprobadas = $pdo->prepare("SELECT COUNT(*) as count FROM sugerencias WHERE estado = 'aprobada'");
                        $stmt_aprobadas->execute();
                        echo $stmt_aprobadas->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Sug. Aprobadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x text-info mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_solicitudes_aprobadas = $pdo->prepare("SELECT COUNT(*) as count FROM solicitudes_libros WHERE estado = 'aprobada'");
                        $stmt_solicitudes_aprobadas->execute();
                        echo $stmt_solicitudes_aprobadas->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Sol. Aprobadas</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabs para diferenciar sugerencias y solicitudes -->
    <div class="card border-0 gestion-card">
        <div class="card-body">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4" id="gestionTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="sugerencias-tab" data-bs-toggle="tab" data-bs-target="#sugerencias-tab-pane" type="button" role="tab">
                        <i class="fas fa-comments me-2"></i>Sugerencias
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="solicitudes-tab" data-bs-toggle="tab" data-bs-target="#solicitudes-tab-pane" type="button" role="tab">
                        <i class="fas fa-upload me-2"></i>Solicitudes de Subida
                    </button>
                </li>
            </ul>
            
            <!-- Tab content -->
            <div class="tab-content" id="gestionTabContent">
                <!-- Tab de Sugerencias -->
                <div class="tab-pane fade show active" id="sugerencias-tab-pane" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>
                                Lista de Sugerencias
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Mostrando 
                                <strong><?php echo min(($pagina - 1) * $sugerencias_por_pagina + 1, $total_sugerencias); ?></strong> - 
                                <strong><?php echo min($pagina * $sugerencias_por_pagina, $total_sugerencias); ?></strong> 
                                de <strong><?php echo $total_sugerencias; ?></strong> sugerencias
                            </small>
                        </div>
                    </div>
            
            <div class="row">
                <?php foreach ($sugerencias as $sugerencia): ?>
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="card h-100 border-0 shadow-sm sugerencia-card">
                        <div class="sugerencia-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="ticket-id">
                                        <i class="fas fa-ticket-alt me-1"></i>
                                        <?php echo $sugerencia['ticket_id'] ?: 'SG-' . str_pad($sugerencia['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </span>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($sugerencia['fecha'])); ?>
                                    </small>
                                </div>
                                <span class="estado-badge estado-<?php echo $sugerencia['estado']; ?>">
                                    <?php
                                    $estados = [
                                        'pendiente' => 'Pendiente',
                                        'revisada' => 'Revisada',
                                        'aprobada' => 'Aprobada',
                                        'rechazada' => 'Rechazada'
                                    ];
                                    echo $estados[$sugerencia['estado']] ?? $sugerencia['estado'];
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="sugerencia-content">
                            <div class="mb-3">
                                <h6 class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    <?php echo htmlspecialchars($sugerencia['nombre_usuario']); ?>
                                    <small class="text-muted">(<?php echo htmlspecialchars($sugerencia['email_usuario']); ?>)</small>
                                </h6>
                            </div>
                            
                            <div class="mb-3">
                                <p class="mb-2">
                                    <i class="fas fa-comment me-1"></i>
                                    <?php echo htmlspecialchars(substr($sugerencia['comentario'], 0, 200)); ?>
                                    <?php echo strlen($sugerencia['comentario']) > 200 ? '...' : ''; ?>
                                </p>
                                
                                <?php if ($sugerencia['total_respuestas'] > 0): ?>
                                    <div class="alert alert-info small">
                                        <i class="fas fa-reply me-1"></i>
                                        <?php echo $sugerencia['total_respuestas']; ?> respuesta(s) enviada(s)
                                        <?php if (!empty($sugerencia['ultima_respuesta'])): ?>
                                            <br><small>Última respuesta: <?php echo date('d/m/Y H:i', strtotime($sugerencia['ultima_respuesta'])); ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#respuestaModal"
                                        onclick="cargarSugerenciaParaResponder(<?php echo $sugerencia['id']; ?>)">
                                    <i class="fas fa-reply me-1"></i>Responder
                                </button>
                                <button type="button" class="btn btn-sm btn-info" 
                                        onclick="verDetalleCompleto(<?php echo $sugerencia['id']; ?>)">
                                    <i class="fas fa-eye me-1"></i>Ver Detalle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center">
                        <label for="pagina_select" class="me-2">Ir a página:</label>
                        <select class="form-select form-select-sm" id="pagina_select" style="width: auto;" onchange="irAPagina(this.value)">
                            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo ($i == $pagina) ? 'selected' : ''; ?>>
                                    Página <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <nav aria-label="Paginación de sugerencias">
                        <ul class="pagination pagination-sm justify-content-end mb-0">
                            <!-- Primera página -->
                            <li class="page-item <?php echo ($pagina == 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=1">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Página anterior -->
                            <li class="page-item <?php echo ($pagina == 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo max(1, $pagina - 1); ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <!-- Páginas numeradas -->
                            <?php
                            $rango = 2;
                            $inicio = max(1, $pagina - $rango);
                            $fin = min($total_paginas, $pagina + $rango);
                            
                            if ($inicio > 1) {
                                echo '<li class="page-item"><a class="page-link" href="?pagina=1">1</a></li>';
                                if ($inicio > 2) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                            }
                            
                            for ($i = $inicio; $i <= $fin; $i++) {
                                echo '<li class="page-item ' . (($i == $pagina) ? 'active' : '') . '">';
                                echo '<a class="page-link" href="?pagina=' . $i . '">' . $i . '</a>';
                                echo '</li>';
                            }
                            
                            if ($fin < $total_paginas) {
                                if ($fin < $total_paginas - 1) {
                                    echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                }
                                echo '<li class="page-item"><a class="page-link" href="?pagina=' . $total_paginas . '">' . $total_paginas . '</a></li>';
                            }
                            ?>
                            
                            <!-- Página siguiente -->
                            <li class="page-item <?php echo ($pagina == $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo min($total_paginas, $pagina + 1); ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            
                            <!-- Última página -->
                            <li class="page-item <?php echo ($pagina == $total_paginas) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $total_paginas; ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
                </div>
                
                <!-- Tab de Solicitudes de Subida -->
                <div class="tab-pane fade" id="solicitudes-tab-pane" role="tabpanel">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-upload me-2"></i>
                                Solicitudes de Subida de Libros
                            </h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Mostrando las últimas <strong>10</strong> solicitudes
                            </small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <?php foreach ($solicitudes_libros as $solicitud): ?>
                        <div class="col-lg-6 col-md-12 mb-4">
                            <div class="card h-100 border-0 shadow-sm solicitud-libro-card">
                                <div class="solicitud-libro-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="codigo-solicitud-libro">
                                                <i class="fas fa-upload me-1"></i>
                                                <?php echo $solicitud['codigo_solicitud'] ?: 'SUL-' . str_pad($solicitud['id'], 6, '0', STR_PAD_LEFT); ?>
                                            </span>
                                            <small class="text-muted ms-3">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <span class="badge tipo-solicitud-<?php echo $solicitud['tipo_solicitud']; ?> me-1">
                                                <?php echo $solicitud['tipo_formateado']; ?>
                                            </span>
                                            <span class="badge estado-solicitud-<?php echo $solicitud['estado']; ?>">
                                                <?php echo $solicitud['estado_formateado']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <h6 class="text-muted mb-1">
                                            <i class="fas fa-user me-1"></i>
                                            <?php echo htmlspecialchars($solicitud['nombre_usuario']); ?>
                                            <?php if ($solicitud['email_usuario']): ?>
                                            <small class="text-muted">(<a href="mailto:<?php echo $solicitud['email_usuario']; ?>"><?php echo $solicitud['email_usuario']; ?></a>)</small>
                                            <?php endif; ?>
                                        </h6>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h6 class="text-primary mb-1">
                                            <i class="fas fa-heading me-1"></i>
                                            <?php echo htmlspecialchars($solicitud['titulo_solicitado']); ?>
                                        </h6>
                                        <?php if ($solicitud['libro_titulo']): ?>
                                        <small class="text-muted">Relacionado con: <?php echo htmlspecialchars($solicitud['libro_titulo']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if ($solicitud['descripcion']): ?>
                                    <div class="mb-3">
                                        <p class="mb-2">
                                            <i class="fas fa-align-left me-1"></i>
                                            <?php echo htmlspecialchars(substr($solicitud['descripcion'], 0, 200)); ?>
                                            <?php if (strlen($solicitud['descripcion']) > 200) echo '...'; ?>
                                        </p>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($solicitud['fecha_respuesta']): ?>
                                    <div class="alert alert-info small">
                                        <i class="fas fa-reply me-1"></i>
                                        Respondido el <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_respuesta'])); ?>
                                        <?php if ($solicitud['observaciones']): ?>
                                        <br><strong>Observaciones:</strong> <?php echo htmlspecialchars(substr($solicitud['observaciones'], 0, 100)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detalleSolicitudModal"
                                                onclick="verDetalleSolicitud(<?php echo $solicitud['id']; ?>)">
                                            <i class="fas fa-eye me-1"></i>Ver Detalle
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#respuestaSolicitudModal"
                                                onclick="responderSolicitud(<?php echo $solicitud['id']; ?>)">
                                            <i class="fas fa-reply me-1"></i>Actualizar Estado
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Cierre de gestion-content -->

<!-- Modal Responder Sugerencia -->
<div class="modal fade" id="respuestaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0)); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>
                    Responder Sugerencia
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?action=responder">
                <div class="modal-body">
                    <input type="hidden" name="id" id="sugerencia_id_responder">
                    
                    <div class="mb-3">
                        <h6 class="text-muted">Información de la Sugerencia:</h6>
                        <div id="info_sugerencia_responder" class="p-3 bg-light rounded">
                            <!-- Cargado dinámicamente -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="respuesta" class="form-label">
                            <i class="fas fa-comment me-1"></i>
                            Tu Respuesta
                        </label>
                        <textarea class="form-control" id="respuesta" name="respuesta" rows="5" 
                                  placeholder="Escribe tu respuesta para el usuario..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado" class="form-label">
                            <i class="fas fa-flag me-1"></i>
                            Nuevo Estado
                        </label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Seleccionar nuevo estado...</option>
                            <option value="revisada">Revisada</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Respuesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Variables globales
let sugerenciasData = <?php echo json_encode($sugerencias); ?>;

// Ir a página específica
function irAPagina(pagina) {
    window.location.href = 'gestion_sugerencias.php?pagina=' + pagina;
}

// Cargar sugerencia para responder
function cargarSugerenciaParaResponder(id) {
    const sugerencia = sugerenciasData.find(s => s.id === id);
    if (sugerencia) {
        document.getElementById('sugerencia_id_responder').value = id;
        
        const infoDiv = document.getElementById('info_sugerencia_responder');
        infoDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong><i class="fas fa-user me-1"></i>Usuario:</strong> ${sugerencia.nombre_usuario}<br>
                    <strong><i class="fas fa-envelope me-1"></i>Email:</strong> ${sugerencia.email_usuario}<br>
                    <strong><i class="fas fa-ticket-alt me-1"></i>Ticket:</strong> ${sugerencia.ticket_id || 'SG-' + String(sugerencia.id).padStart(6, '0')}
                </div>
                <div class="col-md-6">
                    <strong><i class="fas fa-calendar me-1"></i>Fecha:</strong> ${new Date(sugerencia.fecha).toLocaleString()}<br>
                    <strong><i class="fas fa-flag me-1"></i>Estado:</strong> 
                    <span class="badge estado-${sugerencia.estado}">${sugerencia.estado}</span>
                </div>
            </div>
            <hr>
            <div class="mt-2">
                <strong><i class="fas fa-comment me-1"></i>Sugerencia:</strong><br>
                <div class="mt-2 p-2 bg-white rounded">${sugerencia.comentario}</div>
            </div>
        `;
    }
}

// Ver detalle completo
function verDetalleCompleto(id) {
    console.log('Cargando detalle completo para sugerencia ID:', id);
    
    fetch(`api/sugerencias.php?action=detalle&id=${id}`)
        .then(response => response.json())
        .then(data => {
            console.log('Datos recibidos:', data);
            
            if (data.success) {
                mostrarModalDetalleAdmin(data.sugerencia, data.respuestas);
            } else {
                alert('Error al cargar detalle: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al cargar detalle');
        });
}

// Mostrar modal de detalle para admin
function mostrarModalDetalleAdmin(sugerencia, respuestas) {
    // Crear el modal si no existe
    let modal = document.getElementById('detalleAdminModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'detalleAdminModal';
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0)); color: white;">
                        <h5 class="modal-title">
                            <i class="fas fa-ticket-alt me-2"></i>
                            Detalle Completo de Sugerencia
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="detalleAdminContent">
                        <!-- Contenido dinámico -->
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cerrar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="responderSugerencia(${sugerencia.id})">
                            <i class="fas fa-reply me-2"></i>Responder
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
    }
    
    // Generar contenido HTML
    let html = `
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <h6><i class="fas fa-ticket-alt me-2"></i>Ticket: ${sugerencia.ticket_id || 'SG-' + sugerencia.id}</h6>
                    <p><strong>Fecha:</strong> ${new Date(sugerencia.fecha).toLocaleString()}</p>
                    <p><strong>Estado:</strong> 
                        <span class="badge estado-${sugerencia.estado}">${getEstadoLabel(sugerencia.estado)}</span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-user me-2"></i>Información del Usuario:</h6>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-1"><strong>Nombre:</strong> ${sugerencia.nombre_usuario || 'N/A'}</p>
                        <p class="mb-0"><strong>Email:</strong> ${sugerencia.email_usuario || 'N/A'}</p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h6><i class="fas fa-comment me-2"></i>Sugerencia del Usuario:</h6>
                    <div class="p-3 bg-light rounded border-start border-4 border-primary">
                        <p class="mb-0">${sugerencia.comentario}</p>
                    </div>
                </div>
    `;
    
    if (respuestas && respuestas.length > 0) {
        html += `
            <div class="mb-3">
                <h6><i class="fas fa-reply me-2"></i>Respuestas Enviadas:</h6>
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
                            ${new Date(respuesta.fecha_respuesta).toLocaleString()}
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
                <p class="text-muted">Esta sugerencia aún no tiene respuestas</p>
                <small class="text-muted">Usa el botón "Responder" para enviar una respuesta al usuario.</small>
            </div>
        `;
    }
    
    html += '</div>';
    
    // Actualizar contenido y mostrar modal
    document.getElementById('detalleAdminContent').innerHTML = html;
    
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

// Obtener etiqueta de estado
function getEstadoLabel(estado) {
    const labels = {
        'pendiente': 'Pendiente',
        'revisada': 'En Revisión',
        'aprobada': 'Aprobada',
        'rechazada': 'Rechazada'
    };
    return labels[estado] || estado;
}

// Responder a sugerencia (redirigir al modal de respuesta)
function responderSugerencia(id) {
    // Cerrar modal de detalle
    const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('detalleAdminModal'));
    if (modalDetalle) {
        modalDetalle.hide();
    }
    
    // Abrir modal de respuesta
    setTimeout(() => {
        // Llenar datos en el modal de respuesta
        document.getElementById('sugerencia_id_responder').value = id;
        
        // Cargar información de la sugerencia
        fetch(`api/sugerencias.php?action=detalle&id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const infoDiv = document.getElementById('info_sugerencia_responder');
                    infoDiv.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <strong><i class="fas fa-user me-1"></i>Usuario:</strong> ${data.sugerencia.nombre_usuario}<br>
                                <strong><i class="fas fa-envelope me-1"></i>Email:</strong> ${data.sugerencia.email_usuario}<br>
                                <strong><i class="fas fa-ticket-alt me-1"></i>Ticket:</strong> ${data.sugerencia.ticket_id || 'SG-' + String(data.sugerencia.id).padStart(6, '0')}
                            </div>
                            <div class="col-md-6">
                                <strong><i class="fas fa-calendar me-1"></i>Fecha:</strong> ${new Date(data.sugerencia.fecha).toLocaleString()}<br>
                                <strong><i class="fas fa-flag me-1"></i>Estado:</strong> 
                                <span class="badge estado-${data.sugerencia.estado}">${getEstadoLabel(data.sugerencia.estado)}</span>
                            </div>
                        </div>
                        <hr>
                        <div class="mt-2">
                            <strong><i class="fas fa-comment me-1"></i>Sugerencia:</strong><br>
                            <div class="mt-2 p-2 bg-white rounded">${data.sugerencia.comentario}</div>
                        </div>
                    `;
                    
                    // Abrir modal de respuesta
                    const modalRespuesta = new bootstrap.Modal(document.getElementById('respuestaModal'));
                    modalRespuesta.show();
                }
            });
    }, 300);
}
</script>

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
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Respuesta Solicitud -->
<div class="modal fade" id="respuestaSolicitudModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, rgb(40, 167, 69), rgb(34, 139, 34)); color: white;">
                <h5 class="modal-title">
                    <i class="fas fa-reply me-2"></i>
                    Actualizar Estado de Solicitud de Subida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?action=actualizar_estado_solicitud">
                <div class="modal-body">
                    <input type="hidden" name="id" id="solicitud_id_responder">
                    
                    <div class="mb-3">
                        <h6 class="text-muted">Información de la Solicitud:</h6>
                        <div id="info_solicitud_responder" class="p-3 bg-light rounded">
                            <!-- Cargado dinámicamente -->
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="estado" class="form-label">
                            <i class="fas fa-flag me-1"></i>
                            Nuevo Estado
                        </label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="">Seleccionar nuevo estado...</option>
                            <option value="aprobada">Aprobada</option>
                            <option value="rechazada">Rechazada</option>
                            <option value="completada">Completada</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">
                            <i class="fas fa-sticky-note me-1"></i>
                            Observaciones
                        </label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="4" 
                                  placeholder="Escribe las observaciones para el usuario..."></textarea>
                        <small class="text-muted">Estas observaciones serán visibles para el usuario</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2"></i>Actualizar Estado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funciones para solicitudes de libros
function verDetalleSolicitud(id) {
    fetch(`api/solicitudes_libros.php?action=detalle&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetalleSolicitud(data.solicitud);
            } else {
                alert('Error al cargar detalle: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión al cargar detalle');
        });
}

function mostrarDetalleSolicitud(solicitud) {
    const modal = new bootstrap.Modal(document.getElementById('detalleSolicitudModal'));
    
    let html = `
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3">
                    <h6><i class="fas fa-upload me-2"></i>Código: ${solicitud.codigo_solicitud || 'SUL-' + solicitud.id}</h6>
                    <p><strong>Fecha:</strong> ${new Date(solicitud.fecha_solicitud).toLocaleString()}</p>
                    <p><strong>Estado:</strong> 
                        <span class="badge estado-solicitud-${solicitud.estado}">${solicitud.estado_formateado}</span>
                    </p>
                    <p><strong>Tipo:</strong> 
                        <span class="badge tipo-solicitud-${solicitud.tipo_solicitud}">${solicitud.tipo_formateado}</span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <h6><i class="fas fa-user me-2"></i>Información del Usuario:</h6>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-1"><strong>Nombre:</strong> ${solicitud.nombre_usuario || 'N/A'}</p>
                        <p class="mb-0"><strong>Email:</strong> ${solicitud.email_usuario || 'N/A'}</p>
                    </div>
                </div>
                
                ${solicitud.libro_titulo ? `
                <div class="mb-3">
                    <h6><i class="fas fa-book-open me-2"></i>Libro Existente Relacionado:</h6>
                    <div class="p-3 bg-light rounded">
                        <p class="mb-1"><strong>Título:</strong> ${solicitud.libro_titulo}</p>
                        ${solicitud.libro_autor ? `<p class="mb-0"><strong>Autor:</strong> ${solicitud.libro_autor}</p>` : ''}
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
                    <p>${new Date(solicitud.fecha_respuesta).toLocaleString()}</p>
                </div>
                ` : ''}
                
                ${solicitud.observaciones ? `
                <div class="mb-3">
                    <h6><i class="fas fa-sticky-note me-2"></i>Observaciones:</h6>
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

function responderSolicitud(id) {
    // Llenar datos en el modal de respuesta
    document.getElementById('solicitud_id_responder').value = id;
    
    // Cargar información de la solicitud
    fetch(`api/solicitudes_libros.php?action=detalle&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const infoDiv = document.getElementById('info_solicitud_responder');
                infoDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong><i class="fas fa-user me-1"></i>Usuario:</strong> ${data.solicitud.nombre_usuario}<br>
                            <strong><i class="fas fa-envelope me-1"></i>Email:</strong> ${data.solicitud.email_usuario}<br>
                            <strong><i class="fas fa-upload me-1"></i>Código:</strong> ${data.solicitud.codigo_solicitud || 'SUL-' + String(data.solicitud.id).padStart(6, '0')}
                        </div>
                        <div class="col-md-6">
                            <strong><i class="fas fa-calendar me-1"></i>Fecha:</strong> ${new Date(data.solicitud.fecha_solicitud).toLocaleString()}<br>
                            <strong><i class="fas fa-flag me-1"></i>Estado:</strong> 
                            <span class="badge estado-solicitud-${data.solicitud.estado}">${data.solicitud.estado_formateado}</span>
                        </div>
                    </div>
                    <hr>
                    <div class="mt-2">
                        <strong><i class="fas fa-heading me-1"></i>Título Solicitado para Subida:</strong><br>
                        <div class="mt-2 p-2 bg-white rounded">${data.solicitud.titulo_solicitado}</div>
                    </div>
                    ${data.solicitud.descripcion ? `
                    <div class="mt-2">
                        <strong><i class="fas fa-align-left me-1"></i>Descripción y Justificación:</strong><br>
                        <div class="mt-2 p-2 bg-white rounded">${data.solicitud.descripcion}</div>
                    </div>
                    ` : ''}
                `;
            }
        });
}
</script>
