<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/database.php';
require_once '../includes/functions.php';

// Iniciar sesión para acceder a variables de sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Usuario no autenticado'
    ]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'get_favorites':
            // Obtener favoritos del usuario
            $stmt = $pdo->prepare("
                SELECT l.*, f.fecha_agregado 
                FROM favoritos f 
                JOIN libros l ON f.libro_id = l.id 
                WHERE f.usuario_id = ? 
                ORDER BY f.fecha_agregado DESC
            ");
            $stmt->execute([$usuario_id]);
            $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'favorites' => $favoritos
            ]);
            break;
            
        case 'add_favorite':
            // Añadir libro a favoritos
            $data = json_decode(file_get_contents('php://input'), true);
            $libro_id = isset($data['libro_id']) ? (int)$data['libro_id'] : 0;
            
            if ($libro_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de libro inválido'
                ]);
                exit;
            }
            
            // Verificar si ya es favorito
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM favoritos 
                WHERE usuario_id = ? AND libro_id = ?
            ");
            $stmt->execute([$usuario_id, $libro_id]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($exists) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El libro ya está en favoritos'
                ]);
                exit;
            }
            
            // Añadir a favoritos
            $stmt = $pdo->prepare("
                INSERT INTO favoritos (usuario_id, libro_id, fecha_agregado) 
                VALUES (?, ?, NOW())
            ");
            $result = $stmt->execute([$usuario_id, $libro_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Libro añadido a favoritos'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al añadir a favoritos'
                ]);
            }
            break;
            
        case 'remove_favorite':
            // Quitar libro de favoritos
            $data = json_decode(file_get_contents('php://input'), true);
            $libro_id = isset($data['libro_id']) ? (int)$data['libro_id'] : 0;
            
            if ($libro_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de libro inválido'
                ]);
                exit;
            }
            
            // Eliminar de favoritos
            $stmt = $pdo->prepare("
                DELETE FROM favoritos 
                WHERE usuario_id = ? AND libro_id = ?
            ");
            $result = $stmt->execute([$usuario_id, $libro_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Libro eliminado de favoritos'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar de favoritos'
                ]);
            }
            break;
            
        case 'check_favorite':
            // Verificar si un libro es favorito
            $libro_id = isset($_GET['libro_id']) ? (int)$_GET['libro_id'] : 0;
            
            if ($libro_id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de libro inválido'
                ]);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM favoritos 
                WHERE usuario_id = ? AND libro_id = ?
            ");
            $stmt->execute([$usuario_id, $libro_id]);
            $isFavorite = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            echo json_encode([
                'success' => true,
                'is_favorite' => $isFavorite
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Acción no válida'
            ]);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
