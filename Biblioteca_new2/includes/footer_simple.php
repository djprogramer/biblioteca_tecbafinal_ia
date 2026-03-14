<!-- Footer con información TECBA -->
<footer class="bg-dark text-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <!-- Información de Cochabamba -->
            <div class="col-lg-5 col-md-6 mb-4 mb-md-0">
                <h5 class="mb-3 text-center text-white">Cochabamba</h5>
                <ul class="list-unstyled text-center contact-list">
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-phone-alt me-3 text-white contact-icon"></i>
                        <span class="text-white">4 4500614</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fab fa-whatsapp me-3 text-white contact-icon"></i>
                        <span class="text-white">+591 67408813</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-map-marker-alt me-3 text-white contact-icon"></i>
                        <span class="text-white">Calle Pasteur #260 entre Sucre y Bolívar</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-envelope me-3 text-white contact-icon"></i>
                        <span class="text-white">informacion@tecba.edu.bo</span>
                    </li>
                </ul>
                
                <!-- Redes Sociales Cochabamba -->
                <div class="d-flex gap-3 mt-4 justify-content-center">
                    <a href="https://www.facebook.com/TecbaCbba" target="_blank" class="text-white fs-4">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://www.instagram.com/tecba.cochabamba/" target="_blank" class="text-white fs-4">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.tiktok.com/@tecba.cochabamba" target="_blank" class="text-white fs-4">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://www.youtube.com/@tecba.cochabamba" target="_blank" class="text-white fs-4">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="https://www.linkedin.com/company/tecbabolvia" target="_blank" class="text-white fs-4">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
            
            <!-- Información de Sucre -->
            <div class="col-lg-5 col-md-6">
                <h5 class="mb-3 text-center text-white">Sucre</h5>
                <ul class="list-unstyled text-center contact-list">
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-phone-alt me-3 text-white contact-icon"></i>
                        <span class="text-white">4 6453697</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fab fa-whatsapp me-3 text-white contact-icon"></i>
                        <span class="text-white">+591 76123189</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-map-marker-alt me-3 text-white contact-icon"></i>
                        <span class="text-white">Calle Luis Paz Arce 202 - Av. Hernando Siles</span>
                    </li>
                    <li class="mb-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-envelope me-3 text-white contact-icon"></i>
                        <span class="text-white">tecdigital.ch@tecba.edu.bo</span>
                    </li>
                </ul>
                
                <!-- Redes Sociales Sucre -->
                <div class="d-flex gap-3 mt-4 justify-content-center">
                    <a href="https://www.facebook.com/tecbasucre" target="_blank" class="text-white fs-4">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="https://www.instagram.com/tecba.sucre/" target="_blank" class="text-white fs-4">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://www.tiktok.com/@tecba_sucre" target="_blank" class="text-white fs-4">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="https://www.youtube.com/@tecbasucre5848" target="_blank" class="text-white fs-4">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="https://www.linkedin.com/company/tecnol%C3%B3gico-boliviano-alem%C3%A1n/" target="_blank" class="text-white fs-4">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Copyright -->
        <div class="row mt-4">
            <div class="col-12">
                <hr class="bg-light">
                <div class="text-center mt-4">
                    <p class="mb-0 text-white">&copy; <?php echo date('Y'); ?> Biblioteca TECBA. Todos los derechos reservados.</p>
                    <p class="mb-0 text-white">Desarrollado por ZIS</p>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
.footer-social-icons {
    display: flex;
    justify-content: center;
    gap: 1rem;
    margin-top: 1rem;
}

.footer-social-icons a {
    color: #fff;
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.footer-social-icons a:hover {
    color: #fff;
    transform: scale(1.1);
}

.contact-info {
    margin-bottom: 1.5rem;
}

.contact-info h5 {
    color: #fff;
    font-weight: 600;
    margin-bottom: 1rem;
}

.contact-info ul {
    list-style: none;
    padding: 0;
}

.contact-info li {
    color: #fff;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
}

.contact-info i {
    width: 20px;
    text-align: center;
    flex-shrink: 0;
    line-height: 1;
}

/* Estilos mejorados para alineación perfecta */
.contact-list {
    padding: 0;
    margin: 0;
}

.contact-list li {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    min-height: 32px;
}

.contact-icon {
    width: 20px !important;
    text-align: center !important;
    flex-shrink: 0 !important;
    font-size: 14px;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
}

.contact-list span {
    text-align: left;
    line-height: 1.4;
}

/* Alineación perfecta para redes sociales */
.footer-social-icons {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 1rem !important;
    margin-top: 1rem;
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
}

.footer-social-icons a:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    transform: scale(1.1) !important;
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- JavaScript Personalizado -->
<script src="js/main.js"></script>
</body>
</html>
