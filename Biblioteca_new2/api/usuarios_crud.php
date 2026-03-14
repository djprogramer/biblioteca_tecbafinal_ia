<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../includes/database.php';
require_once '../includes/functions.php';

// Iniciar sesión para acceder a variables de sesión
session_start();

// Verificar si el usuario está logueado y es Super Admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'Super Admin') {
    echo json_encode([
        'success' => false,
        'message' => 'No tienes permisos para realizar esta acción'
    ]);
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'create':
            // Crear nuevo usuario
            $nombre = sanitize($_POST['nombre'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = sanitize($_POST['password'] ?? '');
            $rol = sanitize($_POST['rol'] ?? '');
            
            // Validaciones
            if (empty($nombre) || empty($email) || empty($password) || empty($rol)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Todos los campos son requeridos'
                ]);
                exit;
            }
            
            if (strlen($password) < 6) {
                echo json_encode([
                    'success' => false,
                    'message' => 'La contraseña debe tener al menos 6 caracteres'
                ]);
                exit;
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email no es válido'
                ]);
                exit;
            }
            
            // Verificar si el email ya existe
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ]);
                exit;
            }
            
            // Validar rol
            $roles_validos = ['Super Admin', 'Administrativo', 'Docente', 'Estudiante'];
            if (!in_array($rol, $roles_validos)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Rol no válido'
                ]);
                exit;
            }
            
            // Crear usuario
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO usuarios (nombre, email, password, rol, created_at, updated_at) 
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            
            if ($stmt->execute([$nombre, $email, $password_hash, $rol])) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al crear usuario'
                ]);
            }
            break;
            
        case 'update':
            // Actualizar usuario existente
            $id = (int)($_POST['id'] ?? 0);
            $nombre = sanitize($_POST['nombre'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $rol = sanitize($_POST['rol'] ?? '');
            $cambiar_password = isset($_POST['cambiar_password']) ? true : false;
            $password = sanitize($_POST['password'] ?? '');
            
            // Validaciones básicas
            if ($id <= 0 || empty($nombre) || empty($email) || empty($rol)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Datos incompletos'
                ]);
                exit;
            }
            
            // No permitir modificar el rol del usuario actual si es Super Admin
            if ($id == $usuario_id && $_SESSION['rol'] === 'Super Admin') {
                // Permitir cambiar nombre y email, pero no rol
                $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
                $rol = $current_user['rol']; // Mantener rol actual
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email no es válido'
                ]);
                exit;
            }
            
            // Verificar si el email ya existe (excepto para el mismo usuario)
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El email ya está registrado por otro usuario'
                ]);
                exit;
            }
            
            // Validar rol
            $roles_validos = ['Super Admin', 'Administrativo', 'Docente', 'Estudiante'];
            if (!in_array($rol, $roles_validos)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Rol no válido'
                ]);
                exit;
            }
            
            // Construir consulta de actualización
            if ($cambiar_password && !empty($password)) {
                if (strlen($password) < 6) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'La contraseña debe tener al menos 6 caracteres'
                    ]);
                    exit;
                }
                
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nombre = ?, email = ?, password = ?, rol = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nombre, $email, $password_hash, $rol, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE usuarios 
                    SET nombre = ?, email = ?, rol = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $result = $stmt->execute([$nombre, $email, $rol, $id]);
            }
            
            if ($result) {
                // Si el usuario se está actualizando a sí mismo, actualizar sesión
                if ($id == $usuario_id) {
                    $_SESSION['nombre'] = $nombre;
                    $_SESSION['email'] = $email;
                    $_SESSION['rol'] = $rol;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario actualizado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar usuario'
                ]);
            }
            break;
            
        case 'delete':
            // Eliminar usuario
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de usuario inválido'
                ]);
                exit;
            }
            
            // No permitir eliminar al usuario actual
            if ($id == $usuario_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propio usuario'
                ]);
                exit;
            }
            
            // Verificar si existe el usuario
            $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
                exit;
            }
            
            // Eliminar usuario
            $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario eliminado exitosamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar usuario'
                ]);
            }
            break;
            
        case 'get':
            // Obtener usuario específico
            $id = (int)($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de usuario inválido'
                ]);
                exit;
            }
            
            $stmt = $pdo->prepare("
                SELECT id, nombre, email, rol, created_at, updated_at 
                FROM usuarios 
                WHERE id = ?
            ");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usuario) {
                echo json_encode([
                    'success' => true,
                    'usuario' => $usuario
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ]);
            }
            break;
            
        case 'list':
            // Listar todos los usuarios
            $stmt = $pdo->prepare("
                SELECT id, nombre, email, rol, created_at, updated_at 
                FROM usuarios 
                ORDER BY created_at DESC
            ");
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'usuarios' => $usuarios
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
