<?php
// Cargar variables de entorno
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') === false) {
            continue;
        }
        
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Cargar el archivo .env
loadEnv(__DIR__ . '/../.env');

// Configuración de IA - Gemini (desde variables de entorno)
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent');

// Configuración del sistema (con valores por defecto y sobreescribibles)
define('AI_MAX_TOKENS', (int)(getenv('AI_MAX_TOKENS') ?: 1000));
define('AI_TEMPERATURE', (float)(getenv('AI_TEMPERATURE') ?: 0.7));
define('AI_TOP_P', (float)(getenv('AI_TOP_P') ?: 0.8));
define('AI_TOP_K', (int)(getenv('AI_TOP_K') ?: 40));

// Límites de uso por usuario (para controlar costos)
define('AI_MAX_REQUESTS_PER_DAY', (int)(getenv('AI_MAX_REQUESTS_PER_DAY') ?: 50));
define('AI_MAX_REQUESTS_PER_HOUR', (int)(getenv('AI_MAX_REQUESTS_PER_HOUR') ?: 10));

// Sistema de caché (para reducir llamadas a la API)
define('AI_CACHE_DURATION', (int)(getenv('AI_CACHE_DURATION') ?: 3600)); // 1 hora en segundos

// Configuración de Redis
define('REDIS_HOST', getenv('REDIS_HOST') ?: '127.0.0.1');
define('REDIS_PORT', (int)(getenv('REDIS_PORT') ?: 6379));
define('REDIS_PASSWORD', getenv('REDIS_PASSWORD') ?: '');
define('REDIS_DB', (int)(getenv('REDIS_DB') ?: 0));
define('REDIS_PREFIX', getenv('REDIS_PREFIX') ?: 'ai_cache:');

// Validación crítica
if (empty(GEMINI_API_KEY)) {
    error_log('ERROR: GEMINI_API_KEY no está configurada. Revisa el archivo .env');
}
?>
