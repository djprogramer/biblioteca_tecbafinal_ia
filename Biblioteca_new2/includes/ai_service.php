<?php
require_once 'ai_config.php';
require_once 'cache_manager.php';
require_once 'database.php';

class AIService {
    private $pdo;
    private $cache;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->cache = new CacheManager();
    }
    
    /**
     * Verificar si el usuario puede hacer más peticiones según su rol
     */
    private function checkUserLimits($userId) {
        try {
            // Verificar si la tabla ai_role_limits existe
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'ai_role_limits'");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                // Obtener rol y límites del usuario desde la tabla de límites
                $stmt = $this->pdo->prepare("
                    SELECT u.rol, arl.daily_limit, arl.hourly_limit, arl.description
                    FROM usuarios u
                    LEFT JOIN ai_role_limits arl ON u.rol = arl.role_name AND arl.is_active = TRUE
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData && $userData['daily_limit']) {
                    $dailyLimit = $userData['daily_limit'];
                    $hourlyLimit = $userData['hourly_limit'];
                    $roleName = $userData['rol'];
                } else {
                    // Fallback si no hay configuración para el rol
                    list($dailyLimit, $hourlyLimit, $roleName) = $this->getDefaultLimits($userData['rol'] ?? 'usuario');
                }
            } else {
                // Fallback si la tabla no existe - usar límites por rol hardcodeados
                $stmt = $this->pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                list($dailyLimit, $hourlyLimit, $roleName) = $this->getDefaultLimits($userData['rol'] ?? 'usuario');
            }
            
            // Para roles sin límites (Super Admin)
            if ($dailyLimit >= 999999 || $hourlyLimit >= 999999) {
                return ['success' => true, 'unlimited' => true];
            }
            
            // Verificar límite diario
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM ai_requests 
                WHERE user_id = ? AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$userId]);
            $dailyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($dailyCount >= $dailyLimit) {
                return [
                    'success' => false, 
                    'message' => "Has alcanzado tu límite diario de {$dailyLimit} peticiones. Intenta mañana.",
                    'daily_used' => $dailyCount,
                    'daily_limit' => $dailyLimit,
                    'role' => $roleName
                ];
            }
            
            // Verificar límite por hora
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count 
                FROM ai_requests 
                WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ");
            $stmt->execute([$userId]);
            $hourlyCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($hourlyCount >= $hourlyLimit) {
                return [
                    'success' => false, 
                    'message' => "Has alcanzado tu límite horario de {$hourlyLimit} peticiones. Espera una hora.",
                    'hourly_used' => $hourlyCount,
                    'hourly_limit' => $hourlyLimit,
                    'role' => $roleName
                ];
            }
            
            return [
                'success' => true, 
                'daily_used' => $dailyCount,
                'daily_limit' => $dailyLimit,
                'hourly_used' => $hourlyCount,
                'hourly_limit' => $hourlyLimit,
                'role' => $roleName
            ];
            
        } catch (Exception $e) {
            // Fallback total si hay error - usar límites por defecto
            error_log('Error en checkUserLimits: ' . $e->getMessage());
            return [
                'success' => true, 
                'daily_used' => 0,
                'daily_limit' => 50,
                'hourly_used' => 0,
                'hourly_limit' => 10,
                'role' => 'usuario'
            ];
        }
    }
    
    /**
     * Obtener límites por defecto según el rol
     */
    private function getDefaultLimits($role) {
        $limits = [
            'Super Admin' => [999999, 999999, 'Super Admin'],
            'Administrativo' => [50, 10, 'Administrativo'],
            'Docente' => [30, 6, 'Docente'],
            'Estudiante' => [15, 3, 'Estudiante'],
            'usuario' => [25, 5, 'usuario']
        ];
        
        return $limits[$role] ?? [25, 5, 'usuario'];
    }
    
    
    /**
     * Registrar petición en la base de datos
     */
    private function logRequest($userId, $prompt, $response, $type) {
        $stmt = $this->pdo->prepare("
            INSERT INTO ai_requests (user_id, prompt, response, type, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $prompt, $response, $type]);
    }
    
    /**
     * Llamar a la API de Gemini
     */
    private function callGeminiAPI($prompt) {
        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => AI_TEMPERATURE,
                'topP' => AI_TOP_P,
                'topK' => AI_TOP_K,
                'maxOutputTokens' => 16384, // Mitad del límite para permitir continuación
                'stopSequences' => [], // No detener automáticamente
            ],
            'safetySettings' => [
                [
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_HATE_SPEECH', 
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_NONE'
                ],
                [
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_NONE'
                ]
            ]
        ];
        
        $ch = curl_init(GEMINI_API_URL . '?key=' . GEMINI_API_KEY);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Aumentado de 30 a 60 segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // Timeout de conexión
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Error en la API: " . $response);
        }
        
        $result = json_decode($response, true);
        
        // Logging para diagnóstico
        error_log("Respuesta cruda de API: " . substr($response, 0, 500) . "...");
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $responseText = $result['candidates'][0]['content']['parts'][0]['text'];
            
            // Verificar si la respuesta fue truncada
            $finishReason = $result['candidates'][0]['finishReason'] ?? 'UNKNOWN';
            error_log("Finish reason: " . $finishReason);
            
            if ($finishReason === 'MAX_TOKENS') {
                // Agregar botón de continuación
                $responseText .= "\n\n---\n📝 **La respuesta es muy larga.** ¿Quieres que continúe con el mensaje?\n\n💡 *Responde \"continúa\", \"sigue\", o \"continuar\" para leer el resto de la respuesta.*";
            }
            
            return $responseText;
        }
        
        // Si hay error, mostrar información detallada
        if (isset($result['error'])) {
            throw new Exception("Error de API: " . json_encode($result['error']));
        }
        
        throw new Exception("No se pudo procesar la respuesta de la API. Respuesta: " . substr($response, 0, 200));
    }
    
    /**
     * Asistente de investigación bibliotecaria
     */
    public function researchAssistant($userId, $question, $isContinuation = false) {
        // Verificar límites del usuario
        $limits = $this->checkUserLimits($userId);
        if (!$limits['success']) {
            return $limits;
        }
        
        // Verificar caché
        $cacheHash = $this->cache->generateHash($question, 'research');
        $cachedResponse = $this->cache->get($cacheHash);
        
        if ($cachedResponse && !$isContinuation) {
            return ['success' => true, 'response' => $cachedResponse, 'cached' => true];
        }
        
        try {
            // Construir el prompt con contexto de continuación si es necesario
            if ($isContinuation) {
                // Obtener la última pregunta y respuesta para mantener contexto
                $stmt = $this->pdo->prepare("
                    SELECT prompt, response FROM ai_requests 
                    WHERE user_id = ? AND type = 'research' 
                    ORDER BY created_at DESC LIMIT 1
                ");
                $stmt->execute([$userId]);
                $lastInteraction = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($lastInteraction) {
                    $prompt = "Contexto anterior:\nPregunta: " . $lastInteraction['prompt'] . "\nRespuesta parcial: " . $lastInteraction['response'] . "\n\nEl usuario quiere que continúes la respuesta desde donde se cortó. Por favor, continúa la respuesta anterior de manera natural y coherente, sin repetir lo que ya dijiste.";
                } else {
                    $prompt = $question;
                }
            } else {
                $prompt = "Como asistente bibliotecario experto, responde de manera detallada y profesional a: " . $question;
            }
            
            $response = $this->callGeminiAPI($prompt);
            
            // Guardar en caché solo si no es continuación
            if (!$isContinuation) {
                $this->cache->set($cacheHash, $response);
            }
            
            // Registrar petición
            $this->logRequest($userId, $question, $response, 'research');
            
            return ['success' => true, 'response' => $response, 'cached' => false];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al procesar tu pregunta: ' . $e->getMessage()];
        }
    }
    
    /**
     * Recomendaciones de lectura personalizadas
     */
    public function getRecommendations($userId, $interests, $level = 'universitario') {
        // Verificar límites del usuario
        $limits = $this->checkUserLimits($userId);
        if (!$limits['success']) {
            return $limits;
        }
        
        // Verificar caché
        $cacheKey = $interests . '_' . $level;
        $cacheHash = $this->cache->generateHash($cacheKey, 'recommendations');
        $cachedResponse = $this->cache->get($cacheHash);
        
        if ($cachedResponse) {
            return ['success' => true, 'response' => $cachedResponse, 'cached' => true];
        }
        
        try {
            $prompt = "COMO BIBLIOTECARIO EXPERTO EN RECOMENDACIONES: Analiza los intereses del usuario y sugiere 5 libros académicos fundamentales para nivel $level. 

INTERESES: " . $interests . "

FORMATO DE RESPUESTA OBLIGATORIO:
📚 **RECOMENDACIÓN 1: [Título del libro]**
   📖 **Autor:** [Nombre del autor]
   🎯 **Nivel:** $level
   📝 **Descripción:** [2-3 líneas sobre el contenido]
   ⭐ **Por qué recomendarlo:** [Motivo específico para sus intereses]

📚 **RECOMENDACIÓN 2: [Título del libro]**
   📖 **Autor:** [Nombre del autor]
   🎯 **Nivel:** $level
   📝 **Descripción:** [2-3 líneas sobre el contenido]
   ⭐ **Por qué recomendarlo:** [Motivo específico para sus intereses]

[Continuar con 3 recomendaciones más usando el mismo formato]

IMPORTANTE: Solo recomienda libros reales y académicos. Sé específico y práctico. Responde ÚNICAMENTE con las recomendaciones en el formato indicado.";
            
            $response = $this->callGeminiAPI($prompt);
            
            // Guardar en caché
            $this->cache->set($cacheHash, $response);
            
            // Registrar petición
            $this->logRequest($userId, $interests, $response, 'recommendations');
            
            return ['success' => true, 'response' => $response, 'cached' => false];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al generar recomendaciones: ' . $e->getMessage()];
        }
    }
    
    /**
     * Resumir contenido de libro o artículo
     */
    public function summarizeContent($userId, $content, $type = 'libro') {
        // Verificar límites del usuario
        $limits = $this->checkUserLimits($userId);
        if (!$limits['success']) {
            return $limits;
        }
        
        // Verificar caché
        $cacheHash = $this->cache->generateHash(substr($content, 0, 500), 'summary');
        $cachedResponse = $this->cache->get($cacheHash);
        
        if ($cachedResponse) {
            return ['success' => true, 'response' => $cachedResponse, 'cached' => true];
        }
        
        try {
            $prompt = "COMO EXPERTO EN ANÁLISIS ACADÉMICO: Resume el siguiente contenido de $type de manera estructurada y profesional.

CONTENIDO A RESUMIR:
" . $content . "

FORMATO DE RESPUESTA OBLIGATORIO:
📋 **RESUMEN EJECUTIVO**
[2-3 frases con la idea principal]

🔑 **IDEAS PRINCIPALES**
• [Idea clave 1 en una frase]
• [Idea clave 2 en una frase]  
• [Idea clave 3 en una frase]
• [Idea clave 4 en una frase]
• [Idea clave 5 en una frase]

🎯 **CONCLUSIONES CLAVE**
✓ [Conclusión 1]
✓ [Conclusión 2]
✓ [Conclusión 3]

💡 **APLICACIONES PRÁCTICAS**
→ [Aplicación 1]
→ [Aplicación 2]

IMPORTANTE: Sé conciso pero completo. Responde ÚNICAMENTE con el resumen en el formato indicado. No agregues información adicional.";
            
            $response = $this->callGeminiAPI($prompt);
            
            // Guardar en caché
            $this->cache->set($cacheHash, $response);
            
            // Registrar petición
            $this->logRequest($userId, substr($content, 0, 500), $response, 'summary');
            
            return ['success' => true, 'response' => $response, 'cached' => false];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al resumir el contenido: ' . $e->getMessage()];
        }
    }
    
    /**
     * Ayuda con tareas académicas
     */
    public function academicHelp($userId, $task, $subject) {
        // Verificar límites del usuario
        $limits = $this->checkUserLimits($userId);
        if (!$limits['success']) {
            return $limits;
        }
        
        // Verificar caché
        $cacheHash = $this->cache->generateHash($task . '_' . $subject, 'academic');
        $cachedResponse = $this->cache->get($cacheHash);
        
        if ($cachedResponse) {
            return ['success' => true, 'response' => $cachedResponse, 'cached' => true];
        }
        
        try {
            $prompt = "COMO TUTOR ACADÉMICO EXPERTO EN $subject: Guía al estudiante paso a paso para resolver la siguiente tarea. No hagas la tarea, sino enseña a resolverla.

TAREA DEL ESTUDIANTE: " . $task . "
MATERIA: $subject

FORMATO DE RESPUESTA OBLIGATORIO:
🎓 **CONCEPTOS CLAVE**
• [Concepto 1]: [Breve definición]
• [Concepto 2]: [Breve definición]
• [Concepto 3]: [Breve definición]

📝 **PASOS METODOLÓGICOS**
Paso 1: [Explicación detallada del primer paso]
Paso 2: [Explicación detallada del segundo paso]
Paso 3: [Explicación detallada del tercer paso]
Paso 4: [Explicación detallada del cuarto paso]
Paso 5: [Explicación detallada del quinto paso]

📚 **RECURSOS RECOMENDADOS**
📖 [Libro o texto de referencia]
🌐 [Sitio web o recurso online]
📹 [Video o material audiovisual]
📝 [Ejercicio práctico sugerido]

💡 **CONSEJOS PRÁCTICOS**
→ [Consejo 1 para aplicar el conocimiento]
→ [Consejo 2 para evitar errores comunes]

IMPORTANTE: Enfócate en enseñar el método, no en dar la respuesta final. Responde ÚNICAMENTE en el formato indicado.";
            
            $response = $this->callGeminiAPI($prompt);
            
            // Guardar en caché
            $this->cache->set($cacheHash, $response);
            
            // Registrar petición
            $this->logRequest($userId, $task . '_' . $subject, $response, 'academic');
            
            return ['success' => true, 'response' => $response, 'cached' => false];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al proporcionar ayuda académica: ' . $e->getMessage()];
        }
    }
    
    /**
     * Obtener límites actuales del usuario
     */
    public function getUserCurrentLimits($userId) {
        try {
            // Verificar si la tabla ai_role_limits existe
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'ai_role_limits'");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                // Obtener rol y límites del usuario desde la tabla de límites
                $stmt = $this->pdo->prepare("
                    SELECT u.rol, arl.daily_limit, arl.hourly_limit
                    FROM usuarios u
                    LEFT JOIN ai_role_limits arl ON u.rol = arl.role_name AND arl.is_active = TRUE
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($userData && $userData['daily_limit']) {
                    $dailyLimit = $userData['daily_limit'];
                    $hourlyLimit = $userData['hourly_limit'];
                    $role = $userData['rol'];
                } else {
                    // Fallback si no hay configuración para el rol
                    list($dailyLimit, $hourlyLimit, $role) = $this->getDefaultLimits($userData['rol'] ?? 'usuario');
                }
            } else {
                // Fallback si la tabla no existe - usar límites por rol hardcodeados
                $stmt = $this->pdo->prepare("SELECT rol FROM usuarios WHERE id = ?");
                $stmt->execute([$userId]);
                $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                list($dailyLimit, $hourlyLimit, $role) = $this->getDefaultLimits($userData['rol'] ?? 'usuario');
            }
            
            // Obtener uso actual
            $stmt = $this->pdo->prepare("
                SELECT 
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as daily_used,
                    COUNT(CASE WHEN created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as hourly_used
                FROM ai_requests 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'daily_limit' => $dailyLimit,
                'hourly_limit' => $hourlyLimit,
                'daily_used' => $usage['daily_used'] ?? 0,
                'hourly_used' => $usage['hourly_used'] ?? 0,
                'role' => $role
            ];
            
        } catch (Exception $e) {
            error_log('Error en getUserCurrentLimits: ' . $e->getMessage());
            // Fallback total
            return [
                'daily_limit' => 50,
                'hourly_limit' => 10,
                'daily_used' => 0,
                'hourly_used' => 0,
                'role' => 'usuario'
            ];
        }
    }
    
    /**
     * Obtener todos los límites por rol
     */
    public function getRoleLimits() {
        try {
            // Verificar si la tabla ai_role_limits existe
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'ai_role_limits'");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if ($tableExists) {
                $stmt = $this->pdo->prepare("
                    SELECT role_name, daily_limit, hourly_limit, description, is_active
                    FROM ai_role_limits 
                    ORDER BY role_name
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                // Fallback - retornar límites por defecto
                return [
                    ['role_name' => 'Super Admin', 'daily_limit' => 999999, 'hourly_limit' => 999999, 'description' => 'Super Administrador - Sin límites', 'is_active' => 1],
                    ['role_name' => 'Administrativo', 'daily_limit' => 50, 'hourly_limit' => 10, 'description' => 'Personal administrativo - 50 diarias, 10 por hora', 'is_active' => 1],
                    ['role_name' => 'Docente', 'daily_limit' => 30, 'hourly_limit' => 6, 'description' => 'Docentes - 30 diarias, 6 por hora', 'is_active' => 1],
                    ['role_name' => 'Estudiante', 'daily_limit' => 15, 'hourly_limit' => 3, 'description' => 'Estudiantes - 15 diarias, 3 por hora', 'is_active' => 1]
                ];
            }
        } catch (Exception $e) {
            error_log('Error en getRoleLimits: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Actualizar límites por rol
     */
    public function updateRoleLimits($limits) {
        try {
            // Verificar si la tabla ai_role_limits existe
            $stmt = $this->pdo->prepare("SHOW TABLES LIKE 'ai_role_limits'");
            $stmt->execute();
            $tableExists = $stmt->rowCount() > 0;
            
            if (!$tableExists) {
                return ['success' => false, 'message' => 'La tabla de límites no existe. Ejecuta el script SQL primero.'];
            }
            
            $this->pdo->beginTransaction();
            
            foreach ($limits as $limit) {
                $stmt = $this->pdo->prepare("
                    UPDATE ai_role_limits 
                    SET daily_limit = ?, 
                        hourly_limit = ?, 
                        updated_at = CURRENT_TIMESTAMP
                    WHERE role_name = ?
                ");
                $stmt->execute([
                    $limit['daily_limit'],
                    $limit['hourly_limit'],
                    $limit['role_name']
                ]);
            }
            
            $this->pdo->commit();
            return ['success' => true, 'message' => 'Límites actualizados correctamente'];
            
        } catch (Exception $e) {
            if (isset($this->pdo) && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Error en updateRoleLimits: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error actualizando límites: ' . $e->getMessage()];
        }
    }
    
    /**
     * Limpiar datos antiguos según políticas de retención por rol
     */
    public function cleanupOldData() {
        // Configuración de retención por rol (en días)
        $retentionPolicies = [
            'Super Admin' => 0,      // Nunca se borra
            'Administrativo' => 30,  // 1 mes
            'Docente' => 14,         // 2 semanas
            'Estudiante' => 7        // 1 semana
        ];
        
        $totalDeleted = 0;
        
        foreach ($retentionPolicies as $role => $days) {
            if ($days == 0) continue; // Skip Super Admin
            
            try {
                $stmt = $this->pdo->prepare("
                    DELETE ar FROM ai_requests ar
                    JOIN usuarios u ON ar.user_id = u.id
                    WHERE u.rol = ? 
                    AND ar.created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                ");
                $stmt->execute([$role, $days]);
                $totalDeleted += $stmt->rowCount();
                
                error_log("Limpieza AI - Rol $role: {$stmt->rowCount()} registros eliminados (más antiguos de $days días)");
                
            } catch (Exception $e) {
                error_log("Error limpiando datos para rol $role: " . $e->getMessage());
            }
        }
        
        error_log("Limpieza AI completada: $totalDeleted registros eliminados en total");
        
        return [
            'success' => true,
            'deleted' => $totalDeleted,
            'policies' => $retentionPolicies
        ];
    }
    
    /**
     * Obtener estadísticas de almacenamiento por rol
     */
    public function getStorageStatsByRole() {
        $stmt = $this->pdo->query("
            SELECT 
                u.rol,
                COUNT(*) as requests_count,
                AVG(LENGTH(ar.response)) as avg_response_length,
                MIN(ar.created_at) as oldest_request,
                MAX(ar.created_at) as newest_request,
                SUM(LENGTH(ar.prompt) + LENGTH(ar.response)) as total_chars
            FROM ai_requests ar
            JOIN usuarios u ON ar.user_id = u.id
            GROUP BY u.rol
            ORDER BY u.rol
        ");
        
        $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular tamaño en MB para cada rol
        foreach ($stats as &$stat) {
            $stat['size_mb'] = round($stat['total_chars'] / 1024 / 1024, 2);
            $stat['oldest_request'] = $stat['oldest_request'] ? date('Y-m-d H:i:s', strtotime($stat['oldest_request'])) : null;
            $stat['newest_request'] = $stat['newest_request'] ? date('Y-m-d H:i:s', strtotime($stat['newest_request'])) : null;
        }
        
        return $stats;
    }
    
    /**
     * Obtener estadísticas de uso del usuario
     */
    public function getUserStats($userId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_requests,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                MAX(created_at) as last_request,
                type,
                COUNT(*) as requests_by_type
            FROM ai_requests 
            WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY type
        ");
        $stmt->execute([$userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
