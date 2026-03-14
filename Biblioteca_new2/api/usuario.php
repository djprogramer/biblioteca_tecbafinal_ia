<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
        case 'change_password':
            // Cambiar contraseña
            $data = json_decode(file_get_contents('php://input'), true);
            $password_actual = isset($data['password_actual']) ? $data['password_actual'] : '';
            $password_nueva = isset($data['password_nueva']) ? $data['password_nueva'] : '';
            
            if (empty($password_actual) || empty($password_nueva)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ]);
                exit;
            }
            
            // Validar longitud mínima
            if (strlen($password_nueva) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'La nueva contraseña debe tener al menos 6 caracteres'
                ]);
                exit;
            }
            
            // Obtener datos actuales del usuario
            $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE id = ?");
            $stmt->execute([$usuario_id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
                exit;
            }
            
            // Verificar contraseña actual
            $hash_info = password_get_info($usuario['password']);
            $is_hashed = $hash_info['algo'] > 0;
            
            if ($is_hashed) {
                if (!password_verify($password_actual, $usuario['password'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'La contraseña actual es incorrecta'
                    ]);
                    exit;
                }
            } else {
                if ($usuario['password'] !== $password_actual) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'La contraseña actual es incorrecta'
                    ]);
                    exit;
                }
            }
            
            // Generar nuevo hash
            $nuevo_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            
            // Actualizar contraseña
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $result = $stmt->execute([$nuevo_hash, $usuario_id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Contraseña actualizada exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar la contraseña'
                ]);
            }
            break;
            
        case 'get_profile':
            // Obtener datos del perfil
            $stmt = $pdo->prepare("
                SELECT id, nombre, email, rol, fecha_registro 
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$usuario_id]);
            $perfil = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($perfil) {
                echo json_encode([
                    'success' => true,
                    'perfil' => $perfil
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Perfil no encontrado'
                ]);
            }
            break;
            
        case 'update_profile':
            // Actualizar datos del perfil
            $data = json_decode(file_get_contents('php://input'), true);
            $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
            $email = isset($data['email']) ? trim($data['email']) : '';
            
            if (empty($nombre) || empty($email)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ]);
                exit;
            }
            
            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email no es válido'
                ]);
                exit;
            }
            
            // Verificar si el email ya está en uso por otro usuario
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM usuarios 
                WHERE email = ? AND id != ?
            ");
            $stmt->execute([$email, $usuario_id]);
            $email_exists = $stmt->fetch(PDO::FETCH_ASSOC)['count'] > 0;
            
            if ($email_exists) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email ya está en uso por otro usuario'
                ]);
                exit;
            }
            
            // Actualizar perfil
            $stmt = $pdo->prepare("
                UPDATE usuarios 
                SET nombre = ?, email = ? 
                WHERE id = ?
            ");
            $result = $stmt->execute([$nombre, $email, $usuario_id]);
            
            if ($result) {
                // Actualizar sesión
                $_SESSION['nombre'] = $nombre;
                $_SESSION['email'] = $email;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Perfil actualizado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar el perfil'
                ]);
            }
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
