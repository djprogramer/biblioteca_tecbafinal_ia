<?php
session_start();
require_once 'includes/functions.php';

// Si ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    redirect('index.php');
}

$pageTitle = 'Acceso Invitado';
require_once 'includes/header_simple.php';
?>

<div class="invitado-container">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="invitado-card">
                    <div class="text-center mb-4">
                        <img src="images/tecba-logo.png" alt="TECBA" class="login-logo mb-3" style="max-width: 250px; height: auto; display: block; margin: 0 auto;">
                        <h2 class="login-title">Acceso Invitado</h2>
                        <p class="login-subtitle">Explora nuestra biblioteca como invitado</p>
                    </div>
                    
                    <div class="invitado-content">
                        <div class="row justify-content-center">
                            <div class="col-md-6 mb-4">
                                <div class="feature-card-invited">
                                    <div class="text-center">
                                        <i class="fas fa-list feature-icon-invited"></i>
                                        <h4>Categorías</h4>
                                        <p>Explora todas las categorías de libros disponibles</p>
                                        <a href="invitado/categorias.php" class="btn-feature-invited">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            Ver Categorías
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-4">
                                <div class="feature-card-invited">
                                    <div class="text-center">
                                        <i class="fas fa-info-circle feature-icon-invited"></i>
                                        <h4>Información</h4>
                                        <p>Conoce más sobre nuestra biblioteca</p>
                                        <button class="btn-feature-invited" onclick="showInfoModal()">
                                            <i class="fas fa-arrow-right me-2"></i>
                                            Más Información
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <div class="upgrade-card">
                            <h5><i class="fas fa-lock me-2"></i>Acceso Limitado</h5>
                            <p>Como invitado tienes acceso limitado a nuestro contenido. Para acceso completo a todos los recursos:</p>
                            <ul class="text-start">
                                <li><i class="fas fa-check text-success me-2"></i>Descarga ilimitada de libros</li>
                                <li><i class="fas fa-check text-success me-2"></i>Acceso a recursos premium</li>
                                <li><i class="fas fa-check text-success me-2"></i>Guardado de favoritos</li>
                                <li><i class="fas fa-check text-success me-2"></i>Notificaciones personalizadas</li>
                            </ul>
                            <div class="mt-3">
                                <a href="https://api.whatsapp.com/send?phone=59167408813&text=Hola%20TECBA,%20solicito%20m%C3%A1s%20informaci%C3%B3n%20de%20sus%20carreras.%20Mi%20nombre%20es" 
                                   target="_blank" class="btn-upgrade">
                                    <i class="fab fa-whatsapp me-2"></i>
                                    Más información
                                </a>
                                <a href="index.php" class="btn-back">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver al Inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos para sección de invitados */
.invitado-container {
    background: linear-gradient(135deg, 
        rgba(255, 113, 0, 0.1) 0%, 
        rgba(255, 255, 255, 0.95) 50%, 
        rgba(255, 113, 0, 0.05) 100%);
    min-height: 100vh;
    padding: 2rem 0;
    position: relative;
}

.invitado-container::before {
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

.invitado-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(255, 113, 0, 0.15);
    border: 1px solid rgba(255, 113, 0, 0.1);
    padding: 3rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin: 2rem 0;
}

.invitado-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 70px rgba(255, 113, 0, 0.2);
}

.feature-card-invited {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 113, 0, 0.1);
    height: 100%;
}

.feature-card-invited:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(255, 113, 0, 0.15);
    border-color: rgba(255, 113, 0, 0.3);
}

.feature-icon-invited {
    font-size: 3rem;
    color: rgb(255, 113, 0);
    margin-bottom: 1rem;
    transition: transform 0.3s ease;
}

.feature-card-invited:hover .feature-icon-invited {
    transform: scale(1.1);
}

.feature-card-invited h4 {
    color: rgb(255, 113, 0);
    margin-bottom: 1rem;
    font-weight: 600;
}

.feature-card-invited p {
    color: #666;
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.btn-feature-invited {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-feature-invited:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 113, 0, 0.3);
}

.upgrade-card {
    background: rgba(255, 255, 255, 0.95);
    border-radius: 15px;
    padding: 2rem;
    border: 2px solid rgba(255, 113, 0, 0.2);
    margin-top: 2rem;
}

.upgrade-card h5 {
    color: rgb(255, 113, 0);
    font-weight: 600;
    margin-bottom: 1rem;
}

.upgrade-card ul {
    list-style: none;
    padding: 0;
    margin-bottom: 1.5rem;
}

.upgrade-card li {
    padding: 0.5rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.btn-upgrade {
    background: rgb(255, 113, 0);
    color: white;
    border: none;
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
    margin-right: 1rem;
}

.btn-upgrade:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 113, 0, 0.3);
}

.btn-back {
    background: transparent;
    color: rgb(255, 113, 0);
    border: 2px solid rgb(255, 113, 0);
    padding: 12px 25px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-block;
}

.btn-back:hover {
    background: rgb(255, 113, 0);
    color: white;
    transform: translateY(-2px);
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

.invitado-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .invitado-card {
        margin: 1rem;
        padding: 2rem;
    }
    
    .feature-card-invited {
        margin-bottom: 1rem;
        padding: 1.5rem;
    }
}
</style>

<!-- Modal de información -->
<div id="infoModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-info-circle me-2"></i>Información de la Biblioteca</h3>
            <button class="modal-close" onclick="closeInfoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="info-section">
                <h4><i class="fas fa-book-open me-2"></i>Sobre Nosotros</h4>
                <p>Biblioteca TECBA es una plataforma digital que ofrece acceso a recursos educativos de alta calidad para estudiantes y profesionales. Nuestra misión es facilitar el aprendizaje mediante tecnología innovadora.</p>
            </div>
            <div class="info-section">
                <h4><i class="fas fa-users me-2"></i>Comunidad</h4>
                <p>Formamos parte de una comunidad educativa comprometida con la excelencia académica y el desarrollo integral de nuestros estudiantes.</p>
            </div>
            <div class="info-section">
                <h4><i class="fas fa-star me-2"></i>Recursos</h4>
                <p>Accede a miles de libros académicos, artículos de investigación, materiales de estudio y recursos multimedia actualizados constantemente.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeInfoModal()">
                <i class="fas fa-check me-2"></i>
                Entendido
            </button>
        </div>
    </div>
</div>

<script>
function showInfoModal() {
    document.getElementById('infoModal').style.display = 'flex';
}

function closeInfoModal() {
    document.getElementById('infoModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera
document.getElementById('infoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeInfoModal();
    }
});
</script>

<style>
/* Estilos del modal */
.modal {
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
}

.modal-content {
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    max-width: 600px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    animation: slideInUp 0.3s ease;
}

.modal-header {
    background: linear-gradient(135deg, rgb(255, 113, 0), rgb(220, 90, 0));
    color: white;
    padding: 1.5rem;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.5rem;
}

.modal-close {
    background: transparent;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.modal-close:hover {
    transform: scale(1.1);
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

.modal-body {
    padding: 2rem;
}

.info-section {
    margin-bottom: 2rem;
}

.info-section h4 {
    color: rgb(255, 113, 0);
    margin-bottom: 1rem;
    font-weight: 600;
}

.info-section p {
    color: #666;
    line-height: 1.6;
}

.modal-footer {
    padding: 1.5rem 2rem;
    text-align: center;
    border-top: 1px solid #eee;
}

.btn-primary {
    background: rgb(255, 113, 0);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: rgb(220, 90, 0);
    transform: translateY(-1px);
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
