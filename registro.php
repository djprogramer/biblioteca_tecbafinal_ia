<?php
session_start();
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validaciones
    if (empty($nombre) || empty($username) || empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 6) {
        $error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El email no es válido.';
    } else {
        try {
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->getConnection();
            
            // Verificar si el usuario ya existe
            $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'El usuario o email ya están registrados.';
            } else {
                // Crear usuario
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("INSERT INTO usuarios (nombre, username, email, password, fecha_registro) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$nombre, $username, $email, $password_hash]);
                
                $success = '¡Cuenta creada exitosamente! Ahora puedes iniciar sesión.';
                
                // Redireccionar después de 2 segundos
                echo "<script>setTimeout(() => window.location.href='login.php', 2000);</script>";
            }
        } catch (Exception $e) {
            $error = 'Error en el sistema. Por favor, intenta más tarde.';
        }
    }
}

$pageTitle = 'Registro';
require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h2>Crear Cuenta</h2>
                        <p class="text-muted">Regístrate para acceder a la biblioteca</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">
                                <i class="fas fa-user me-1"></i>Nombre Completo
                            </label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-at me-1"></i>Usuario
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                            <div class="form-text">Nombre de usuario único para el sistema</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope me-1"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-1"></i>Contraseña
                            </label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   minlength="6" required>
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password_confirm" class="form-label">
                                <i class="fas fa-lock me-1"></i>Confirmar Contraseña
                            </label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                   minlength="6" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-user-plus me-2"></i>
                            Crear Cuenta
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">
                            ¿Ya tienes una cuenta? 
                            <a href="login.php" class="text-primary">Inicia sesión aquí</a>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al inicio
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
