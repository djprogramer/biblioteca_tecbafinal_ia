<?php
require_once 'includes/header.php';
require_once 'includes/database.php';

// Verificar si el usuario es Super Admin
if ($_SESSION['rol'] !== 'Super Admin') {
    $_SESSION['message'] = '<div class="alert alert-danger">No tienes permisos para acceder a esta sección.</div>';
    redirect('dashboard.php');
}

$pageTitle = 'Gestión de Solicitudes de Subida de Libros';
require_once 'includes/header.php';
require_once 'includes/database.php';

// Procesar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'actualizar_estado' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_estado = sanitize($_POST['estado'] ?? '');
    $observaciones = sanitize($_POST['observaciones'] ?? '');
    
    if (empty($nuevo_estado) || !in_array($nuevo_estado, ['pendiente', 'aprobada', 'rechazada', 'completada'])) {
        $_SESSION['message'] = '<div class="alert alert-warning">Estado no válido.</div>';
        redirect('gestion_solicitudes_libros.php');
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
        
        $_SESSION['message'] = '<div class="alert alert-success">Estado actualizado exitosamente.</div>';
        
    } catch (Exception $e) {
        // Revertir transacción
        $pdo->rollBack();
        
        $_SESSION['message'] = '<div class="alert alert-danger">Error al actualizar estado: ' . $e->getMessage() . '</div>';
    }
    
    redirect('gestion_solicitudes_libros.php');
}

// Obtener página actual
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$solicitudes_por_pagina = 20;
$offset = ($pagina - 1) * $solicitudes_por_pagina;

// Obtener total de solicitudes para paginación
$stmt_total = $pdo->query("SELECT COUNT(*) as total FROM solicitudes_libros");
$total_solicitudes = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_solicitudes / $solicitudes_por_pagina);

// Obtener solicitudes con información de usuarios
$stmt = $pdo->prepare("
    SELECT sl.*, u.nombre as nombre_usuario, u.email as email_usuario,
           l.titulo as libro_titulo, l.autor as libro_autor
    FROM solicitudes_libros sl
    LEFT JOIN usuarios u ON sl.usuario_id = u.id
    LEFT JOIN libros l ON sl.libro_id = l.id
    ORDER BY sl.fecha_solicitud DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$solicitudes_por_pagina, $offset]);
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    background-repeat: no-repeat;
    z-index: -1;
}

.gestion-content {
    position: relative;
    z-index: 1;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    margin: 20px 0;
    padding: 30px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
}

/* Cards consistentes */
.gestion-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.gestion-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.solicitud-card {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.solicitud-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
}

.solicitud-header {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(34, 139, 34, 0.05));
    padding: 15px;
    border-bottom: 1px solid rgba(40, 167, 69, 0.1);
}

.solicitud-content {
    padding: 15px;
}

.codigo-solicitud {
    background: linear-gradient(135deg, rgb(40, 167, 69), rgb(34, 139, 34));
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

.tipo-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Estados */
.estado-pendiente { background: #ffc107; color: #000; }
.estado-aprobada { background: #28a745; color: #fff; }
.estado-rechazada { background: #dc3545; color: #fff; }
.estado-completada { background: #17a2b8; color: #fff; }

/* Tipos */
.tipo-sugerencia_compra { background: #28a745; color: #fff; }
.tipo-donacion { background: #007bff; color: #fff; }
.tipo-digitalizacion { background: #17a2b8; color: #fff; }
.tipo-otro { background: #6c757d; color: #fff; }

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

.slide-in-right {
    animation: slideInRight 0.3s ease-out;
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
            <li class="breadcrumb-item active">Gestión de Solicitudes de Libros</li>
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
            <div class="card border-0 text-white gestion-card" style="background: linear-gradient(135deg, rgb(40, 167, 69), rgb(34, 139, 34));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-upload me-2"></i>
                                Gestión de Solicitudes de Subida de Libros
                            </h2>
                            <p class="mb-0 mt-2">
                                Revisa y gestiona las solicitudes de subida de nuevos libros a la biblioteca
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
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-book fa-2x text-success mb-2"></i>
                    <h4><?php echo $total_solicitudes; ?></h4>
                    <small class="text-muted">Total Solicitudes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_pendientes = $pdo->query("SELECT COUNT(*) as count FROM solicitudes_libros WHERE estado = 'pendiente'");
                        echo $stmt_pendientes->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-check fa-2x text-info mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_aprobadas = $pdo->query("SELECT COUNT(*) as count FROM solicitudes_libros WHERE estado = 'aprobada'");
                        echo $stmt_aprobadas->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Aprobadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-tasks fa-2x text-success mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_completadas = $pdo->query("SELECT COUNT(*) as count FROM solicitudes_libros WHERE estado = 'completada'");
                        echo $stmt_completadas->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Completadas</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de solicitudes -->
    <div class="card border-0 gestion-card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Solicitudes
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Mostrando 
                        <strong><?php echo min(($pagina - 1) * $solicitudes_por_pagina + 1, $total_solicitudes); ?></strong> - 
                        <strong><?php echo min($pagina * $solicitudes_por_pagina, $total_solicitudes); ?></strong> 
                        de <strong><?php echo $total_solicitudes; ?></strong> solicitudes
                    </small>
                </div>
            </div>
            
            <div class="row">
                <?php foreach ($solicitudes as $solicitud): ?>
                <div class="col-lg-6 col-md-12 mb-4">
                    <div class="card h-100 border-0 shadow-sm solicitud-card">
                        <div class="solicitud-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="codigo-solicitud">
                                        <i class="fas fa-upload me-1"></i>
                                        <?php echo $solicitud['codigo_solicitud'] ?: 'SUL-' . str_pad($solicitud['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </span>
                                    <small class="text-muted ms-3">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                    </small>
                                </div>
                                <div>
                                    <span class="tipo-badge tipo-<?php echo $solicitud['tipo_solicitud']; ?> me-1">
                                        <?php
                                        $tipos = [
                                            'sugerencia_compra' => 'Sugerencia de Compra',
                                            'donacion' => 'Donación',
                                            'digitalizacion' => 'Digitalización',
                                            'otro' => 'Otro'
                                        ];
                                        echo $tipos[$solicitud['tipo_solicitud']] ?? $solicitud['tipo_solicitud'];
                                        ?>
                                    </span>
                                    <span class="estado-badge estado-<?php echo $solicitud['estado']; ?>">
                                        <?php
                                        $estados = [
                                            'pendiente' => 'Pendiente',
                                            'aprobada' => 'Aprobada',
                                            'rechazada' => 'Rechazada',
                                            'completada' => 'Completada'
                                        ];
                                        echo $estados[$solicitud['estado']] ?? $solicitud['estado'];
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="solicitud-content">
                            <div class="mb-3">
                                <h6 class="text-muted">
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
                                <small class="text-muted">Libro relacionado: <?php echo htmlspecialchars($solicitud['libro_titulo']); ?></small>
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
                <div class="col-md-6 text-end">
                    <nav>
                        <ul class="pagination pagination-sm mb-0 justify-content-end">
                            <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $pagina - 1; ?>">Anterior</a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            $inicio = max(1, $pagina - 2);
                            $fin = min($total_paginas, $pagina + 2);
                            
                            for ($i = $inicio; $i <= $fin; $i++):
                            ?>
                            <li class="page-item <?php echo ($i == $pagina) ? 'active' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($pagina < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?pagina=<?php echo $pagina + 1; ?>">Siguiente</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
            <?php endif; ?>
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
            <form method="POST" action="?action=actualizar_estado">
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
function irAPagina(pagina) {
    window.location.href = `?pagina=${pagina}`;
}

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
                        <span class="estado-badge estado-${solicitud.estado}">${solicitud.estado_formateado}</span>
                    </p>
                    <p><strong>Tipo:</strong> 
                        <span class="tipo-badge tipo-${solicitud.tipo_solicitud}">${solicitud.tipo_formateado}</span>
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
                            <span class="estado-badge estado-${data.solicitud.estado}">${data.solicitud.estado_formateado}</span>
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

<?php require_once 'includes/footer.php'; ?>
