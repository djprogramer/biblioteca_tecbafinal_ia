<?php
session_start();
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$error = '';

// Prueba: verificar si PHP funciona
error_log("Login.php cargado - Método: " . $_SERVER['REQUEST_METHOD']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Depuración: mostrar datos recibidos
    error_log("Datos POST recibidos: " . print_r($_POST, true));
    
    $email = sanitize($_POST['email'] ?? '');
    $password = sanitize($_POST['password'] ?? '');
    
    error_log("Email: '$email'");
    error_log("Password length: " . strlen($password));
    error_log("Email empty: " . (empty($email) ? 'Sí' : 'No'));
    error_log("Password empty: " . (empty($password) ? 'Sí' : 'No'));
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, completa todos los campos.';
        error_log("Error: Campos vacíos - Email vacío: " . (empty($email) ? 'Sí' : 'No') . ", Password vacío: " . (empty($password) ? 'Sí' : 'No'));
    } else {
        try {
            require_once 'includes/database.php';
            
            error_log("Conexión a BD exitosa");
            
            // Buscar usuario por email
            $stmt = $pdo->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Usuario encontrado: " . ($usuario ? "Sí" : "No"));
            if ($usuario) {
                error_log("Rol del usuario: " . $usuario['rol']);
            }
                
                $password_valid = false; // Inicializar variable
                
                if ($usuario) {
                    // Verificar si la contraseña está hasheada o en texto plano
                    $hash_info = password_get_info($usuario['password']);
                    $is_hashed = $hash_info['algo'] > 0;
                    
                    error_log("Hash info: " . json_encode($hash_info));
                    error_log("¿Está hasheada?: " . ($is_hashed ? 'Sí' : 'No'));
                    
                    if ($is_hashed) {
                        // Contraseña hasheada - usar password_verify
                        error_log("Usando password_verify para contraseña hasheada");
                        if (password_verify($password, $usuario['password'])) {
                            error_log("Contraseña hasheada verificada correctamente");
                            $login_success = true;
                        } else {
                            error_log("Contraseña hasheada incorrecta");
                            $login_success = false;
                        }
                    } else {
                        // Contraseña en texto plano - comparación directa
                        error_log("Usando comparación directa para contraseña en texto plano");
                        if ($usuario['password'] === $password) {
                            error_log("Contraseña en texto plano coincide");
                            $login_success = true;
                        } else {
                            error_log("Contraseña en texto plano incorrecta");
                            $login_success = false;
                        }
                    }
                    
                    if ($login_success) {
                        // Login exitoso
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['nombre'] = $usuario['nombre'];
                        $_SESSION['email'] = $usuario['email'];
                        $_SESSION['rol'] = $usuario['rol'] ?? 'usuario';
                        
                        error_log("Login exitoso, redirigiendo a index.php");
                        redirect('index.php');
                    } else {
                        $error = 'Usuario o contraseña incorrectos.';
                        error_log("Login fallido");
                    }
                } else {
                    $error = 'Usuario o contraseña incorrectos.';
                    error_log("Usuario no encontrado");
                }
        } catch (Exception $e) {
            $error = 'Error en el sistema: ' . $e->getMessage();
            error_log("Excepción: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }
    }
}

$pageTitle = 'Login';
require_once 'includes/header_simple_login.php';
?>

<!-- CSS del footer_simple.php para asegurar el mismo aspecto -->
<style>
/* Estilos del footer_simple.php */
.contact-info {
    display: flex;
    align-items: center;
}

.contact-info i {
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    line-height: 1;
}

.contact-list {
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
}

.contact-list li {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.75rem !important;
    margin-bottom: 0.75rem !important;
    min-height: 32px !important;
}

.contact-icon {
    width: 20px !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    font-size: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.contact-list span {
    text-align: left !important;
    line-height: 1.4 !important;
}

.footer-social-icons {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 1rem !important;
    margin-top: 1rem !important;
}

.footer-social-icons a {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background-color: rgba(255, 255, 255, 0.1) !important;
    transition: all 0.3s ease !important;
    color: white !important;
    text-decoration: none !important;
}

.footer-social-icons a:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    transform: scale(1.1) !important;
    color: rgb(255, 113, 0) !important;
}

/* Forzar footer full-width */
footer {
    width: 100% !important;
    margin-top: auto !important;
}

footer .bg-dark {
    background-color: #212529 !important;
}

footer .container {
    max-width: 100% !important;
}

footer h5 {
    color: white !important;
    font-weight: bold !important;
}

footer .text-white {
    color: white !important;
}

footer .text-center {
    text-align: center !important;
}
</style>

<div class="login-container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-lg-5 col-md-7">
            <div class="login-card">
                <div class="text-center mb-4">
                    <img src="images/tecba-logo.png" alt="TECBA" class="login-logo mb-3">
                    <h2 class="login-title">Biblioteca TECBA</h2>
                    <p class="login-subtitle">¡Bienvenido a la Biblioteca Virtual TECBA!</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-modern">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group-modern mb-4">
                        <div class="input-group-modern">
                            <span class="input-icon">
                                <i class="fas fa-user"></i>
                            </span>
                            <input type="email" class="form-input-modern" id="email" name="email" 
                                   placeholder="Correo electrónico" required>
                            <label class="form-label-modern" for="email">Correo Electrónico</label>
                        </div>
                    </div>
                    
                    <div class="form-group-modern mb-4">
                        <div class="input-group-modern">
                            <span class="input-icon">
                                <i class="fas fa-lock"></i>
                            </span>
                            <input type="password" class="form-input-modern" id="password" name="password" 
                                   placeholder="Contraseña" required>
                            <label class="form-label-modern" for="password">Contraseña</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login-modern w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Iniciar Sesión
                    </button>
                </form>
                
                <div class="text-center mt-4">
                    <a href="#" class="link-modern">¿Olvidaste tu contraseña?</a>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="invitado.php" class="btn-guest-modern" id="guest-login-btn">
                    <i class="fas fa-eye me-2"></i>
                    Ver como invitado
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos modernos para el login */
.login-container {
    min-height: 100vh;
    position: relative;
    overflow: hidden;
    margin: 0;
    padding: 0;
}

.login-container::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('../images/wallpaperbetter.jpg') center/cover no-repeat;
    opacity: 0.3;
    z-index: -1;
    background-attachment: fixed;
}

.login-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(255, 113, 0, 0.15);
    border: 1px solid rgba(255, 113, 0, 0.1);
    padding: 3rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.login-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 70px rgba(255, 113, 0, 0.2);
}

.login-logo {
    height: 80px;
    filter: drop-shadow(0 4px 8px rgba(255, 113, 0, 0.3));
    transition: transform 0.3s ease;
    animation: logoFloat 3s ease-in-out infinite;
}

.login-logo:hover {
    transform: scale(1.05);
    animation-play-state: paused;
}

@keyframes logoFloat {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

/* Estilos exactos del footer_simple.php */
.contact-info {
    display: flex;
    align-items: center;
}

.contact-info i {
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    line-height: 1;
}

.contact-list {
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
}

.contact-list li {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.75rem !important;
    margin-bottom: 0.75rem !important;
    min-height: 32px !important;
}

.contact-icon {
    width: 20px !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    font-size: 14px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.contact-list span {
    text-align: left !important;
    line-height: 1.4 !important;
}

.footer-social-icons {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 1rem !important;
    margin-top: 1rem !important;
}

.footer-social-icons a {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 36px !important;
    height: 36px !important;
    border-radius: 50% !important;
    background-color: rgba(255, 255, 255, 0.1) !important;
    transition: all 0.3s ease !important;
    color: white !important;
    text-decoration: none !important;
}

.footer-social-icons a:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    transform: scale(1.1) !important;
    color: rgb(255, 113, 0) !important;
}

/* Forzar footer full-width */
footer {
    width: 100% !important;
    margin-top: auto !important;
}

footer .bg-dark {
    background-color: #212529 !important;
}

footer .container {
    max-width: 100% !important;
}

footer h5 {
    color: white !important;
    font-weight: bold !important;
}

footer .text-white {
    color: white !important;
}

footer .text-center {
    text-align: center !important;
}

.login-title {
    color: rgb(255, 113, 0);
    font-weight: 700;
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.login-subtitle {
    color: #666;
    font-weight: 500;
    margin-bottom: 2rem;
}

.alert-modern {
    border-radius: 10px;
    border: none;
    padding: 1rem;
    background: rgba(220, 53, 69, 0.1);
    color: #721c24;
    backdrop-filter: blur(10px);
}

.input-group-modern {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: rgb(255, 113, 0);
    z-index: 2;
    transition: color 0.3s ease;
}

.form-input-modern {
    width: 100%;
    padding: 15px 15px 15px 45px;
    border: 2px solid rgba(255, 113, 0, 0.2);
    border-radius: 10px;
    font-size: 16px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(10px);
}

.form-input-modern:focus {
    outline: none;
    border-color: rgb(255, 113, 0);
    box-shadow: 0 0 0 3px rgba(255, 113, 0, 0.1);
    background: white;
}

.form-input-modern:focus + .form-label-modern,
.form-input-modern:not(:placeholder-shown) + .form-label-modern {
    top: -10px;
    left: 10px;
    font-size: 12px;
    color: rgb(255, 113, 0);
    background: white;
    padding: 0 5px;
    z-index: 3;
}

.form-label-modern {
    position: absolute;
    left: 45px;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 16px;
    pointer-events: none;
    transition: all 0.3s ease;
    background: transparent;
    z-index: 2;
    opacity: 1;
}

.form-input-modern:focus + .form-label-modern,
.form-input-modern:not(:placeholder-shown) + .form-label-modern {
    top: -10px;
    left: 10px;
    font-size: 12px;
    color: rgb(255, 113, 0);
    background: white;
    padding: 0 5px;
    z-index: 4;
    opacity: 1;
}

/* Ocultar placeholder cuando el label está flotando */
.form-input-modern:focus::placeholder,
.form-input-modern:not(:placeholder-shown)::placeholder {
    color: transparent;
    opacity: 0;
}

/* Asegurarse de que el placeholder sea visible solo cuando el input está vacío */
.form-input-modern:placeholder-shown + .form-label-modern {
    opacity: 0;
    pointer-events: none;
}

.form-input-modern:not(:placeholder-shown) + .form-label-modern {
    opacity: 1;
    pointer-events: none;
}

.btn-login-modern {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    border: none;
    padding: 15px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn-login-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s ease;
}

.btn-login-modern:hover::before {
    left: 100%;
}

.btn-login-modern:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.3);
}

.link-modern {
    color: rgb(255, 113, 0);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    position: relative;
}

.link-modern::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: rgb(255, 113, 0);
    transition: width 0.3s ease;
}

.link-modern:hover::after {
    width: 100%;
}

.btn-guest-modern {
    background: transparent;
    color: rgb(255, 113, 0);
    border: 2px solid rgb(255, 113, 0);
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-guest-modern:hover {
    background: rgb(255, 113, 0);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 113, 0, 0.3);
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

.login-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .login-card {
        margin: 1rem;
        padding: 2rem;
    }
    
    .login-logo {
        height: 60px;
    }
    
    .login-title {
        font-size: 1.5rem;
    }
}
</style>

<!-- Script para mensaje emergente -->
<script>
document.getElementById('guest-login-btn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Redirigir a invitado.php
    window.location.href = 'invitado.php';
});

// Función para mostrar mensaje cuando se selecciona una categoría
function showGuestMessage() {
    // Crear modal
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeIn 0.3s ease;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 90%;
        text-align: center;
        animation: slideInUp 0.3s ease;
    `;
    
    modalContent.innerHTML = `
        <h3 style="color: rgb(255, 113, 0); margin-bottom: 1rem;">
            <i class="fas fa-info-circle me-2"></i>
            Acceso Limitado
        </h3>
        <p style="color: #666; margin-bottom: 1.5rem; line-height: 1.6;">
            Para ver los libros de esta categoría, únete a nuestra institución. 
            Como invitado solo puedes navegar por las categorías disponibles.
        </p>
        <div style="display: flex; gap: 1rem; justify-content: center;">
            <button onclick="this.closest('.modal').remove()" 
                    style="background: rgb(255, 113, 0); color: white; border: none; 
                           padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-user-plus me-2"></i>
                Registrarse
            </button>
            <button onclick="this.closest('.modal').remove()" 
                    style="background: #6c757d; color: white; border: none; 
                           padding: 10px 20px; border-radius: 8px; cursor: pointer;">
                <i class="fas fa-times me-2"></i>
                Cerrar
            </button>
        </div>
    `;
    
    modal.appendChild(modalContent);
    modal.className = 'modal';
    document.body.appendChild(modal);
    
    // Cerrar al hacer clic fuera del contenido
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.remove();
        }
    });
}

// Detectar si estamos en categorías y mostrar el mensaje
if (window.location.pathname.includes('categorias.php')) {
    // Añadir listener a los enlaces de categorías
    setTimeout(() => {
        const categoryLinks = document.querySelectorAll('a[href*="categoria.php"]');
        categoryLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                showGuestMessage();
            });
        });
    }, 1000);
}
</script>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInUp {
    from { 
        opacity: 0;
        transform: translateY(30px);
    }
    to { 
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php require_once 'includes/footer_simple.php'; ?>
