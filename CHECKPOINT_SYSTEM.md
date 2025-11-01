# Checkpoint System - Multi-Location Tracking

## Overview

The Checkpoint System enables employees to check in and out multiple times per day at different authorized locations, providing accurate time tracking for workers who move between sites.

## Use Cases

### 1. Field Workers Visiting Multiple Sites
```
9:00 AM  → Check IN at Office A
11:00 AM → Check OUT from Office A (2 hours worked)
11:30 AM → Check IN at Client Site B
4:00 PM  → Check OUT from Client Site B (4.5 hours worked)
Total: 6.5 hours
```

### 2. Office Workers with Flexible Locations
```
8:00 AM  → Check IN at Main Office
12:00 PM → Quick Transfer to Branch Office
5:00 PM  → Check OUT from Branch Office
Total: 9 hours (with automatic transfer handling)
```

### 3. Construction/Project Workers
```
7:00 AM  → Check IN at Construction Site A
12:00 PM → Check OUT for lunch
1:00 PM  → Check IN at Construction Site B
6:00 PM  → Check OUT from Site B
Total: 10 hours across multiple sites
```

## Database Schema

### New Tables

#### `location_transfers`
Tracks movement between authorized locations.

| Column | Type | Description |
|--------|------|-------------|
| id | INT | Primary key |
| usuario_id | INT | User who transferred |
| from_ubicacion_id | INT | Previous location (NULL if first check-in) |
| to_ubicacion_id | INT | New location |
| from_registro_id | INT | Previous checkpoint record ID |
| to_registro_id | INT | New checkpoint record ID |
| transfer_time | DATETIME | When the transfer occurred |
| lat, lon | DECIMAL | GPS coordinates at transfer |
| precision_metros | FLOAT | GPS accuracy |
| transfer_reason | VARCHAR | Optional reason for transfer |
| validated | TINYINT | Whether transfer was validated |
| distance_from_previous | INT | Distance in meters from previous location |

### Modified Tables

#### `registros_asistencia` - New Columns
- `session_type` - ENUM('normal', 'checkpoint') - Type of attendance session
- `checkpoint_sequence` - INT - Order of checkpoint in the day (1, 2, 3...)
- `is_active` - TINYINT - Whether this checkpoint is currently active (open)

## API Endpoints

### POST `/api/checkpoint.php`

#### Action: `checkin`
Start a new checkpoint at a location.

**Request:**
```json
{
  "action": "checkin",
  "lat": 19.432608,
  "lng": -99.133209,
  "accuracy": 15.5,
  "location_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "action": "checkin",
  "message": "Check-in registrado exitosamente",
  "checkpoint_number": 1,
  "time": "09:00 AM",
  "registro_id": 156
}
```

#### Action: `checkout`
Close the current active checkpoint.

**Request:**
```json
{
  "action": "checkout",
  "lat": 19.432615,
  "lng": -99.133198,
  "accuracy": 12.3,
  "location_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "action": "checkout",
  "message": "Check-out registrado exitosamente",
  "checkpoint_number": 1,
  "hours_worked": 2.5,
  "total_daily_hours": 2.5,
  "time": "11:30 AM"
}
```

#### Action: `transfer`
Quick transfer: automatically checkout from current location and checkin to new location in one action.

**Request:**
```json
{
  "action": "transfer",
  "lat": 19.425608,
  "lng": -99.143209,
  "accuracy": 18.2,
  "location_id": 8,
  "reason": "Reunión con cliente"
}
```

**Response:**
```json
{
  "success": true,
  "action": "transfer",
  "message": "Transferencia registrada exitosamente",
  "time": "02:00 PM",
  "registro_id": 157
}
```

## Database Functions & Procedures

### Function: `calculate_checkpoint_hours(usuario_id, fecha)`
Calculates total hours worked across all checkpoints for a specific user and date.

```sql
SELECT calculate_checkpoint_hours(25, '2025-11-01');
-- Returns: 8.50 (hours)
```

### Procedure: `sp_transfer_location(...)`
Handles the complete transfer process:
1. Closes current active checkpoint
2. Creates new checkpoint at new location
3. Records transfer in location_transfers table
4. Creates marcaje entries

```sql
CALL sp_transfer_location(
    25,              -- usuario_id
    8,               -- new_ubicacion_id
    19.425608,       -- lat
    -99.143209,      -- lon
    18.2,            -- precision
    'Client meeting', -- reason
    @success,
    @message,
    @new_registro_id
);
SELECT @success, @message, @new_registro_id;
```

### View: `v_checkpoint_summary`
Provides daily summary of all checkpoints for reporting.

```sql
SELECT * FROM v_checkpoint_summary
WHERE fecha = CURDATE()
ORDER BY usuario_id;
```

Output:
```
usuario_id | nombre | fecha      | total_checkpoints | total_hours | checkpoint_route
-----------|--------|------------|-------------------|-------------|------------------
25         | Juan   | 2025-11-01 | 3                 | 8.5         | Oficina A (09:00-11:30) → Cliente B (12:00-16:00) → Oficina A (16:30-18:00)
```

## Automatic Behaviors

### Auto-Close Previous Checkpoint
When a new checkpoint is created, the trigger `before_checkpoint_insert` automatically:
1. Finds any active checkpoint for that user/date
2. Sets `hora_salida` to the new checkpoint's `hora_entrada`
3. Calculates `horas_trabajadas`
4. Marks it as inactive (`is_active = 0`)
5. Assigns proper `checkpoint_sequence` number

### Validation Rules
- GPS accuracy must be < 50 meters
- User must be within location's authorized radius
- Only one active checkpoint per user at a time
- Automatic sequence numbering (1, 2, 3, ...)

## Configuration Settings

System configuration in `configuracion_sistema` table:

| Setting | Default | Description |
|---------|---------|-------------|
| checkpoint_system_enabled | 1 | Enable/disable checkpoint system |
| checkpoint_min_interval_minutes | 30 | Minimum time between checkpoints |
| checkpoint_max_per_day | 10 | Maximum checkpoints per day |
| checkpoint_auto_close_enabled | 1 | Auto-close previous when starting new |

## Migration Instructions

### Apply the Migration

```bash
# Run the migration SQL
mysql -u asist_user -p asist_db < db_migration_checkpoint_system.sql

# Verify tables created
mysql -u asist_user -p asist_db -e "SHOW TABLES LIKE '%checkpoint%';"
mysql -u asist_user -p asist_db -e "DESCRIBE location_transfers;"
```

### Rollback (if needed)

The migration file includes a rollback script at the bottom:

```bash
# Extract and run rollback section
mysql -u asist_user -p asist_db < rollback_checkpoint_system.sql
```

## Reporting & Analytics

### Total Hours Per Day (All Checkpoints)
```sql
SELECT
    u.nombre,
    u.apellidos,
    ra.fecha,
    COUNT(*) as num_checkpoints,
    SUM(ra.horas_trabajadas) as total_horas,
    GROUP_CONCAT(ub.nombre ORDER BY ra.checkpoint_sequence) as locations_visited
FROM registros_asistencia ra
JOIN usuarios u ON ra.usuario_id = u.id
LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
WHERE ra.session_type = 'checkpoint'
    AND ra.fecha >= '2025-11-01'
GROUP BY u.id, ra.fecha
ORDER BY ra.fecha DESC, u.apellidos;
```

### Location Transfer Analytics
```sql
SELECT
    u.nombre,
    u.apellidos,
    DATE(lt.transfer_time) as fecha,
    COUNT(*) as num_transfers,
    AVG(lt.distance_from_previous) as avg_distance_meters,
    GROUP_CONCAT(
        CONCAT(ub_from.nombre, ' → ', ub_to.nombre)
        ORDER BY lt.transfer_time
        SEPARATOR ' | '
    ) as transfer_route
FROM location_transfers lt
JOIN usuarios u ON lt.usuario_id = u.id
LEFT JOIN ubicaciones ub_from ON lt.from_ubicacion_id = ub_from.id
JOIN ubicaciones ub_to ON lt.to_ubicacion_id = ub_to.id
WHERE lt.transfer_time >= '2025-11-01'
GROUP BY u.id, DATE(lt.transfer_time)
ORDER BY fecha DESC;
```

### Find Suspicious Patterns
```sql
-- Users with too many checkpoints in one day
SELECT
    u.nombre,
    ra.fecha,
    COUNT(*) as checkpoint_count
FROM registros_asistencia ra
JOIN usuarios u ON ra.usuario_id = u.id
WHERE ra.session_type = 'checkpoint'
GROUP BY ra.usuario_id, ra.fecha
HAVING COUNT(*) > 10
ORDER BY checkpoint_count DESC;

-- Very short checkpoints (< 15 minutes)
SELECT
    u.nombre,
    ra.fecha,
    ra.hora_entrada,
    ra.hora_salida,
    ra.horas_trabajadas,
    ub.nombre as ubicacion
FROM registros_asistencia ra
JOIN usuarios u ON ra.usuario_id = u.id
LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
WHERE ra.session_type = 'checkpoint'
    AND ra.horas_trabajadas < 0.25
ORDER BY ra.fecha DESC;
```

## Security Considerations

1. **GPS Validation**: All checkpoints validate GPS coordinates against authorized location radius
2. **Accuracy Check**: Minimum GPS accuracy enforced (< 50m)
3. **Audit Trail**: All actions logged in `marcajes` table
4. **IP Tracking**: IP address recorded for each checkpoint
5. **Device Fingerprinting**: User agent stored for fraud detection
6. **One Active Session**: Only one active checkpoint per user prevents double check-ins

## Next Steps

### Pending Implementation
- [ ] Update asistencias.php UI with checkpoint interface
- [ ] Add "Quick Transfer" button for easy location switching
- [ ] Update dashboard to display checkpoint timeline
- [ ] Modify reportes.php to show checkpoint breakdown
- [ ] Add mobile UI optimizations for field workers
- [ ] Implement push notifications for checkpoint reminders
- [ ] Add admin panel for checkpoint management

### Future Enhancements
- [ ] Automatic checkout after X hours of inactivity
- [ ] Geofencing alerts when leaving authorized zones
- [ ] Offline mode with sync when connection restored
- [ ] Project/task association per checkpoint
- [ ] Photo capture at checkpoint for verification
- [ ] NFC/QR code checkpoint for areas with poor GPS

## Support

For questions or issues with the checkpoint system:
- Check logs: `/var/log/nginx/error.log` or PHP error logs
- Database errors: Check `error_log` in PHP
- API testing: Use Postman or curl with sample requests above

---

**Version**: 1.0
**Date**: 2025-11-01
**Status**: In Development (API Complete, UI Pending)
