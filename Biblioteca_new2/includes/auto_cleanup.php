<?php


require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ai_service.php';

class AutoCleanupManager {
    private $pdo;
    private $lastCleanupFile;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->lastCleanupFile = __DIR__ . '/last_cleanup.txt';
    }
    
    /**
     * Verificar si es el momento adecuado para la limpieza (Domingo 23:55)
     */
    public function shouldRunCleanup() {
        $currentDateTime = new DateTime();
        $currentDay = $currentDateTime->format('N'); // 1=Lunes, 7=Domingo
        $currentTime = $currentDateTime->format('H:i'); // Formato 24:00
        
        // Verificar si es domingo (7) y son las 23:55 o más tarde
        $isSunday = $currentDay == '7';
        $isTime = $currentTime >= '23:55';
        
        // Si no es domingo o no es la hora, no ejecutar
        if (!$isSunday || !$isTime) {
            return false;
        }
        
        // Si ya se ejecutó hoy, no volver a ejecutar
        if (file_exists($this->lastCleanupFile)) {
            $lastCleanup = file_get_contents($this->lastCleanupFile);
            $lastCleanupDate = date('Y-m-d', strtotime($lastCleanup));
            $currentDate = date('Y-m-d');
            
            if ($lastCleanupDate === $currentDate) {
                return false; // Ya se ejecutó hoy
            }
        }
        
        return true;
    }
    
    /**
     * Verificar si es domingo para mostrar aviso
     */
    public function isCleanupTime() {
        $currentDateTime = new DateTime();
        $currentDay = $currentDateTime->format('N');
        $currentTime = $currentDateTime->format('H:i');
        
        return $currentDay == '7' && $currentTime >= '23:55';
    }
    
    /**
     * Obtener tiempo restante para la próxima limpieza
     */
    public function getNextCleanupTime() {
        $currentDateTime = new DateTime();
        $currentDay = $currentDateTime->format('N'); // 1=Lunes, 7=Domingo
        $currentDayName = $currentDateTime->format('l'); // Nombre del día
        $currentTime = $currentDateTime->format('H:i');
        
        // Calcular días hasta el próximo domingo de forma simple
        if ($currentDay == '7') { // Si ya es domingo
            $daysUntilSunday = 7; 
        } else {
            $daysUntilSunday = 7 - $currentDay; 
        }
        
        // Crear fecha del próximo domingo a las 23:55
        $nextSunday = clone $currentDateTime;
        $nextSunday->modify("next sunday 23:55");
        
        // Calcular diferencia de forma simple
        $interval = $currentDateTime->diff($nextSunday);
        $totalDays = $interval->days;
        
        // Si la diferencia es 0 pero hay horas, contar como 1 día
        if ($totalDays == 0 && ($interval->h > 0 || $interval->i > 0)) {
            $totalDays = 1;
        }
        
        // Depuración
        error_log("DEBUG: Fecha actual: " . $currentDateTime->format('Y-m-d H:i:s'));
        error_log("DEBUG: Día de la semana: $currentDayName (número: $currentDay)");
        error_log("DEBUG: Próximo domingo: " . $nextSunday->format('Y-m-d H:i:s'));
        error_log("DEBUG: Días hasta domingo (fórmula): $daysUntilSunday");
        error_log("DEBUG: Diferencia DateTime: {$interval->days} días, {$interval->h} horas, {$interval->i} minutos");
        error_log("DEBUG: Total días final: $totalDays");
        
        return [
            'datetime' => $nextSunday->format('Y-m-d H:i'),
            'days_remaining' => max(1, $totalDays),
            'hours_remaining' => $interval->h,
            'minutes_remaining' => $interval->i
        ];
    }
    
    /**
     * Ejecutar limpieza si es necesario
     */
    public function runAutoCleanup() {
        if (!$this->shouldRunCleanup()) {
            return ['executed' => false, 'reason' => 'not_time_yet'];
        }
        
        try {
            $aiService = new AIService();
            $result = $aiService->cleanupOldData();
            
            // Actualizar timestamp de última limpieza
            file_put_contents($this->lastCleanupFile, date('Y-m-d H:i:s'));
            
            // Registrar en log
            error_log("AutoCleanup Domingo 23:55 ejecutado: {$result['deleted']} registros eliminados");
            
            return [
                'executed' => true,
                'result' => $result,
                'timestamp' => date('Y-m-d H:i:s'),
                'type' => 'scheduled_sunday'
            ];
            
        } catch (Exception $e) {
            error_log("Error en AutoCleanup: " . $e->getMessage());
            return ['executed' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Forzar ejecución manual (para admin)
     */
    public function forceCleanup() {
        try {
            $aiService = new AIService();
            $result = $aiService->cleanupOldData();
            
            // Actualizar timestamp
            file_put_contents($this->lastCleanupFile, date('Y-m-d H:i:s'));
            
            return [
                'executed' => true,
                'result' => $result,
                'timestamp' => date('Y-m-d H:i:s'),
                'forced' => true
            ];
            
        } catch (Exception $e) {
            return ['executed' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Obtener estado de la última limpieza
     */
    public function getLastCleanupStatus() {
        if (!file_exists($this->lastCleanupFile)) {
            return [
                'last_cleanup' => null,
                'next_cleanup' => $this->getNextCleanupTime(),
                'days_since' => null,
                'is_sunday_night' => $this->isCleanupTime()
            ];
        }
        
        $lastCleanup = file_get_contents($this->lastCleanupFile);
        $lastCleanupTime = strtotime($lastCleanup);
        $currentTime = time();
        $daysSince = floor(($currentTime - $lastCleanupTime) / 86400);
        
        return [
            'last_cleanup' => $lastCleanup,
            'days_since' => $daysSince,
            'next_cleanup' => $this->getNextCleanupTime(),
            'is_sunday_night' => $this->isCleanupTime()
        ];
    }
}

// Integración automática en el sistema
function initializeAutoCleanup() {
    // Solo ejecutar para usuarios autenticados para no sobrecargar
    if (isset($_SESSION['usuario_id'])) {
        $cleanupManager = new AutoCleanupManager();
        
        // Ejecutar limpieza si es domingo 23:55 (en modo silencioso)
        $result = $cleanupManager->runAutoCleanup();
        
        // Opcional: mostrar notificación solo a Super Admin
        if ($_SESSION['rol'] === 'Super Admin' && $result['executed']) {
            $_SESSION['cleanup_notification'] = [
                'type' => 'info',
                'message' => "🧹 Limpieza dominical ejecutada: {$result['result']['deleted']} registros eliminados"
            ];
        }
    }
}

// Ejecutar automáticamente al cargar la página
initializeAutoCleanup();
?>
