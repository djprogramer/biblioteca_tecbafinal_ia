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

$pageTitle = 'Gestión de Usuarios';
require_once 'includes/header.php';
require_once 'includes/database.php';

// Procesar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    // No permitir eliminar al usuario actual
    if ($id == $_SESSION['usuario_id']) {
        $_SESSION['message'] = '<div class="alert alert-danger">No puedes eliminar tu propio usuario.</div>';
    } else {
        try {
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['message'] = '<div class="alert alert-success">Usuario eliminado exitosamente.</div>';
        } catch (Exception $e) {
            $_SESSION['message'] = '<div class="alert alert-danger">Error al eliminar usuario: ' . $e->getMessage() . '</div>';
        }
    }
    redirect('gestion_usuarios.php');
}

// Obtener página actual
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina); // Asegurar que sea al menos 1
$usuarios_por_pagina = 20;
$offset = ($pagina - 1) * $usuarios_por_pagina;

// Obtener total de usuarios para paginación
$stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM usuarios");
$stmt_total->execute();
$total_usuarios = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_usuarios / $usuarios_por_pagina);

// Obtener usuarios con orden jerárquico y paginación
$stmt = $pdo->prepare("
    SELECT id, nombre, email, rol, created_at 
    FROM usuarios 
    ORDER BY 
        CASE 
            WHEN rol = 'Super Admin' THEN 1
            WHEN rol = 'Administrativo' THEN 2
            WHEN rol = 'Docente' THEN 3
            WHEN rol = 'Estudiante' THEN 4
            ELSE 5
        END,
        id ASC
    LIMIT ? OFFSET ?
");
$stmt->execute([$usuarios_por_pagina, $offset]);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

/* Botones */
.btn {
    position: relative !important;
    z-index: 10 !important;
    pointer-events: auto !important;
    cursor: pointer !important;
}

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

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
    border: none !important;
}

/* Badges de rol */
.rol-badge {
    font-size: 0.8rem;
    padding: 0.4rem 0.8rem;
    border-radius: 50px;
    font-weight: 600;
}

.rol-super-admin {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.rol-administrativo {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
}

.rol-docente {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
}

.rol-estudiante {
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
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

.gestion-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Tabla responsiva */
.table-responsive {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 10px;
    padding: 15px;
}

.table {
    margin-bottom: 0;
}

.table th {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    border: none;
    font-weight: 600;
}

.table td {
    vertical-align: middle;
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

/* Modal */
.modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    border-radius: 15px 15px 0 0;
}

.modal-footer {
    border-top: none;
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
            <li class="breadcrumb-item active">Gestión de Usuarios</li>
        </ol>
    </nav>
    
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white gestion-card" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-users-cog me-2"></i>
                                Gestión de Usuarios
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-shield-alt me-1"></i>
                                Panel de administración de usuarios del sistema
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#crearUsuarioModal">
                                <i class="fas fa-user-plus me-2"></i>Crear Usuario
                            </button>
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
                    <i class="fas fa-users fa-2x text-danger mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_super = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'Super Admin'");
                        $stmt_super->execute();
                        echo $stmt_super->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Super Admin</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-user-tie fa-2x text-primary mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_admin = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'Administrativo'");
                        $stmt_admin->execute();
                        echo $stmt_admin->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Administrativo</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-chalkboard-teacher fa-2x text-info mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_docente = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'Docente'");
                        $stmt_docente->execute();
                        echo $stmt_docente->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Docente</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-graduation-cap fa-2x text-success mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_estudiante = $pdo->prepare("SELECT COUNT(*) as count FROM usuarios WHERE rol = 'Estudiante'");
                        $stmt_estudiante->execute();
                        echo $stmt_estudiante->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Estudiante</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabla de usuarios -->
    <div class="card border-0 gestion-card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Usuarios
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Mostrando 
                        <strong><?php echo min(($pagina - 1) * $usuarios_por_pagina + 1, $total_usuarios); ?></strong> - 
                        <strong><?php echo min($pagina * $usuarios_por_pagina, $total_usuarios); ?></strong> 
                        de <strong><?php echo $total_usuarios; ?></strong> usuarios
                    </small>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Fecha de Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><strong><?php echo $usuario['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td>
                                <span class="rol-badge rol-<?php echo strtolower(str_replace(' ', '-', $usuario['rol'])); ?>">
                                    <?php echo htmlspecialchars($usuario['rol']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-warning" 
                                            onclick="editarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>', '<?php echo htmlspecialchars($usuario['email']); ?>', '<?php echo htmlspecialchars($usuario['rol']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($usuario['id'] != $_SESSION['usuario_id']): ?>
                                    <button type="button" class="btn btn-sm btn-danger" 
                                            onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['nombre']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="No puedes eliminarte a ti mismo">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                    <nav aria-label="Paginación de usuarios">
                        <ul class="pagination pagination-sm justify-content-end mb-0">
                            <!-- Primera página -->
                            <li class="page-item <?php echo ($pagina == 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=1" tabindex="-1" aria-disabled="<?php echo ($pagina == 1) ? 'true' : 'false'; ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            
                            <!-- Página anterior -->
                            <li class="page-item <?php echo ($pagina == 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?pagina=<?php echo max(1, $pagina - 1); ?>" tabindex="-1" aria-disabled="<?php echo ($pagina == 1) ? 'true' : 'false'; ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                            
                            <!-- Páginas numeradas -->
                            <?php
                            $rango = 2; // Mostrar 2 páginas antes y después
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
            
            <div class="row mt-2">
                <div class="col-12 text-center">
                    <small class="text-muted">
                        Página <strong><?php echo $pagina; ?></strong> de <strong><?php echo $total_paginas; ?></strong> 
                        (<?php echo $usuarios_por_pagina; ?> usuarios por página)
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div> <!-- Cierre de gestion-content -->

<!-- Modal Crear Usuario -->
<div class="modal fade" id="crearUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Crear Nuevo Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="crearUsuarioForm">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="6" required>
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="">Seleccionar rol...</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Administrativo">Administrativo</option>
                            <option value="Docente">Docente</option>
                            <option value="Estudiante">Estudiante</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="crearUsuario()">Crear Usuario</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>Editar Usuario
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editarUsuarioForm">
                    <input type="hidden" id="editar_id" name="id">
                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="editar_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editar_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editar_rol" class="form-label">Rol</label>
                        <select class="form-select" id="editar_rol" name="rol" required>
                            <option value="">Seleccionar rol...</option>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Administrativo">Administrativo</option>
                            <option value="Docente">Docente</option>
                            <option value="Estudiante">Estudiante</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cambiar_password" name="cambiar_password">
                            <label class="form-check-label" for="cambiar_password">
                                Cambiar contraseña
                            </label>
                        </div>
                    </div>
                    <div class="mb-3" id="password_fields" style="display: none;">
                        <label for="editar_password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="editar_password" name="password" minlength="6">
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="actualizarUsuario()">Actualizar Usuario</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
// Variables globales
let usuariosData = <?php echo json_encode($usuarios); ?>;

// Ir a página específica
function irAPagina(pagina) {
    window.location.href = 'gestion_usuarios.php?pagina=' + pagina;
}

// Crear usuario
function crearUsuario() {
    const form = document.getElementById('crearUsuarioForm');
    const formData = new FormData(form);
    
    // Validaciones básicas
    if (!formData.get('nombre') || !formData.get('email') || !formData.get('password') || !formData.get('rol')) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    if (formData.get('password').length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres');
        return;
    }
    
    fetch('api/usuarios_crud.php?action=create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Usuario creado exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al crear usuario');
    });
}

// Editar usuario
function editarUsuario(id, nombre, email, rol) {
    document.getElementById('editar_id').value = id;
    document.getElementById('editar_nombre').value = nombre;
    document.getElementById('editar_email').value = email;
    document.getElementById('editar_rol').value = rol;
    
    const modal = new bootstrap.Modal(document.getElementById('editarUsuarioModal'));
    modal.show();
}

// Actualizar usuario
function actualizarUsuario() {
    const form = document.getElementById('editarUsuarioForm');
    const formData = new FormData(form);
    
    if (!formData.get('nombre') || !formData.get('email') || !formData.get('rol')) {
        alert('Por favor completa todos los campos');
        return;
    }
    
    if (document.getElementById('cambiar_password').checked && formData.get('password').length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres');
        return;
    }
    
    fetch('api/usuarios_crud.php?action=update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Usuario actualizado exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar usuario');
    });
}

// Eliminar usuario
function eliminarUsuario(id, nombre) {
    if (confirm(`¿Estás seguro de eliminar al usuario "${nombre}"?`)) {
        window.location.href = `gestion_usuarios.php?action=delete&id=${id}`;
    }
}

// Mostrar/ocultar campos de contraseña
document.getElementById('cambiar_password').addEventListener('change', function() {
    const passwordFields = document.getElementById('password_fields');
    if (this.checked) {
        passwordFields.style.display = 'block';
        document.getElementById('editar_password').required = true;
    } else {
        passwordFields.style.display = 'none';
        document.getElementById('editar_password').required = false;
        document.getElementById('editar_password').value = '';
    }
});
</script>
