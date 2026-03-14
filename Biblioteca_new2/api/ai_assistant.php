<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/functions.php';
require_once '../includes/ai_service.php';

session_start();

// Logging para diagnóstico
error_log("API AI Assistant llamada - Action: " . ($data['action'] ?? 'unknown'));

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    error_log("Usuario no logueado");
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión para usar el asistente de IA']);
    exit;
}

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    error_log("Datos no válidos recibidos: " . file_get_contents('php://input'));
    echo json_encode(['success' => false, 'message' => 'Datos no válidos']);
    exit;
}

$action = $data['action'] ?? '';
$userId = $_SESSION['usuario_id'];

error_log("Action: $action, UserID: $userId");

// Inicializar el servicio de IA
$aiService = new AIService();

try {
    switch ($action) {
        case 'research':
            $question = $data['question'] ?? '';
            $isContinuation = $data['is_continuation'] ?? false;
            
            if (empty($question)) {
                echo json_encode(['success' => false, 'message' => 'Debes proporcionar una pregunta']);
                exit;
            }
            
            error_log("Procesando research: $question (continuación: " . ($isContinuation ? 'sí' : 'no') . ")");
            $result = $aiService->researchAssistant($userId, $question, $isContinuation);
            error_log("Resultado research: " . json_encode($result));
            echo json_encode($result);
            break;
            
        case 'recommendations':
            $interests = $data['interests'] ?? '';
            $level = $data['level'] ?? 'universitario';
            
            if (empty($interests)) {
                echo json_encode(['success' => false, 'message' => 'Debes proporcionar tus intereses']);
                exit;
            }
            
            $result = $aiService->getRecommendations($userId, $interests, $level);
            echo json_encode($result);
            break;
            
        case 'summarize':
            $content = $data['content'] ?? '';
            $type = $data['type'] ?? 'libro';
            
            if (empty($content)) {
                echo json_encode(['success' => false, 'message' => 'Debes proporcionar el contenido a resumir']);
                exit;
            }
            
            $result = $aiService->summarizeContent($userId, $content, $type);
            echo json_encode($result);
            break;
            
        case 'academic_help':
            $task = $data['task'] ?? '';
            $subject = $data['subject'] ?? '';
            
            if (empty($task) || empty($subject)) {
                echo json_encode(['success' => false, 'message' => 'Debes proporcionar la tarea y la materia']);
                exit;
            }
            
            $result = $aiService->academicHelp($userId, $task, $subject);
            echo json_encode($result);
            break;
            
        case 'stats':
            $stats = $aiService->getUserStats($userId);
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'stats_with_limits':
            $stats = $aiService->getUserStats($userId);
            $limits = $aiService->getUserCurrentLimits($userId);
            echo json_encode(['success' => true, 'stats' => $stats, 'user_limits' => $limits]);
            break;
            
        case 'get_role_limits':
            // Solo Super Admin puede ver/configurar límites
            if ($_SESSION['rol'] !== 'Super Admin') {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }
            
            $roleLimits = $aiService->getRoleLimits();
            echo json_encode(['success' => true, 'roles' => $roleLimits]);
            break;
            
        case 'update_role_limits':
            // Solo Super Admin puede actualizar límites
            if ($_SESSION['rol'] !== 'Super Admin') {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }
            
            $limits = $data['limits'] ?? [];
            if (empty($limits)) {
                echo json_encode(['success' => false, 'message' => 'No se proporcionaron límites']);
                exit;
            }
            
            $result = $aiService->updateRoleLimits($limits);
            echo json_encode($result);
            break;
            
        case 'storage_stats':
            // Solo Super Admin puede ver estadísticas de almacenamiento
            if ($_SESSION['rol'] !== 'Super Admin') {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }
            
            $stats = $aiService->getStorageStatsByRole();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'cleanup_data':
            // Solo Super Admin puede ejecutar limpieza manual
            if ($_SESSION['rol'] !== 'Super Admin') {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }
            
            $result = $aiService->cleanupOldData();
            echo json_encode($result);
            break;
            
        case 'cleanup_status':
            // Solo Super Admin puede ver estado de limpieza
            if ($_SESSION['rol'] !== 'Super Admin') {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }
            
            require_once '../includes/auto_cleanup.php';
            $cleanupManager = new AutoCleanupManager();
            $status = $cleanupManager->getLastCleanupStatus();
            $stats = $aiService->getStorageStatsByRole();
            
            echo json_encode(['success' => true, 'status' => $status, 'stats' => $stats]);
            break;
            
        case 'force_cleanup':
            // Solo Super Admin puede forzar limpieza
            if ($_SESSION['rol'] !== 'Super Admin') {
                echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
                exit;
            }
            
            require_once '../includes/auto_cleanup.php';
            $cleanupManager = new AutoCleanupManager();
            $result = $cleanupManager->forceCleanup();
            echo json_encode($result);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()]);
}
?>
