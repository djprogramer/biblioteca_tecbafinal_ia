<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para realizar esta acción.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'enviar':
        // Procesar nueva solicitud de subida de libro
        $titulo_solicitado = sanitize($_POST['titulo_solicitado'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $tipo_solicitud = sanitize($_POST['tipo_solicitud'] ?? 'sugerencia_compra');
        $libro_id = sanitize($_POST['libro_id'] ?? null);
        
        if (empty($titulo_solicitado)) {
            echo json_encode(['success' => false, 'message' => 'El título del libro es obligatorio.']);
            exit;
        }
        
        if (empty($tipo_solicitud) || !in_array($tipo_solicitud, ['sugerencia_compra', 'donacion', 'digitalizacion', 'otro'])) {
            echo json_encode(['success' => false, 'message' => 'El tipo de solicitud no es válido.']);
            exit;
        }
        
        try {
            // Generar código de solicitud único
            $codigo_solicitud = 'SUL-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            // Insertar solicitud
            $stmt = $pdo->prepare("
                INSERT INTO solicitudes_libros (codigo_solicitud, usuario_id, libro_id, titulo_solicitado, descripcion, tipo_solicitud, estado) 
                VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
            ");
            
            $stmt->execute([
                $codigo_solicitud,
                $_SESSION['usuario_id'],
                $libro_id ?: null,
                $titulo_solicitado,
                $descripcion,
                $tipo_solicitud
            ]);
            
            $solicitud_id = $pdo->lastInsertId();
            
            // Notificar a Super Admins (opcional - podrías implementar email)
            try {
                $stmt_admins = $pdo->prepare("
                    SELECT email, nombre 
                    FROM usuarios 
                    WHERE rol = 'Super Admin'
                ");
                $stmt_admins->execute();
                $admins = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);
                
                // Aquí podrías enviar emails a los admins
                foreach ($admins as $admin) {
                    // mail($admin['email'], 'Nueva Solicitud de Subida de Libro', "El usuario {$_SESSION['nombre']} ha solicitado subir: $titulo_solicitado");
                }
            } catch (Exception $e) {
                error_log("Error al obtener admins para notificación: " . $e->getMessage());
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Solicitud de subida de libro enviada exitosamente',
                'codigo_solicitud' => $codigo_solicitud,
                'solicitud_id' => $solicitud_id
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al enviar solicitud: ' . $e->getMessage()]);
        }
        break;
        
    case 'listar':
        // Obtener solicitudes de subida del usuario actual
        try {
            $stmt = $pdo->prepare("
                SELECT sl.*, 
                       l.titulo as libro_titulo,
                       l.autor as libro_autor,
                       CASE 
                           WHEN sl.estado = 'pendiente' THEN 'Pendiente'
                           WHEN sl.estado = 'aprobada' THEN 'Aprobada'
                           WHEN sl.estado = 'rechazada' THEN 'Rechazada'
                           WHEN sl.estado = 'completada' THEN 'Completada'
                           ELSE 'Pendiente'
                       END as estado_formateado,
                       CASE 
                           WHEN sl.tipo_solicitud = 'sugerencia_compra' THEN 'Sugerencia de Compra'
                           WHEN sl.tipo_solicitud = 'donacion' THEN 'Donación'
                           WHEN sl.tipo_solicitud = 'digitalizacion' THEN 'Digitalización'
                           WHEN sl.tipo_solicitud = 'otro' THEN 'Otro'
                           ELSE sl.tipo_solicitud
                       END as tipo_formateado,
                       u.nombre as nombre_usuario
                FROM solicitudes_libros sl
                LEFT JOIN libros l ON sl.libro_id = l.id
                JOIN usuarios u ON sl.usuario_id = u.id
                WHERE sl.usuario_id = ?
                ORDER BY sl.fecha_solicitud DESC
            ");
            $stmt->execute([$_SESSION['usuario_id']]);
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'solicitudes' => $solicitudes]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener solicitudes: ' . $e->getMessage()]);
        }
        break;
        
    case 'detalle':
        // Obtener detalles de una solicitud de subida específica
        $solicitud_id = $_GET['id'] ?? '';
        
        if (empty($solicitud_id) || !is_numeric($solicitud_id)) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud no válido']);
            exit;
        }
        
        try {
            // Los Super Admins pueden ver cualquier solicitud, los usuarios solo las suyas
            if ($_SESSION['rol'] === 'Super Admin') {
                $stmt = $pdo->prepare("
                    SELECT sl.*, 
                           l.titulo as libro_titulo,
                           l.autor as libro_autor,
                           l.isbn as libro_isbn,
                           CASE 
                               WHEN sl.estado = 'pendiente' THEN 'Pendiente'
                               WHEN sl.estado = 'aprobada' THEN 'Aprobada'
                               WHEN sl.estado = 'rechazada' THEN 'Rechazada'
                               WHEN sl.estado = 'completada' THEN 'Completada'
                               ELSE 'Pendiente'
                           END as estado_formateado,
                           CASE 
                               WHEN sl.tipo_solicitud = 'sugerencia_compra' THEN 'Sugerencia de Compra'
                               WHEN sl.tipo_solicitud = 'donacion' THEN 'Donación'
                               WHEN sl.tipo_solicitud = 'digitalizacion' THEN 'Digitalización'
                               WHEN sl.tipo_solicitud = 'otro' THEN 'Otro'
                               ELSE sl.tipo_solicitud
                           END as tipo_formateado,
                           u.nombre as nombre_usuario,
                           u.email as email_usuario
                    FROM solicitudes_libros sl
                    LEFT JOIN libros l ON sl.libro_id = l.id
                    JOIN usuarios u ON sl.usuario_id = u.id
                    WHERE sl.id = ?
                ");
                $stmt->execute([$solicitud_id]);
            } else {
                // Usuarios normales solo pueden ver sus propias solicitudes
                $stmt = $pdo->prepare("
                    SELECT sl.*, 
                           l.titulo as libro_titulo,
                           l.autor as libro_autor,
                           CASE 
                               WHEN sl.estado = 'pendiente' THEN 'Pendiente'
                               WHEN sl.estado = 'aprobada' THEN 'Aprobada'
                               WHEN sl.estado = 'rechazada' THEN 'Rechazada'
                               WHEN sl.estado = 'completada' THEN 'Completada'
                               ELSE 'Pendiente'
                           END as estado_formateado,
                           CASE 
                               WHEN sl.tipo_solicitud = 'sugerencia_compra' THEN 'Sugerencia de Compra'
                               WHEN sl.tipo_solicitud = 'donacion' THEN 'Donación'
                               WHEN sl.tipo_solicitud = 'digitalizacion' THEN 'Digitalización'
                               WHEN sl.tipo_solicitud = 'otro' THEN 'Otro'
                               ELSE sl.tipo_solicitud
                           END as tipo_formateado,
                           u.nombre as nombre_usuario
                    FROM solicitudes_libros sl
                    LEFT JOIN libros l ON sl.libro_id = l.id
                    JOIN usuarios u ON sl.usuario_id = u.id
                    WHERE sl.id = ? AND sl.usuario_id = ?
                ");
                $stmt->execute([$solicitud_id, $_SESSION['usuario_id']]);
            }
            
            $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solicitud) {
                echo json_encode(['success' => false, 'message' => 'Solicitud no encontrada o no tienes permiso para verla']);
                exit;
            }
            
            echo json_encode([
                'success' => true, 
                'solicitud' => $solicitud
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener solicitud: ' . $e->getMessage()]);
        }
        break;
        
    case 'actualizar_estado':
        // Actualizar estado de una solicitud (solo para Super Admins)
        if ($_SESSION['rol'] !== 'Super Admin') {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
            exit;
        }
        
        $solicitud_id = sanitize($_POST['id'] ?? '');
        $nuevo_estado = sanitize($_POST['estado'] ?? '');
        $observaciones = sanitize($_POST['observaciones'] ?? '');
        
        if (empty($solicitud_id) || !is_numeric($solicitud_id)) {
            echo json_encode(['success' => false, 'message' => 'ID de solicitud no válido']);
            exit;
        }
        
        if (empty($nuevo_estado) || !in_array($nuevo_estado, ['pendiente', 'aprobada', 'rechazada', 'completada'])) {
            echo json_encode(['success' => false, 'message' => 'Estado no válido']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("
                UPDATE solicitudes_libros 
                SET estado = ?, observaciones = ?, fecha_respuesta = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$nuevo_estado, $observaciones, $solicitud_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Estado actualizado exitosamente'
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar estado: ' . $e->getMessage()]);
        }
        break;
        
    case 'listar_admin':
        // Listar todas las solicitudes de subida (solo para Super Admins)
        if ($_SESSION['rol'] !== 'Super Admin') {
            echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción.']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("
                SELECT sl.*, 
                       l.titulo as libro_titulo,
                       l.autor as libro_autor,
                       CASE 
                           WHEN sl.estado = 'pendiente' THEN 'Pendiente'
                           WHEN sl.estado = 'aprobada' THEN 'Aprobada'
                           WHEN sl.estado = 'rechazada' THEN 'Rechazada'
                           WHEN sl.estado = 'completada' THEN 'Completada'
                           ELSE 'Pendiente'
                       END as estado_formateado,
                       CASE 
                           WHEN sl.tipo_solicitud = 'sugerencia_compra' THEN 'Sugerencia de Compra'
                           WHEN sl.tipo_solicitud = 'donacion' THEN 'Donación'
                           WHEN sl.tipo_solicitud = 'digitalizacion' THEN 'Digitalización'
                           WHEN sl.tipo_solicitud = 'otro' THEN 'Otro'
                           ELSE sl.tipo_solicitud
                       END as tipo_formateado,
                       u.nombre as nombre_usuario,
                       u.email as email_usuario
                FROM solicitudes_libros sl
                LEFT JOIN libros l ON sl.libro_id = l.id
                JOIN usuarios u ON sl.usuario_id = u.id
                ORDER BY sl.fecha_solicitud DESC
            ");
            $stmt->execute();
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'solicitudes' => $solicitudes]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error al obtener solicitudes: ' . $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        break;
}
?>
