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

// Obtener ID del libro
$libro_id = $_GET['id'] ?? '';
if (empty($libro_id) || !is_numeric($libro_id)) {
    $_SESSION['message'] = '<div class="alert alert-danger">ID de libro no válido.</div>';
    redirect('gestion_libros.php');
}

$pageTitle = 'Editar Libro';
require_once 'includes/header.php';
require_once 'includes/database.php';

// Obtener datos del libro
$stmt = $pdo->prepare("SELECT * FROM libros WHERE id = ?");
$stmt->execute([$libro_id]);
$libro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$libro) {
    $_SESSION['message'] = '<div class="alert alert-danger">Libro no encontrado.</div>';
    redirect('gestion_libros.php');
}

// Obtener categorías disponibles
$stmt_categorias = $pdo->prepare("SELECT id, nombre FROM categorias ORDER BY nombre");
$stmt_categorias->execute();
$categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías del libro
$stmt_libro_categorias = $pdo->prepare("
    SELECT categoria_id 
    FROM libro_categoria 
    WHERE libro_id = ?
");
$stmt_libro_categorias->execute([$libro_id]);
$categorias_libro = $stmt_libro_categorias->fetchAll(PDO::FETCH_COLUMN);
$categorias_seleccionadas = array_column($categorias_libro, 0);

// Procesar formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = sanitize($_POST['titulo'] ?? '');
    $autor = sanitize($_POST['autor'] ?? '');
    $anio = sanitize($_POST['anio'] ?? '');
    $descripcion = sanitize($_POST['descripcion'] ?? '');
    $link = sanitize($_POST['link'] ?? '');
    $categorias_seleccionadas = $_POST['categorias'] ?? [];
    
    // Manejo de imagen de portada
    $portada = $libro['portada']; // Mantener portada actual por defecto
    
    if (isset($_FILES['portada']) && $_FILES['portada']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'images/portadas/';
        
        // Crear directorio si no existe
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['portada']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // Extensiones permitidas
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Generar nombre único
            $filename = uniqid('libro_') . '.' . $file_extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['portada']['tmp_name'], $filepath)) {
                // Eliminar portada anterior si no es la por defecto
                if ($libro['portada'] && $libro['portada'] !== 'assets/images/default-book.jpg' && file_exists($libro['portada'])) {
                    unlink($libro['portada']);
                }
                $portada = $filepath;
            }
        }
    }
    
    // Validaciones básicas
    $errores = [];
    
    if (empty($titulo)) {
        $errores[] = "El título es requerido";
    }
    
    if (empty($autor)) {
        $errores[] = "El autor es requerido";
    }
    
    if (empty($anio)) {
        $errores[] = "El año es requerido";
    } elseif (!is_numeric($anio) || $anio < 1000 || $anio > date('Y')) {
        $errores[] = "El año debe ser un número válido entre 1000 y " . date('Y');
    }
    
    if (empty($descripcion)) {
        $errores[] = "La descripción es requerida";
    }
    
    if (empty($link)) {
        $errores[] = "El link es requerido";
    }
    
    if (empty($categorias_seleccionadas)) {
        $errores[] = "Debes seleccionar al menos una categoría";
    }
    
    if (empty($errores)) {
        try {
            // Iniciar transacción
            $pdo->beginTransaction();
            
            // Actualizar libro
            $stmt_update = $pdo->prepare("
                UPDATE libros 
                SET titulo = ?, autor = ?, anio = ?, descripcion = ?, link = ?, portada = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt_update->execute([
                $titulo,
                $autor,
                $anio,
                $descripcion,
                $link,
                $portada,
                $libro_id
            ]);
            
            // Eliminar categorías anteriores
            $stmt_delete_categorias = $pdo->prepare("DELETE FROM libro_categoria WHERE libro_id = ?");
            $stmt_delete_categorias->execute([$libro_id]);
            
            // Insertar nuevas categorías
            $stmt_insert_categoria = $pdo->prepare("
                INSERT INTO libro_categoria (libro_id, categoria_id) 
                VALUES (?, ?)
            ");
            
            foreach ($categorias_seleccionadas as $categoria_id) {
                $stmt_insert_categoria->execute([$libro_id, $categoria_id]);
            }
            
            // Confirmar transacción
            $pdo->commit();
            
            $_SESSION['message'] = '<div class="alert alert-success">Libro actualizado exitosamente.</div>';
            redirect('gestion_libros.php');
            
        } catch (Exception $e) {
            // Revertir transacción
            $pdo->rollback();
            $_SESSION['message'] = '<div class="alert alert-danger">Error al actualizar libro: ' . $e->getMessage() . '</div>';
        }
    } else {
        $mensaje_errores = '<div class="alert alert-danger"><h5>Errores encontrados:</h5><ul>';
        foreach ($errores as $error) {
            $mensaje_errores .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $mensaje_errores .= '</ul></div>';
        $_SESSION['message'] = $mensaje_errores;
    }
}
?>

<style>
/* Fondo consistente */
.editar-background {
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

.editar-content {
    position: relative;
    z-index: 1;
}

/* Tarjetas con glassmorphism */
.editar-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.editar-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.2);
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

/* Botones */
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

/* Categorías */
.categoria-chip {
    display: inline-block;
    padding: 8px 15px;
    margin: 5px;
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    border-radius: 25px;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.categoria-chip:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 113, 0, 0.3);
}

.categoria-chip.selected {
    background: linear-gradient(135deg, #28a745, #218838);
}

/* Upload de imagen */
.upload-area {
    border: 2px dashed #e9ecef;
    border-radius: 10px;
    padding: 30px;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-area:hover {
    border-color: rgb(255, 113, 0);
    background: rgba(255, 113, 0, 0.05);
}

.upload-area.dragover {
    border-color: rgb(255, 113, 0);
    background: rgba(255, 113, 0, 0.1);
}

/* Preview de imagen */
.image-preview {
    max-width: 200px;
    max-height: 300px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
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

.editar-card {
    animation: fadeInUp 0.6s ease-out;
}
</style>

<div class="editar-background"></div>

<div class="editar-content">

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="gestion_libros.php">Gestión de Libros</a></li>
            <li class="breadcrumb-item active">Editar Libro</li>
        </ol>
    </nav>
    
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 text-white editar-card" style="background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-0">
                                <i class="fas fa-edit me-2"></i>
                                Editar Libro
                            </h2>
                            <p class="mb-0 mt-2">
                                <i class="fas fa-book me-1"></i>
                                Modifica la información del libro seleccionado
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="gestion_libros.php" class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulario -->
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 editar-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-edit me-2"></i>
                        Información del Libro
                    </h5>
                    
                    <form method="POST" enctype="multipart/form-data" id="formEditarLibro">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="titulo" class="form-label">
                                    <i class="fas fa-heading me-1"></i>Título *
                                </label>
                                <input type="text" class="form-control" id="titulo" name="titulo" 
                                       value="<?php echo htmlspecialchars($libro['titulo']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="autor" class="form-label">
                                    <i class="fas fa-user-edit me-1"></i>Autor *
                                </label>
                                <input type="text" class="form-control" id="autor" name="autor" 
                                       value="<?php echo htmlspecialchars($libro['autor']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="anio" class="form-label">
                                    <i class="fas fa-calendar me-1"></i>Año *
                                </label>
                                <input type="number" class="form-control" id="anio" name="anio" 
                                       value="<?php echo htmlspecialchars($libro['anio']); ?>" 
                                       min="1000" max="<?php echo date('Y'); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="link" class="form-label">
                                    <i class="fas fa-link me-1"></i>Link *
                                </label>
                                <input type="url" class="form-control" id="link" name="link" 
                                       value="<?php echo htmlspecialchars($libro['link']); ?>" required>
                                <div class="form-text">URL del libro o recurso</div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="portada" class="form-label">
                                    <i class="fas fa-image me-1"></i>Portada del Libro
                                </label>
                                <input type="file" class="form-control" id="portada" name="portada" accept="image/*">
                                <div class="form-text">Formatos: JPG, PNG, GIF (máx 5MB)</div>
                                <?php if ($libro['portada'] && $libro['portada'] !== 'assets/images/default-book.jpg'): ?>
                                    <small class="text-info d-block mt-1">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Actual: <?php echo basename($libro['portada']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-info-circle me-1"></i>Información
                                </label>
                                <div class="alert alert-info small">
                                    <strong>ID Libro:</strong> <?php echo $libro['id']; ?><br>
                                    <strong>Creado:</strong> <?php echo date('d/m/Y H:i', strtotime($libro['created_at'])); ?><br>
                                    <strong>Actualizado:</strong> <?php echo date('d/m/Y H:i', strtotime($libro['updated_at'])); ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="descripcion" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Descripción *
                            </label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required><?php echo htmlspecialchars($libro['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-tags me-1"></i>Categorías *
                            </label>
                            <div class="d-flex flex-wrap" id="categoriasContainer">
                                <?php foreach ($categorias as $categoria): ?>
                                    <div class="categoria-chip <?php echo in_array($categoria['id'], $categorias_seleccionadas) ? 'selected' : ''; ?>" 
                                         data-categoria-id="<?php echo $categoria['id']; ?>">
                                        <i class="fas fa-tag me-1"></i>
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                        <input type="checkbox" name="categorias[]" value="<?php echo $categoria['id']; ?>" 
                                               <?php echo in_array($categoria['id'], $categorias_seleccionadas) ? 'checked' : ''; ?> 
                                               style="display: none;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Selecciona al menos una categoría para el libro</div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="gestion_libros.php" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 editar-card">
                <div class="card-body">
                    <h5 class="card-title mb-4">
                        <i class="fas fa-eye me-2"></i>
                        Vista Previa
                    </h5>
                    
                    <!-- Preview de portada -->
                    <div class="text-center mb-4">
                        <div id="portadaPreview" class="upload-area">
                            <?php if ($libro['portada'] && $libro['portada'] !== 'assets/images/default-book.jpg'): ?>
                                <img src="<?php echo htmlspecialchars($libro['portada']); ?>" 
                                     class="image-preview" alt="Portada actual">
                            <?php else: ?>
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <p class="mb-0">Arrastra una imagen aquí</p>
                                <small class="text-muted">o haz clic para seleccionar</small>
                            <?php endif; ?>
                        </div>
                        <img id="portadaPreviewImg" class="image-preview mt-3" style="display: none;">
                    </div>
                    
                    <!-- Preview de información -->
                    <div id="infoPreview">
                        <h6 class="text-muted">Información del libro:</h6>
                        <div id="previewContent" class="mt-3">
                            <h6 class="mb-2"><strong><?php echo htmlspecialchars($libro['titulo']); ?></strong></h6>
                            <p class="mb-1"><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($libro['autor']); ?></p>
                            <p class="mb-1"><i class="fas fa-calendar me-1"></i> Año: <?php echo htmlspecialchars($libro['anio']); ?></p>
                            <p class="mb-1"><i class="fas fa-link me-1"></i> <a href="<?php echo htmlspecialchars($libro['link']); ?>" target="_blank"><?php echo htmlspecialchars($libro['link']); ?></a></p>
                            <?php 
                            $categorias_nombres = [];
                            foreach ($categorias as $categoria) {
                                if (in_array($categoria['id'], $categorias_seleccionadas)) {
                                    $categorias_nombres[] = $categoria['nombre'];
                                }
                            }
                            if (!empty($categorias_nombres)):
                            ?>
                            <div class="mb-2">
                                <?php foreach ($categorias_nombres as $cat): ?>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($cat); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            <p class="text-muted small"><?php echo htmlspecialchars(substr($libro['descripcion'], 0, 100)); ?><?php echo strlen($libro['descripcion']) > 100 ? '...' : ''; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div> <!-- Cierre de editar-content -->

<?php require_once 'includes/footer.php'; ?>

<script>
// Variables globales
let categoriasSeleccionadas = <?php echo json_encode($categorias_seleccionadas); ?>;

// Manejo de categorías
document.querySelectorAll('.categoria-chip').forEach(chip => {
    chip.addEventListener('click', function() {
        const checkbox = this.querySelector('input[type="checkbox"]');
        const categoriaId = this.dataset.categoriaId;
        
        checkbox.checked = !checkbox.checked;
        
        if (checkbox.checked) {
            this.classList.add('selected');
            if (!categoriasSeleccionadas.includes(categoriaId)) {
                categoriasSeleccionadas.push(categoriaId);
            }
        } else {
            this.classList.remove('selected');
            categoriasSeleccionadas = categoriasSeleccionadas.filter(id => id !== categoriaId);
        }
        
        actualizarPreview();
    });
});

// Preview de portada
const portadaInput = document.getElementById('portada');
const portadaPreview = document.getElementById('portadaPreview');

portadaPreview.addEventListener('click', () => {
    portadaInput.click();
});

portadaInput.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('portadaPreviewImg');
            previewImg.src = e.target.result;
            previewImg.style.display = 'block';
            portadaPreview.style.display = 'none';
            actualizarPreview();
        };
        reader.readAsDataURL(file);
    }
});

// Actualizar preview de información
function actualizarPreview() {
    const titulo = document.getElementById('titulo').value;
    const autor = document.getElementById('autor').value;
    const anio = document.getElementById('anio').value;
    const descripcion = document.getElementById('descripcion').value;
    const link = document.getElementById('link').value;
    const categoriasNames = Array.from(document.querySelectorAll('.categoria-chip.selected'))
        .map(chip => chip.textContent.trim());
    
    const previewContent = document.getElementById('previewContent');
    
    let html = '';
    
    if (titulo) {
        html += `<h6 class="mb-2"><strong>${titulo}</strong></h6>`;
    }
    
    if (autor) {
        html += `<p class="mb-1"><i class="fas fa-user me-1"></i> ${autor}</p>`;
    }
    
    if (anio) {
        html += `<p class="mb-1"><i class="fas fa-calendar me-1"></i> Año: ${anio}</p>`;
    }
    
    if (link) {
        html += `<p class="mb-1"><i class="fas fa-link me-1"></i> <a href="${link}" target="_blank">${link}</a></p>`;
    }
    
    if (categoriasNames.length > 0) {
        html += `<div class="mb-2">`;
        categoriasNames.forEach(cat => {
            html += `<span class="badge bg-secondary me-1">${cat}</span>`;
        });
        html += `</div>`;
    }
    
    if (descripcion) {
        html += `<p class="text-muted small">${descripcion.substring(0, 100)}${descripcion.length > 100 ? '...' : ''}</p>`;
    }
    
    previewContent.innerHTML = html;
}

// Actualizar preview en tiempo real
document.querySelectorAll('#formEditarLibro input, #formEditarLibro textarea, #formEditarLibro select').forEach(element => {
    element.addEventListener('input', actualizarPreview);
    element.addEventListener('change', actualizarPreview);
});

// Validación del formulario
document.getElementById('formEditarLibro').addEventListener('submit', function(e) {
    if (categoriasSeleccionadas.length === 0) {
        e.preventDefault();
        alert('Debes seleccionar al menos una categoría');
        return false;
    }
    
    const titulo = document.getElementById('titulo').value.trim();
    const autor = document.getElementById('autor').value.trim();
    const anio = document.getElementById('anio').value.trim();
    const descripcion = document.getElementById('descripcion').value.trim();
    const link = document.getElementById('link').value.trim();
    
    if (!titulo || !autor || !anio || !descripcion || !link) {
        e.preventDefault();
        alert('Todos los campos marcados con * son obligatorios');
        return false;
    }
    
    // Validar año
    const anioNum = parseInt(anio);
    if (isNaN(anioNum) || anioNum < 1000 || anioNum > new Date().getFullYear()) {
        e.preventDefault();
        alert('El año debe ser un número válido entre 1000 y ' + new Date().getFullYear());
        return false;
    }
    
    // Validar URL
    try {
        new URL(link);
    } catch {
        e.preventDefault();
        alert('El link debe ser una URL válida (ej: https://ejemplo.com/libro)');
        return false;
    }
    
    return true;
});
</script>
