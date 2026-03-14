<?php
/**
 * API para búsqueda de libros
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

try {
    $query = isset($_GET['q']) ? trim($_GET['q']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 12;
    $offset = ($page - 1) * $limit;
    $live = isset($_GET['live']) ? (int)$_GET['live'] : 0;
    
    if (empty($query)) {
        http_response_code(400);
        echo json_encode(['error' => 'La consulta de búsqueda no puede estar vacía']);
        return;
    }
    
    // Para búsqueda en vivo, limitar resultados
    if ($live) {
        $limit = 5;
    }
    
    // Construir consulta de búsqueda
    $where = "(l.titulo LIKE :query1 OR l.autor LIKE :query2 OR l.descripcion LIKE :query3)";
    $params = [
        ':query1' => '%' . $query . '%',
        ':query2' => '%' . $query . '%',
        ':query3' => '%' . $query . '%',
        ':query4' => '%' . $query . '%'
    ];
    
    // Contar total de resultados
    $countSql = "SELECT COUNT(DISTINCT l.id) as total 
                 FROM libros l 
                 LEFT JOIN libro_categoria lc ON l.id = lc.libro_id 
                 LEFT JOIN categorias c ON lc.categoria_id = c.id 
                 WHERE $where OR c.nombre LIKE :query4";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Obtener resultados
    $sql = "SELECT DISTINCT l.*, 
                   GROUP_CONCAT(c.nombre SEPARATOR ', ') as categorias,
                   MATCH(l.titulo, l.autor, l.descripcion) AGAINST(:query5 IN NATURAL LANGUAGE MODE) as relevancia
            FROM libros l 
            LEFT JOIN libro_categoria lc ON l.id = lc.libro_id 
            LEFT JOIN categorias c ON lc.categoria_id = c.id 
            WHERE $where OR c.nombre LIKE :query4
            GROUP BY l.id 
            ORDER BY relevancia DESC, l.titulo 
            LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
            
            // Bind todos los parámetros
            $stmt->bindValue(':query1', '%' . $query . '%');
            $stmt->bindValue(':query2', '%' . $query . '%');
            $stmt->bindValue(':query3', '%' . $query . '%');
            $stmt->bindValue(':query4', '%' . $query . '%');
            $stmt->bindValue(':query5', $query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
    $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar resultados
    foreach ($libros as &$libro) {
        $libro['portada'] = $libro['portada'] ?: 'assets/images/default-book.jpg';
        $libro['descripcion'] = limitText($libro['descripcion'], $live ? 100 : 150);
        $libro['categorias'] = $libro['categorias'] ? explode(', ', $libro['categorias']) : [];
        
        // Resaltar términos de búsqueda
        if (!$live) {
            $libro['titulo'] = highlightSearchTerm($libro['titulo'], $query);
            $libro['autor'] = highlightSearchTerm($libro['autor'], $query);
        }
    }
    
    $response = [
        'libros' => $libros,
        'query' => $query,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => ceil($total / $limit),
            'total_items' => $total,
            'items_per_page' => $limit
        ]
    ];
    
    if (!$live) {
        $response['suggestions'] = getSearchSuggestions($db, $query);
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}

function highlightSearchTerm($text, $query) {
    $words = explode(' ', $query);
    foreach ($words as $word) {
        if (strlen($word) > 2) {
            $text = preg_replace('/(' . preg_quote($word, '/') . ')/i', '<mark>$1</mark>', $text);
        }
    }
    return $text;
}

function getSearchSuggestions($db, $query) {
    try {
        $sql = "SELECT DISTINCT titulo 
                FROM libros 
                WHERE titulo LIKE :query 
                ORDER BY titulo 
                LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':query' => $query . '%']);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}
?>
