-- Tabla para configurar límites de uso de IA por rol
CREATE TABLE IF NOT EXISTS ai_role_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    daily_limit INT NOT NULL DEFAULT 50,
    hourly_limit INT NOT NULL DEFAULT 10,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_active (is_active)
);

-- Insertar límites por defecto según los requisitos CORRECTOS
INSERT INTO ai_role_limits (role_name, daily_limit, hourly_limit, description) VALUES
('Super Admin', 999999, 999999, 'Super Administrador - Sin límites de uso'),
('Administrativo', 50, 10, 'Personal administrativo - 50 peticiones diarias, 10 por hora'),
('Docente', 30, 6, 'Docentes - 30 peticiones diarias, 6 por hora'),
('Estudiante', 15, 3, 'Estudiantes - 15 peticiones diarias, 3 por hora')
ON DUPLICATE KEY UPDATE 
    daily_limit = VALUES(daily_limit),
    hourly_limit = VALUES(hourly_limit),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

-- NOTA: No se modifica la tabla usuarios ya que tiene los roles correctos:
-- ENUM('Administrativo','Docente','Estudiante','Super Admin')

-- Vista para consultar límites activos
CREATE OR REPLACE VIEW ai_active_limits AS
SELECT 
    role_name,
    daily_limit,
    hourly_limit,
    description
FROM ai_role_limits 
WHERE is_active = TRUE;
