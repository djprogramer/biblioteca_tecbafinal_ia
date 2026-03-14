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

$pageTitle = 'Gestión de Libros';
require_once 'includes/header.php';
require_once 'includes/database.php';

// Procesar acciones
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'delete' && $id) {
    try {
        // Primero eliminar relaciones con categorías
        $stmt_delete_categorias = $pdo->prepare("DELETE FROM libro_categoria WHERE libro_id = ?");
        $stmt_delete_categorias->execute([$id]);
        
        // Luego eliminar el libro
        $stmt_delete_libro = $pdo->prepare("DELETE FROM libros WHERE id = ?");
        $stmt_delete_libro->execute([$id]);
        
        $_SESSION['message'] = '<div class="alert alert-success">Libro eliminado exitosamente.</div>';
    } catch (Exception $e) {
        $_SESSION['message'] = '<div class="alert alert-danger">Error al eliminar libro: ' . $e->getMessage() . '</div>';
    }
    redirect('gestion_libros.php');
}

// Obtener página actual
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$pagina = max(1, $pagina);
$libros_por_pagina = 20;
$offset = ($pagina - 1) * $libros_por_pagina;

// Obtener total de libros para paginación
$stmt_total = $pdo->prepare("SELECT COUNT(*) as total FROM libros");
$stmt_total->execute();
$total_libros = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];
$total_paginas = ceil($total_libros / $libros_por_pagina);

// Obtener libros con categorías
$stmt = $pdo->prepare("
    SELECT l.id, l.titulo, l.autor, l.anio, l.descripcion, l.link, l.portada, l.created_at,
           GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias
    FROM libros l
    LEFT JOIN libro_categoria lc ON l.id = lc.libro_id
    LEFT JOIN categorias c ON lc.categoria_id = c.id
    GROUP BY l.id
    ORDER BY l.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$libros_por_pagina, $offset]);
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
    border: none !important;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
    border: none !important;
}

/* Tarjetas de libros */
.libro-card {
    height: 100%;
    transition: all 0.3s ease;
    border-radius: 15px;
    overflow: hidden;
}

.libro-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(255, 113, 0, 0.3);
}

.libro-cover {
    height: 200px;
    background-size: cover;
    background-position: center;
    background-color: #f8f9fa;
    position: relative;
}

.libro-cover-placeholder {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
}

.libro-info {
    padding: 15px;
}

.libro-titulo {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.libro-autor {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.libro-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.libro-ano {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.libro-categorias {
    font-size: 0.8rem;
    color: #666;
    margin-bottom: 10px;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.libro-acciones {
    display: flex;
    gap: 8px;
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

.gestion-card, .libro-card {
    animation: fadeInUp 0.6s ease-out;
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
            <li class="breadcrumb-item active">Gestión de Libros</li>
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
                                <i class="fas fa-books me-2"></i>
                                Gestión de Libros
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-cog me-1"></i>
                                Administra el catálogo de libros de la biblioteca
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="subir_libro.php" class="btn btn-light btn-lg me-2">
                                <i class="fas fa-plus me-2"></i>Subir Libro
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
                    <i class="fas fa-book fa-2x text-primary mb-2"></i>
                    <h4><?php echo $total_libros; ?></h4>
                    <small class="text-muted">Total de Libros</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-tags fa-2x text-success mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_categorias = $pdo->prepare("SELECT COUNT(*) as count FROM categorias");
                        $stmt_categorias->execute();
                        echo $stmt_categorias->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Categorías</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_este_anio = $pdo->prepare("SELECT COUNT(*) as count FROM libros WHERE YEAR(created_at) = YEAR(CURRENT_DATE)");
                        $stmt_este_anio->execute();
                        echo $stmt_este_anio->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Este Año</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 h-100 gestion-card">
                <div class="card-body text-center">
                    <i class="fas fa-link fa-2x text-warning mb-2"></i>
                    <h4>
                        <?php 
                        $stmt_con_link = $pdo->prepare("SELECT COUNT(*) as count FROM libros WHERE link IS NOT NULL AND link != ''");
                        $stmt_con_link->execute();
                        echo $stmt_con_link->fetch(PDO::FETCH_ASSOC)['count']; 
                        ?>
                    </h4>
                    <small class="text-muted">Con Link</small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lista de libros -->
    <div class="card border-0 gestion-card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Catálogo de Libros
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <small class="text-muted">
                        Mostrando 
                        <strong><?php echo min(($pagina - 1) * $libros_por_pagina + 1, $total_libros); ?></strong> - 
                        <strong><?php echo min($pagina * $libros_por_pagina, $total_libros); ?></strong> 
                        de <strong><?php echo $total_libros; ?></strong> libros
                    </small>
                </div>
            </div>
            
            <!-- Grid de libros -->
            <div class="row">
                <?php foreach ($libros as $libro): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="card libro-card border-0 shadow-sm">
                        <div class="libro-cover">
                            <?php if ($libro['portada'] && $libro['portada'] !== 'assets/images/default-book.jpg'): ?>
                                <img src="<?php echo htmlspecialchars($libro['portada']); ?>" 
                                     alt="<?php echo htmlspecialchars($libro['titulo']); ?>" 
                                     class="w-100 h-100" style="object-fit: cover;">
                            <?php else: ?>
                                <div class="libro-cover-placeholder">
                                    <i class="fas fa-book fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="libro-info">
                            <h6 class="libro-titulo"><?php echo htmlspecialchars($libro['titulo']); ?></h6>
                            <p class="libro-autor">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($libro['autor']); ?>
                            </p>
                            <div class="libro-meta">
                                <span class="libro-ano"><?php echo htmlspecialchars($libro['anio']); ?></span>
                                <?php if ($libro['link']): ?>
                                    <a href="<?php echo htmlspecialchars($libro['link']); ?>" target="_blank" class="text-primary">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <?php if ($libro['categorias']): ?>
                                <div class="libro-categorias">
                                    <i class="fas fa-tags me-1"></i>
                                    <?php echo htmlspecialchars($libro['categorias']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="libro-acciones">
                                <button type="button" class="btn btn-sm btn-warning" 
                                        onclick="editarLibro(<?php echo $libro['id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" 
                                        onclick="eliminarLibro(<?php echo $libro['id']; ?>, '<?php echo htmlspecialchars($libro['titulo']); ?>')">
                                    <i class="fas fa-trash"></i>
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
                    <nav aria-label="Paginación de libros">
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
            
            <div class="row mt-2">
                <div class="col-12 text-center">
                    <small class="text-muted">
                        Página <strong><?php echo $pagina; ?></strong> de <strong><?php echo $total_paginas; ?></strong> 
                        (<?php echo $libros_por_pagina; ?> libros por página)
                    </small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div> <!-- Cierre de gestion-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
// Variables globales
let librosData = <?php echo json_encode($libros); ?>;

// Ir a página específica
function irAPagina(pagina) {
    window.location.href = 'gestion_libros.php?pagina=' + pagina;
}

// Editar libro
function editarLibro(id) {
    window.location.href = 'editar_libro.php?id=' + id;
}

// Eliminar libro
function eliminarLibro(id, titulo) {
    if (confirm(`¿Estás seguro de eliminar el libro "${titulo}"?`)) {
        window.location.href = `gestion_libros.php?action=delete&id=${id}`;
    }
}
</script>
