<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para enviar sugerencias']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'enviar':
        // Procesar nueva sugerencia
        $comentario = sanitize($_POST['comentario'] ?? '');
        
        if (empty($comentario)) {
            echo json_encode(['success' => false, 'message' => 'La sugerencia no puede estar vacía']);
            exit;
        }
        
        try {
            // Insertar sugerencia con el usuario_id de la sesión
            $stmt = $pdo->prepare("
                INSERT INTO sugerencias (usuario_id, comentario, fecha) 
                VALUES (?, ?, NOW())
            ");
            
            $stmt->execute([$_SESSION['usuario_id'], $comentario]);
            
            // Crear ticket
            $sugerencia_id = $pdo->lastInsertId();
            $ticket_id = 'SG-' . str_pad($sugerencia_id, 6, '0', STR_PAD_LEFT);
            
            // Actualizar con ID del ticket (si la columna existe)
            try {
                $stmt_ticket = $pdo->prepare("
                    UPDATE sugerencias 
                    SET ticket_id = ? 
                    WHERE id = ?
                ");
                $stmt_ticket->execute([$ticket_id, $sugerencia_id]);
            } catch (Exception $e) {
                // Si la columna ticket_id no existe, continuamos sin ella
                error_log("Columna ticket_id no existe: " . $e->getMessage());
            }
            
            // Notificar a Super Admins
            try {
                $stmt_admins = $pdo->prepare("
                    SELECT email, nombre 
                    FROM usuarios 
                    WHERE rol = 'Super Admin'
                ");
                $stmt_admins->execute();
                $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($admins as $admin) {
                    // Aquí podrías enviar email a los admins
                    // mail($admin['email'], 'Nueva Sugerencia', "El usuario {$_SESSION['nombre']} ha enviado una nueva sugerencia.");
                }
            } catch (Exception $e) {
                error_log("Error al obtener admins: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Sugerencia enviada exitosamente',
                'ticket_id' => $ticket_id
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al enviar sugerencia: ' . $e->getMessage()]);
        }
        break;
        
    case 'listar':
        // Obtener sugerencias del usuario actual
        try {
            $stmt = $pdo->prepare("
                SELECT s.id, s.comentario, s.fecha, s.estado,
                       CONCAT('SG-', LPAD(s.id, 6, '0')) as ticket_id,
                       CASE 
                           WHEN s.estado = 'pendiente' THEN 'Pendiente'
                           WHEN s.estado = 'revisada' THEN 'En Revisión'
                           WHEN s.estado = 'aprobada' THEN 'Aprobada'
                           WHEN s.estado = 'rechazada' THEN 'Rechazada'
                           ELSE 'Pendiente'
                       END as estado_formateado,
                       u.nombre as nombre_usuario
                FROM sugerencias s
                JOIN usuarios u ON s.usuario_id = u.id
                WHERE s.usuario_id = ?
                ORDER BY s.fecha DESC
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            $sugerencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'sugerencias' => $sugerencias]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener sugerencias: ' . $e->getMessage()]);
        }
        break;
        
    case 'detalle':
        // Obtener detalles de una sugerencia específica
        $sugerencia_id = $_GET['id'] ?? '';
        
        if (empty($sugerencia_id) || !is_numeric($sugerencia_id)) {
            echo json_encode(['success' => false, 'message' => 'ID de sugerencia no válido']);
            exit;
        }
        
        try {
            // Los Super Admins pueden ver cualquier sugerencia, los usuarios solo las suyas
            if ($_SESSION['rol'] === 'Super Admin') {
                $stmt = $pdo->prepare("
                    SELECT s.id, s.comentario, s.fecha, s.estado,
                           CONCAT('SG-', LPAD(s.id, 6, '0')) as ticket_id,
                           CASE 
                               WHEN s.estado = 'pendiente' THEN 'Pendiente'
                               WHEN s.estado = 'revisada' THEN 'En Revisión'
                               WHEN s.estado = 'aprobada' THEN 'Aprobada'
                               WHEN s.estado = 'rechazada' THEN 'Rechazada'
                               ELSE 'Pendiente'
                           END as estado_formateado,
                           u.nombre as nombre_usuario,
                           u.email as email_usuario
                    FROM sugerencias s
                    JOIN usuarios u ON s.usuario_id = u.id
                    WHERE s.id = ?
                ");
                $stmt->execute([$sugerencia_id]);
            } else {
                // Usuarios normales solo pueden ver sus propias sugerencias
                $stmt = $pdo->prepare("
                    SELECT s.id, s.comentario, s.fecha, s.estado,
                           CONCAT('SG-', LPAD(s.id, 6, '0')) as ticket_id,
                           CASE 
                               WHEN s.estado = 'pendiente' THEN 'Pendiente'
                               WHEN s.estado = 'revisada' THEN 'En Revisión'
                               WHEN s.estado = 'aprobada' THEN 'Aprobada'
                               WHEN s.estado = 'rechazada' THEN 'Rechazada'
                               ELSE 'Pendiente'
                           END as estado_formateado,
                           u.nombre as nombre_usuario
                    FROM sugerencias s
                    JOIN usuarios u ON s.usuario_id = u.id
                    WHERE s.id = ? AND s.usuario_id = ?
                ");
                $stmt->execute([$sugerencia_id, $_SESSION['usuario_id']]);
            }
            
            $sugerencia = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sugerencia) {
                echo json_encode(['success' => false, 'message' => 'Sugerencia no encontrada o no tienes permiso para verla']);
                exit;
            }
            
            // Obtener respuestas del admin
            $respuestas = [];
            try {
                $stmt_respuestas = $pdo->prepare("
                    SELECT sr.*, u.nombre as nombre_admin
                    FROM sugerencias_respuestas sr
                    JOIN usuarios u ON sr.admin_id = u.id
                    WHERE sr.sugerencia_id = ? 
                    ORDER BY sr.fecha_respuesta ASC
                ");
                $stmt_respuestas->execute([$sugerencia_id]);
                $respuestas = $stmt_respuestas->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Si hay error en la consulta, continuamos sin respuestas
                error_log("Error al obtener respuestas: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'sugerencia' => $sugerencia,
                'respuestas' => $respuestas
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener sugerencia: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
