<?php
/**
 * Location Model
 */
class Location extends Model {
    protected $table = 'ubicaciones';
    protected $primaryKey = 'id';
    protected $fillable = [
        'nombre', 'codigo', 'direccion', 'ciudad', 'estado',
        'codigo_postal', 'latitud', 'longitud', 'radio_metros',
        'tipo_ubicacion', 'horario_apertura', 'horario_cierre',
        'dias_laborales', 'requiere_foto', 'activa', 'creado_por'
    ];

    /**
     * Get active locations
     */
    public function getActiveLocations() {
        return $this->where(['activa' => 1], 'nombre', 'ASC');
    }

    /**
     * Find location by code
     */
    public function findByCode($code) {
        return $this->findBy('codigo', $code);
    }

    /**
     * Get users assigned to location
     */
    public function getUsers($locationId) {
        $sql = "SELECT u.*, ul.es_principal
                FROM usuarios u
                JOIN usuarios_ubicaciones ul ON u.id = ul.usuario_id
                WHERE ul.ubicacion_id = :location_id AND u.activo = 1
                ORDER BY u.nombre";

        return $this->db->select($sql, ['location_id' => $locationId]);
    }

    /**
     * Check if coordinates are within any active location
     */
    public function findLocationByCoordinates($lat, $lon) {
        $activeLocations = $this->getActiveLocations();

        foreach ($activeLocations as $location) {
            if (isWithinGeofence($lat, $lon, $location['latitud'], $location['longitud'], $location['radio_metros'])) {
                $distance = calculateDistance($lat, $lon, $location['latitud'], $location['longitud']);
                $location['distancia'] = round($distance);
                return $location;
            }
        }

        return null;
    }

    /**
     * Get nearest location to coordinates
     */
    public function getNearestLocation($lat, $lon) {
        $activeLocations = $this->getActiveLocations();
        $nearest = null;
        $minDistance = PHP_INT_MAX;

        foreach ($activeLocations as $location) {
            $distance = calculateDistance($lat, $lon, $location['latitud'], $location['longitud']);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $location;
                $nearest['distancia'] = round($distance);
            }
        }

        return $nearest;
    }

    /**
     * Check if location is open at given time
     */
    public function isOpen($locationId, $datetime = null) {
        if ($datetime === null) {
            $datetime = getCurrentDateTime();
        }

        $location = $this->find($locationId);
        if (!$location || !$location['activa']) {
            return false;
        }

        // Check day of week (1 = Monday, 7 = Sunday)
        $dayOfWeek = date('N', strtotime($datetime));
        $workDays = explode(',', $location['dias_laborales']);
        if (!in_array($dayOfWeek, $workDays)) {
            return false;
        }

        // Check time
        $time = date('H:i:s', strtotime($datetime));
        if ($location['horario_apertura'] && $location['horario_cierre']) {
            return $time >= $location['horario_apertura'] && $time <= $location['horario_cierre'];
        }

        return true; // No schedule restrictions
    }

    /**
     * Get location statistics
     */
    public function getStatistics($locationId, $startDate = null, $endDate = null) {
        if (!$startDate) $startDate = date('Y-m-01'); // First day of month
        if (!$endDate) $endDate = getCurrentDate();

        $sql = "SELECT
                    COUNT(DISTINCT usuario_id) as empleados_unicos,
                    COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) as total_entradas,
                    COUNT(CASE WHEN tipo = 'salida' THEN 1 END) as total_salidas,
                    COUNT(CASE WHEN dentro_geofence = 0 THEN 1 END) as fuera_geofence,
                    AVG(distancia_ubicacion) as distancia_promedio
                FROM registros_asistencia
                WHERE ubicacion_id = :location_id
                    AND fecha_local BETWEEN :start_date AND :end_date";

        $params = [
            'location_id' => $locationId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        return $this->db->selectOne($sql, $params);
    }

    /**
     * Get active employees at location
     */
    public function getActiveEmployees($locationId) {
        $sql = "SELECT u.*, st.hora_entrada,
                    TIMESTAMPDIFF(MINUTE, st.hora_entrada, NOW()) as minutos_trabajados
                FROM sesiones_trabajo st
                JOIN usuarios u ON st.usuario_id = u.id
                WHERE st.ubicacion_id = :location_id
                    AND st.estado = 'activa'
                ORDER BY st.hora_entrada DESC";

        return $this->db->select($sql, ['location_id' => $locationId]);
    }
}