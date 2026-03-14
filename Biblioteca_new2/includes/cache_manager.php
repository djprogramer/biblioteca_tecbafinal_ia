<?php
require_once 'ai_config.php';

class CacheManager {
    private $redis = null;
    private $useRedis = false;
    
    public function __construct() {
        $this->initializeRedis();
    }
    
    /**
     * Inicializar conexión a Redis
     */
    private function initializeRedis() {
        try {
            if (!class_exists('Redis')) {
                error_log('Redis extension no está instalada. Usando caché de archivos.');
                return;
            }
            
            $this->redis = new Redis();
            
            // Conectar a Redis
            $connected = $this->redis->connect(REDIS_HOST, REDIS_PORT, 2);
            
            if (!$connected) {
                throw new Exception('No se pudo conectar a Redis');
            }
            
            // Autenticar si hay contraseña
            if (!empty(REDIS_PASSWORD)) {
                if (!$this->redis->auth(REDIS_PASSWORD)) {
                    throw new Exception('Error de autenticación en Redis');
                }
            }
            
            // Seleccionar base de datos
            $this->redis->select(REDIS_DB);
            
            // Probar conexión
            $this->redis->ping();
            
            $this->useRedis = true;
            error_log('Conectado a Redis exitosamente');
            
        } catch (Exception $e) {
            error_log('Error conectando a Redis: ' . $e->getMessage() . '. Usando caché de archivos.');
            $this->redis = null;
            $this->useRedis = false;
        }
    }
    
    /**
     * Generar hash para clave de caché
     */
    public function generateHash($data, $type) {
        return md5($data . $type);
    }
    
    /**
     * Guardar en caché
     */
    public function set($hash, $response, $duration = null) {
        $duration = $duration ?: AI_CACHE_DURATION;
        $key = REDIS_PREFIX . $hash;
        
        if ($this->useRedis && $this->redis) {
            try {
                $data = [
                    'response' => $response,
                    'timestamp' => time(),
                    'cached' => true
                ];
                
                $result = $this->redis->setex($key, $duration, json_encode($data));
                
                if (!$result) {
                    throw new Exception('Error guardando en Redis');
                }
                
                return true;
                
            } catch (Exception $e) {
                error_log('Error guardando en Redis: ' . $e->getMessage() . '. Usando archivos.');
                return $this->setToFile($hash, $response, $duration);
            }
        }
        
        return $this->setToFile($hash, $response, $duration);
    }
    
    /**
     * Obtener desde caché
     */
    public function get($hash) {
        $key = REDIS_PREFIX . $hash;
        
        if ($this->useRedis && $this->redis) {
            try {
                $data = $this->redis->get($key);
                
                if ($data === false) {
                    return null;
                }
                
                $decoded = json_decode($data, true);
                
                if (!$decoded || !isset($decoded['timestamp'])) {
                    return null;
                }
                
                // Verificar si el caché aún es válido
                if (time() - $decoded['timestamp'] > AI_CACHE_DURATION) {
                    $this->redis->del($key);
                    return null;
                }
                
                return $decoded['response'];
                
            } catch (Exception $e) {
                error_log('Error leyendo desde Redis: ' . $e->getMessage() . '. Usando archivos.');
                return $this->getFromFile($hash);
            }
        }
        
        return $this->getFromFile($hash);
    }
    
    /**
     * Eliminar de caché
     */
    public function delete($hash) {
        $key = REDIS_PREFIX . $hash;
        
        if ($this->useRedis && $this->redis) {
            try {
                return $this->redis->del($key) > 0;
            } catch (Exception $e) {
                error_log('Error eliminando de Redis: ' . $e->getMessage());
            }
        }
        
        return $this->deleteFromFile($hash);
    }
    
    /**
     * Limpiar caché antiguo
     */
    public function cleanup() {
        if ($this->useRedis && $this->redis) {
            try {
                $keys = $this->redis->keys(REDIS_PREFIX . '*');
                $removed = 0;
                
                foreach ($keys as $key) {
                    $data = $this->redis->get($key);
                    if ($data) {
                        $decoded = json_decode($data, true);
                        if ($decoded && isset($decoded['timestamp'])) {
                            if (time() - $decoded['timestamp'] > AI_CACHE_DURATION) {
                                $this->redis->del($key);
                                $removed++;
                            }
                        }
                    }
                }
                
                error_log("Limpiados $removed keys de Redis");
                return $removed;
                
            } catch (Exception $e) {
                error_log('Error limpiando Redis: ' . $e->getMessage());
            }
        }
        
        return $this->cleanupFiles();
    }
    
    /**
     * Obtener estadísticas del caché
     */
    public function getStats() {
        if ($this->useRedis && $this->redis) {
            try {
                $keys = $this->redis->keys(REDIS_PREFIX . '*');
                $info = $this->redis->info('memory');
                
                return [
                    'type' => 'Redis',
                    'keys' => count($keys),
                    'memory_used' => $info['used_memory_human'] ?? 'N/A',
                    'connected' => true
                ];
                
            } catch (Exception $e) {
                error_log('Error obteniendo stats de Redis: ' . $e->getMessage());
            }
        }
        
        return $this->getFileStats();
    }
    
    /**
     * Guardar en archivo (fallback)
     */
    private function setToFile($hash, $response, $duration) {
        $cacheFile = __DIR__ . '/../cache/ai_' . $hash . '.json';
        $cacheDir = dirname($cacheFile);
        
        if (!file_exists($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $data = [
            'response' => $response,
            'timestamp' => time(),
            'cached' => true
        ];
        
        return file_put_contents($cacheFile, json_encode($data)) !== false;
    }
    
    /**
     * Obtener desde archivo (fallback)
     */
    private function getFromFile($hash) {
        $cacheFile = __DIR__ . '/../cache/ai_' . $hash . '.json';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $data = json_decode(file_get_contents($cacheFile), true);
        
        if (!$data || !isset($data['timestamp'])) {
            return null;
        }
        
        // Verificar si el caché aún es válido
        if (time() - $data['timestamp'] > AI_CACHE_DURATION) {
            unlink($cacheFile);
            return null;
        }
        
        return $data['response'];
    }
    
    /**
     * Eliminar archivo (fallback)
     */
    private function deleteFromFile($hash) {
        $cacheFile = __DIR__ . '/../cache/ai_' . $hash . '.json';
        
        if (file_exists($cacheFile)) {
            return unlink($cacheFile);
        }
        
        return false;
    }
    
    /**
     * Limpiar archivos antiguos (fallback)
     */
    private function cleanupFiles() {
        $cacheDir = __DIR__ . '/../cache';
        $removed = 0;
        
        if (file_exists($cacheDir)) {
            $files = glob($cacheDir . '/ai_*.json');
            
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                
                if ($data && isset($data['timestamp'])) {
                    if (time() - $data['timestamp'] > AI_CACHE_DURATION) {
                        unlink($file);
                        $removed++;
                    }
                }
            }
        }
        
        error_log("Limpiados $removed archivos de caché");
        return $removed;
    }
    
    /**
     * Estadísticas de archivos (fallback)
     */
    private function getFileStats() {
        $cacheDir = __DIR__ . '/../cache';
        $fileCount = 0;
        $totalSize = 0;
        
        if (file_exists($cacheDir)) {
            $files = glob($cacheDir . '/ai_*.json');
            $fileCount = count($files);
            
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }
        }
        
        return [
            'type' => 'Files',
            'keys' => $fileCount,
            'memory_used' => $this->formatBytes($totalSize),
            'connected' => false
        ];
    }
    
    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Verificar si Redis está disponible
     */
    public function isRedisAvailable() {
        return $this->useRedis && $this->redis;
    }
}
?>
