<?php
/**
 * Attendance Model
 */
class Attendance extends Model {
    protected $table = 'registros_asistencia';
    protected $primaryKey = 'id';
    protected $fillable = [
        'usuario_id', 'ubicacion_id', 'tipo', 'fecha_hora',
        'latitud_registro', 'longitud_registro', 'precision_gps',
        'dentro_geofence', 'distancia_ubicacion', 'metodo_registro',
        'direccion_ip', 'user_agent', 'dispositivo_id', 'foto_registro',
        'notas', 'editado', 'editado_por', 'editado_en', 'razon_edicion'
    ];

    /**
     * Clock in (register entry)
     */
    public function clockIn($userId, $locationId, $lat, $lon, $precision = null, $withinGeofence = true, $distance = null) {
        // Check for active session
        $sql = "SELECT COUNT(*) as count FROM sesiones_trabajo WHERE usuario_id = :user_id AND estado = 'activa'";
        $result = $this->db->selectOne($sql, ['user_id' => $userId]);
        if ($result && $result['count'] > 0) {
            return ['error' => 'active_session_exists'];
        }

        try {
            $this->db->beginTransaction();

            // Insert attendance record
            $attendanceId = $this->create([
                'usuario_id' => $userId,
                'ubicacion_id' => $locationId,
                'tipo' => 'entrada',
                'fecha_hora' => getCurrentDateTime(),
                'latitud_registro' => $lat,
                'longitud_registro' => $lon,
                'precision_gps' => $precision,
                'dentro_geofence' => $withinGeofence ? 1 : 0,
                'distancia_ubicacion' => $distance,
                'metodo_registro' => 'web',
                'direccion_ip' => getUserIP(),
                'user_agent' => getUserAgent()
            ]);

            // Create work session
            $sessionData = [
                'usuario_id' => $userId,
                'entrada_id' => $attendanceId,
                'ubicacion_id' => $locationId,
                'fecha_inicio' => getCurrentDate(),
                'hora_entrada' => getCurrentDateTime(),
                'estado' => 'activa'
            ];

            $sessionId = $this->db->insert('sesiones_trabajo', $sessionData);

            $this->db->commit();

            // Log activity
            logActivity('clock_in', [
                'user_id' => $userId,
                'location_id' => $locationId,
                'attendance_id' => $attendanceId,
                'session_id' => $sessionId
            ]);

            return [
                'success' => true,
                'attendance_id' => $attendanceId,
                'session_id' => $sessionId
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            logError('Clock in failed: ' . $e->getMessage());
            return ['error' => 'database_error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Clock out (register exit)
     */
    public function clockOut($userId, $locationId = null, $lat = null, $lon = null, $precision = null, $withinGeofence = true, $distance = null) {
        // Get active session
        $sql = "SELECT * FROM sesiones_trabajo WHERE usuario_id = :user_id AND estado = 'activa' LIMIT 1";
        $session = $this->db->selectOne($sql, ['user_id' => $userId]);

        if (!$session) {
            return ['error' => 'no_active_session'];
        }

        try {
            $this->db->beginTransaction();

            // Insert exit record
            $attendanceId = $this->create([
                'usuario_id' => $userId,
                'ubicacion_id' => $locationId ?: $session['ubicacion_id'],
                'tipo' => 'salida',
                'fecha_hora' => getCurrentDateTime(),
                'latitud_registro' => $lat,
                'longitud_registro' => $lon,
                'precision_gps' => $precision,
                'dentro_geofence' => $withinGeofence ? 1 : 0,
                'distancia_ubicacion' => $distance,
                'metodo_registro' => 'web',
                'direccion_ip' => getUserIP(),
                'user_agent' => getUserAgent()
            ]);

            // Calculate duration
            $duration = $this->calculateDuration($session['hora_entrada'], getCurrentDateTime());

            // Update work session
            $updateData = [
                'salida_id' => $attendanceId,
                'hora_salida' => getCurrentDateTime(),
                'duracion_minutos' => $duration['total_minutes'],
                'duracion_efectiva_minutos' => $duration['total_minutes'], // Can be adjusted for breaks
                'estado' => 'completada'
            ];

            $sql = "UPDATE sesiones_trabajo SET
                    salida_id = :salida_id,
                    hora_salida = :hora_salida,
                    duracion_minutos = :duracion_minutos,
                    duracion_efectiva_minutos = :duracion_efectiva_minutos,
                    estado = :estado
                    WHERE id = :id";

            $updateData['id'] = $session['id'];
            $this->db->query($sql, $updateData);

            $this->db->commit();

            // Log activity
            logActivity('clock_out', [
                'user_id' => $userId,
                'location_id' => $locationId,
                'attendance_id' => $attendanceId,
                'session_id' => $session['id'],
                'duration_minutes' => $duration['total_minutes']
            ]);

            return [
                'success' => true,
                'attendance_id' => $attendanceId,
                'session_id' => $session['id'],
                'duration' => $duration
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            logError('Clock out failed: ' . $e->getMessage());
            return ['error' => 'database_error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate duration between two times
     */
    private function calculateDuration($start, $end) {
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        $diff = $endTime - $startTime;

        return [
            'total_minutes' => round($diff / 60),
            'hours' => floor($diff / 3600),
            'minutes' => floor(($diff % 3600) / 60),
            'formatted' => sprintf("%02d:%02d", floor($diff / 3600), floor(($diff % 3600) / 60))
        ];
    }

    /**
     * Get user attendance history
     */
    public function getUserHistory($userId, $startDate = null, $endDate = null, $limit = null) {
        $params = ['user_id' => $userId];
        $sql = "SELECT ra.*, u.nombre as ubicacion_nombre,
                    st.duracion_minutos, st.estado as sesion_estado
                FROM registros_asistencia ra
                LEFT JOIN ubicaciones u ON ra.ubicacion_id = u.id
                LEFT JOIN sesiones_trabajo st ON
                    (ra.tipo = 'entrada' AND st.entrada_id = ra.id) OR
                    (ra.tipo = 'salida' AND st.salida_id = ra.id)
                WHERE ra.usuario_id = :user_id";

        if ($startDate) {
            $sql .= " AND ra.fecha_local >= :start_date";
            $params['start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND ra.fecha_local <= :end_date";
            $params['end_date'] = $endDate;
        }

        $sql .= " ORDER BY ra.fecha_hora DESC";

        if ($limit) {
            $sql .= " LIMIT $limit";
        }

        return $this->db->select($sql, $params);
    }

    /**
     * Get attendance by date range
     */
    public function getByDateRange($startDate, $endDate, $locationId = null) {
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate
        ];

        $sql = "SELECT ra.*, u.nombre, u.apellidos, u.numero_empleado,
                    ub.nombre as ubicacion_nombre
                FROM registros_asistencia ra
                JOIN usuarios u ON ra.usuario_id = u.id
                LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
                WHERE ra.fecha_local BETWEEN :start_date AND :end_date";

        if ($locationId) {
            $sql .= " AND ra.ubicacion_id = :location_id";
            $params['location_id'] = $locationId;
        }

        $sql .= " ORDER BY ra.fecha_hora DESC";

        return $this->db->select($sql, $params);
    }

    /**
     * Get daily summary
     */
    public function getDailySummary($date = null, $locationId = null) {
        if (!$date) $date = getCurrentDate();

        $params = ['date' => $date];
        $sql = "SELECT
                    COUNT(DISTINCT usuario_id) as empleados_total,
                    COUNT(CASE WHEN tipo = 'entrada' THEN 1 END) as total_entradas,
                    COUNT(CASE WHEN tipo = 'salida' THEN 1 END) as total_salidas,
                    COUNT(CASE WHEN dentro_geofence = 0 THEN 1 END) as fuera_geofence
                FROM registros_asistencia
                WHERE fecha_local = :date";

        if ($locationId) {
            $sql .= " AND ubicacion_id = :location_id";
            $params['location_id'] = $locationId;
        }

        $summary = $this->db->selectOne($sql, $params);

        // Get active sessions
        $sql = "SELECT COUNT(*) as sesiones_activas
                FROM sesiones_trabajo
                WHERE DATE(hora_entrada) = :date AND estado = 'activa'";

        $active = $this->db->selectOne($sql, ['date' => $date]);
        $summary['sesiones_activas'] = $active['sesiones_activas'];

        return $summary;
    }

    /**
     * Edit attendance record
     */
    public function editRecord($recordId, $data, $editedBy, $reason) {
        $data['editado'] = 1;
        $data['editado_por'] = $editedBy;
        $data['editado_en'] = getCurrentDateTime();
        $data['razon_edicion'] = $reason;

        return $this->update($recordId, $data);
    }

    /**
     * Get late arrivals
     */
    public function getLateArrivals($date = null, $locationId = null) {
        if (!$date) $date = getCurrentDate();

        $params = ['date' => $date];
        $sql = "SELECT ra.*, u.nombre, u.apellidos, u.numero_empleado,
                    ub.nombre as ubicacion_nombre, ub.horario_apertura,
                    TIME(ra.fecha_hora) as hora_entrada
                FROM registros_asistencia ra
                JOIN usuarios u ON ra.usuario_id = u.id
                JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
                WHERE ra.fecha_local = :date
                    AND ra.tipo = 'entrada'
                    AND TIME(ra.fecha_hora) > ub.horario_apertura";

        if ($locationId) {
            $sql .= " AND ra.ubicacion_id = :location_id";
            $params['location_id'] = $locationId;
        }

        $sql .= " ORDER BY ra.fecha_hora";

        return $this->db->select($sql, $params);
    }
}