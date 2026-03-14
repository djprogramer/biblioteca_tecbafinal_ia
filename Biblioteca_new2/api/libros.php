<?php
/**
 * API para gestión de libros
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'index';

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $action);
            break;
        case 'POST':
            handlePost($db, $action);
            break;
        case 'PUT':
            handlePut($db, $action);
            break;
        case 'DELETE':
            handleDelete($db, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

function handleGet($db, $action) {
    switch ($action) {
        case 'index':
            // Obtener todos los libros con paginación
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
            $offset = ($page - 1) * $limit;
            $categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;
            
            $where = "";
            $params = [];
            
            if ($categoria) {
                $where = "WHERE lc.categoria_id = :categoria";
                $params[':categoria'] = $categoria;
            }
            
            // Contar total
            $countSql = "SELECT COUNT(DISTINCT l.id) as total 
                         FROM libros l 
                         LEFT JOIN libro_categoria lc ON l.id = lc.libro_id 
                         $where";
            $countStmt = $db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Obtener libros
            $sql = "SELECT DISTINCT l.*, 
                           GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias
                    FROM libros l 
                    LEFT JOIN libro_categoria lc ON l.id = lc.libro_id 
                    LEFT JOIN categorias c ON lc.categoria_id = c.id 
                    $where
                    GROUP BY l.id 
                    ORDER BY l.titulo 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // Agregar otros parámetros si existen
            if ($categoria) {
                $stmt->bindValue(':categoria', $categoria, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            
            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar resultados
            foreach ($libros as &$libro) {
                $libro['portada'] = $libro['portada'] ?: 'assets/images/default-book.jpg';
                $libro['descripcion'] = limitText($libro['descripcion'], 150);
                $libro['categorias'] = $libro['categorias'] ? explode(', ', $libro['categorias']) : [];
            }
            
            echo json_encode([
                'libros' => $libros,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($total / $limit),
                    'total_items' => $total,
                    'items_per_page' => $limit
                ]
            ]);
            break;
            
        case 'show':
            // Obtener un libro específico
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de libro inválido']);
                return;
            }
            
            $sql = "SELECT l.*, 
                           GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias
                    FROM libros l 
                    LEFT JOIN libro_categoria lc ON l.id = lc.libro_id 
                    LEFT JOIN categorias c ON lc.categoria_id = c.id 
                    WHERE l.id = :id
                    GROUP BY l.id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $libro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$libro) {
                http_response_code(404);
                echo json_encode(['error' => 'Libro no encontrado']);
                return;
            }
            
            $libro['portada'] = $libro['portada'] ?: 'assets/images/default-book.jpg';
            $libro['categorias'] = $libro['categorias'] ? explode(', ', $libro['categorias']) : [];
            
            echo json_encode($libro);
            break;
            
        case 'recent':
            // Obtener libros recientes
            $sql = "SELECT l.*, 
                           GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias
                    FROM libros l 
                    LEFT JOIN libro_categoria lc ON l.id = lc.libro_id 
                    LEFT JOIN categorias c ON lc.categoria_id = c.id 
                    GROUP BY l.id 
                    ORDER BY l.id DESC 
                    LIMIT 8";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($libros as &$libro) {
                $libro['portada'] = $libro['portada'] ?: 'assets/images/default-book.jpg';
                $libro['descripcion'] = limitText($libro['descripcion'], 100);
            }
            
            echo json_encode(['libros' => $libros]);
            break;
    }
}

function handlePost($db, $action) {
    // Aquí iría la lógica para crear nuevos libros
    http_response_code(501);
    echo json_encode(['error' => 'Función no implementada']);
}

function handlePut($db, $action) {
    // Aquí iría la lógica para actualizar libros
    http_response_code(501);
    echo json_encode(['error' => 'Función no implementada']);
}

function handleDelete($db, $action) {
    // Aquí iría la lógica para eliminar libros
    http_response_code(501);
    echo json_encode(['error' => 'Función no implementada']);
}
?>
