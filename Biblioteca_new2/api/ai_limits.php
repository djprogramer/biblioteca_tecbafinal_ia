<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/functions.php';
require_once '../includes/database.php';

session_start();

// Verificar si el usuario es administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

$action = $data['action'] ?? '';

global $pdo;

try {
    switch ($action) {
        case 'get_limits':
            $stmt = $pdo->prepare("
                SELECT id, role_name, daily_limit, hourly_limit, description, is_active
                FROM ai_role_limits 
                ORDER BY role_name
            ");
            $stmt->execute();
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'roles' => $roles]);
            break;
            
        case 'update_limits':
            $limits = $data['limits'] ?? [];
            
            if (empty($limits)) {
                echo json_encode(['success' => false, 'message' => 'No se proporcionaron límites']);
                exit;
            }
            
            $pdo->beginTransaction();
            
            foreach ($limits as $limit) {
                $stmt = $pdo->prepare("
                    UPDATE ai_role_limits 
                    SET daily_limit = ?, 
                        hourly_limit = ?, 
                        description = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([
                    $limit['daily_limit'],
                    $limit['hourly_limit'],
                    $limit['description'],
                    $limit['id']
                ]);
            }
            
            $pdo->commit();
            
            echo json_encode(['success' => true, 'message' => 'Límites actualizados correctamente']);
            break;
            
        case 'toggle_role':
            $roleId = $data['role_id'] ?? 0;
            
            if (!$roleId) {
                echo json_encode(['success' => false, 'message' => 'ID de rol no proporcionado']);
                exit;
            }
            
            $stmt = $pdo->prepare("
                UPDATE ai_role_limits 
                SET is_active = NOT is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$roleId]);
            
            echo json_encode(['success' => true, 'message' => 'Rol actualizado correctamente']);
            break;
            
        case 'get_user_stats':
            $stmt = $pdo->prepare("
                SELECT 
                    u.rol,
                    COUNT(u.id) as user_count,
                    COALESCE(SUM(daily_requests), 0) as total_daily_requests,
                    COALESCE(SUM(hourly_requests), 0) as total_hourly_requests
                FROM usuarios u
                LEFT JOIN ai_role_limits arl ON u.rol = arl.role_name
                LEFT JOIN (
                    SELECT 
                        user_id,
                        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as daily_requests,
                        COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as hourly_requests
                    FROM ai_requests 
                    GROUP BY user_id
                ) req_stats ON u.id = req_stats.user_id
                GROUP BY u.rol
                ORDER BY u.rol
            ");
            $stmt->execute();
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
