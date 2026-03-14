<?php
/**
 * API para gestión de categorías
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
            // Obtener todas las categorías con conteo de libros
            $sql = "SELECT c.*, COUNT(lc.libro_id) as total_libros
                    FROM categorias c 
                    LEFT JOIN libro_categoria lc ON c.id = lc.categoria_id 
                    LEFT JOIN libros l ON lc.libro_id = l.id 
                    GROUP BY c.id 
                    ORDER BY c.nombre";
            
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Asignar iconos según el tipo de categoría
            foreach ($categorias as &$categoria) {
                $categoria['icon'] = getCategoryIcon($categoria['nombre']);
                $categoria['color'] = getCategoryColor($categoria['nombre']);
            }
            
            echo json_encode(['categorias' => $categorias]);
            break;
            
        case 'show':
            // Obtener una categoría específica con sus libros
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'ID de categoría inválido']);
                return;
            }
            
            // Obtener información de la categoría
            $sql = "SELECT c.*, COUNT(lc.libro_id) as total_libros
                    FROM categorias c 
                    LEFT JOIN libro_categoria lc ON c.id = lc.categoria_id 
                    WHERE c.id = :id
                    GROUP BY c.id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $id]);
            $categoria = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$categoria) {
                http_response_code(404);
                echo json_encode(['error' => 'Categoría no encontrada']);
                return;
            }
            
            // Obtener libros de esta categoría
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
            $offset = ($page - 1) * $limit;
            
            $librosSql = "SELECT l.* 
                         FROM libros l 
                         INNER JOIN libro_categoria lc ON l.id = lc.libro_id 
                         WHERE lc.categoria_id = :id 
                         ORDER BY l.titulo 
                         LIMIT :limit OFFSET :offset";
            
            $librosStmt = $db->prepare($librosSql);
            $librosStmt->bindValue(':id', $id, PDO::PARAM_INT);
            $librosStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $librosStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $librosStmt->execute();
            $libros = $librosStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Procesar libros
            foreach ($libros as &$libro) {
                $libro['portada'] = $libro['portada'] ?: 'assets/images/default-book.jpg';
                $libro['descripcion'] = limitText($libro['descripcion'], 150);
            }
            
            $categoria['icon'] = getCategoryIcon($categoria['nombre']);
            $categoria['color'] = getCategoryColor($categoria['nombre']);
            
            echo json_encode([
                'categoria' => $categoria,
                'libros' => $libros,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($categoria['total_libros'] / $limit),
                    'total_items' => $categoria['total_libros'],
                    'items_per_page' => $limit
                ]
            ]);
            break;
    }
}

function handlePost($db, $action) {
    // Aquí iría la lógica para crear nuevas categorías
    http_response_code(501);
    echo json_encode(['error' => 'Función no implementada']);
}

function handlePut($db, $action) {
    // Aquí iría la lógica para actualizar categorías
    http_response_code(501);
    echo json_encode(['error' => 'Función no implementada']);
}

function handleDelete($db, $action) {
    // Aquí iría la lógica para eliminar categorías
    http_response_code(501);
    echo json_encode(['error' => 'Función no implementada']);
}

function getCategoryIcon($categoryName) {
    $icons = [
        'FATEK EMPRESAS' => 'fas fa-briefcase',
        'FATEK COMUNICACION' => 'fas fa-comments',
        'FATEK SALUD' => 'fas fa-heartbeat',
        'FATEK COMPUTACION' => 'fas fa-laptop-code',
        'FATEK INDUSTRIA' => 'fas fa-industry',
        'FATEK ARQUITECTURA' => 'fas fa-building',
        'PROYECTOS DE GRADO CBBA' => 'fas fa-graduation-cap',
        'DESIGN LAB' => 'fas fa-palette',
        'PROYECTOS SUCRE' => 'fas fa-project-diagram'
    ];
    
    return $icons[$categoryName] ?? 'fas fa-folder';
}

function getCategoryColor($categoryName) {
    $colors = [
        'FATEK EMPRESAS' => '#007bff',
        'FATEK COMUNICACION' => '#17a2b8',
        'FATEK SALUD' => '#28a745',
        'FATEK COMPUTACION' => '#6f42c1',
        'FATEK INDUSTRIA' => '#fd7e14',
        'FATEK ARQUITECTURA' => '#20c997',
        'PROYECTOS DE GRADO CBBA' => '#6c757d',
        'DESIGN LAB' => '#e83e8c',
        'PROYECTOS SUCRE' => '#343a40'
    ];
    
    return $colors[$categoryName] ?? '#007bff';
}
?>
