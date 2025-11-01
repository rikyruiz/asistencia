# 🧪 Guía de Pruebas - Sistema de Checkpoints

**Sistema:** Sistema de Asistencia - AlpeFresh
**URL Base:** https://asistencia.alpefresh.app
**Fecha:** 2025-11-01

---

## 🎯 URLs Correctas del Sistema

| Componente | URL |
|------------|-----|
| **Login** | https://asistencia.alpefresh.app/login.php |
| **Dashboard** | https://asistencia.alpefresh.app/dashboard.php |
| **Control de Checkpoints** | https://asistencia.alpefresh.app/asistencias_checkpoint.php |
| **API Endpoint** | https://asistencia.alpefresh.app/api/checkpoint.php |

---

## 🚀 Opción 1: Prueba Visual (Más Fácil)

### Paso a Paso:

1. **Accede al sistema:**
   ```
   https://asistencia.alpefresh.app/asistencias_checkpoint.php
   ```

2. **Inicia sesión** con tus credenciales

3. **Selecciona una ubicación** de la lista (click en la tarjeta)

4. **Haz Check-In:**
   - El navegador pedirá permiso para acceder a tu ubicación GPS
   - Permite el acceso
   - Click en el botón "Hacer Check-In"
   - Espera confirmación

5. **Verifica el checkpoint:**
   - Debe aparecer en la timeline del lado derecho
   - Debe mostrar el número de checkpoint (#1)
   - Debe aparecer como "ACTIVO" con punto verde pulsante

6. **Prueba Transferencia:** (opcional)
   - Selecciona otra ubicación
   - Click en "Transferir a Otra Ubicación"
   - Escribe un motivo (opcional)
   - Confirma

7. **Haz Check-Out:**
   - Click en "Hacer Check-Out"
   - Verifica que se muestre las horas trabajadas
   - El checkpoint debe marcarse como completado

---

## 💻 Opción 2: Prueba con JavaScript Console

### Instrucciones:

1. **Abre el navegador** en https://asistencia.alpefresh.app

2. **Inicia sesión** en el sistema

3. **Abre la consola del navegador:**
   - Chrome/Edge: `F12` o `Ctrl+Shift+J`
   - Firefox: `F12` o `Ctrl+Shift+K`
   - Safari: `Cmd+Option+C`

4. **Copia y pega este código:**

```javascript
// ===============================================
// TEST 1: Check-in en ubicación
// ===============================================
fetch('/api/checkpoint.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    action: 'checkin',
    location_id: 1,  // Cambia por un ID válido
    lat: 19.432608,
    lng: -99.133209,
    accuracy: 15.5
  })
})
.then(r => r.json())
.then(data => {
  console.log('✅ Check-in Result:', data);
  if (data.success) {
    console.log('Checkpoint #' + data.checkpoint_number + ' creado a las ' + data.time);
  }
});

// ===============================================
// TEST 2: Check-out (ejecutar DESPUÉS del check-in)
// ===============================================
setTimeout(() => {
  fetch('/api/checkpoint.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
      action: 'checkout',
      location_id: 1,
      lat: 19.432615,
      lng: -99.133198,
      accuracy: 12.3
    })
  })
  .then(r => r.json())
  .then(data => {
    console.log('✅ Check-out Result:', data);
    if (data.success) {
      console.log('Horas trabajadas: ' + data.hours_worked + 'h');
      console.log('Total del día: ' + data.total_daily_hours + 'h');
    }
  });
}, 5000); // Espera 5 segundos

// ===============================================
// TEST 3: Transferencia (requiere checkpoint activo)
// ===============================================
fetch('/api/checkpoint.php', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    action: 'transfer',
    location_id: 2,  // Cambia por otro ID válido
    lat: 19.425608,
    lng: -99.143209,
    accuracy: 18.2,
    reason: 'Reunión con cliente'
  })
})
.then(r => r.json())
.then(data => {
  console.log('✅ Transfer Result:', data);
});
```

5. **Revisa los resultados** en la consola

---

## 🔧 Opción 3: Prueba con cURL (Línea de Comandos)

### Pre-requisitos:
- Necesitas tu cookie de sesión (PHPSESSID)

### Obtener tu cookie:
1. Inicia sesión en https://asistencia.alpefresh.app
2. Abre las herramientas de desarrollador (F12)
3. Ve a la pestaña "Application" o "Storage"
4. Busca "Cookies" → "asistencia.alpefresh.app"
5. Copia el valor de `PHPSESSID`

### Comandos de prueba:

```bash
# Check-in
curl -X POST https://asistencia.alpefresh.app/api/checkpoint.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=TU_SESSION_ID_AQUI" \
  -d '{
    "action": "checkin",
    "location_id": 1,
    "lat": 19.432608,
    "lng": -99.133209,
    "accuracy": 15.5
  }'

# Check-out
curl -X POST https://asistencia.alpefresh.app/api/checkpoint.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=TU_SESSION_ID_AQUI" \
  -d '{
    "action": "checkout",
    "location_id": 1,
    "lat": 19.432615,
    "lng": -99.133198,
    "accuracy": 12.3
  }'

# Transfer
curl -X POST https://asistencia.alpefresh.app/api/checkpoint.php \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=TU_SESSION_ID_AQUI" \
  -d '{
    "action": "transfer",
    "location_id": 2,
    "lat": 19.425608,
    "lng": -99.143209,
    "accuracy": 18.2,
    "reason": "Cambio de ubicación"
  }'
```

---

## 📊 Opción 4: Pruebas de Base de Datos

### Ejecutar test suite automatizado:

```bash
cd /var/www/asistencia
php test_checkpoint_system.php
```

### Consultas SQL útiles:

```sql
-- Ver checkpoints de hoy
SELECT * FROM v_checkpoint_summary
WHERE fecha = CURDATE();

-- Ver transferencias de hoy
SELECT
    u.nombre,
    lt.transfer_time,
    ub_from.nombre as desde,
    ub_to.nombre as hacia,
    lt.transfer_reason
FROM location_transfers lt
JOIN usuarios u ON lt.usuario_id = u.id
LEFT JOIN ubicaciones ub_from ON lt.from_ubicacion_id = ub_from.id
JOIN ubicaciones ub_to ON lt.to_ubicacion_id = ub_to.id
WHERE DATE(lt.transfer_time) = CURDATE();

-- Calcular horas totales de un usuario
SELECT calculate_checkpoint_hours(1, CURDATE()) as horas_totales;

-- Ver todos los checkpoints de un usuario hoy
SELECT
    checkpoint_sequence as num,
    ub.nombre as ubicacion,
    TIME(hora_entrada) as entrada,
    TIME(hora_salida) as salida,
    horas_trabajadas as horas,
    is_active as activo
FROM registros_asistencia ra
LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
WHERE ra.usuario_id = 1
  AND ra.fecha = CURDATE()
  AND ra.session_type = 'checkpoint'
ORDER BY checkpoint_sequence;
```

---

## ✅ Checklist de Pruebas

### Funcionalidad Básica
- [ ] Login funciona
- [ ] Dashboard carga correctamente
- [ ] Página de checkpoints carga
- [ ] Ubicaciones se muestran correctamente
- [ ] GPS se activa al hacer click

### Check-in
- [ ] Permite seleccionar ubicación
- [ ] Solicita permiso GPS
- [ ] Valida distancia de la ubicación
- [ ] Crea checkpoint con número secuencial
- [ ] Muestra checkpoint en timeline
- [ ] Marca checkpoint como activo

### Check-out
- [ ] Solo funciona si hay checkpoint activo
- [ ] Calcula horas trabajadas correctamente
- [ ] Marca checkpoint como completado
- [ ] Muestra total de horas del día

### Transferencia
- [ ] Solo funciona si hay checkpoint activo
- [ ] Cierra checkpoint anterior automáticamente
- [ ] Crea nuevo checkpoint en nueva ubicación
- [ ] Registra la transferencia en la tabla
- [ ] Guarda el motivo de transferencia

### UI/UX
- [ ] Diseño responsive funciona en móvil
- [ ] Botones tienen estados correctos (habilitado/deshabilitado)
- [ ] Mensajes de error son claros
- [ ] Timeline se actualiza en tiempo real
- [ ] Stats muestran números correctos

### Base de Datos
- [ ] Trigger asigna sequence automáticamente
- [ ] View v_checkpoint_summary funciona
- [ ] Function calculate_checkpoint_hours devuelve valor correcto
- [ ] Procedure sp_transfer_location ejecuta sin errores
- [ ] location_transfers registra transferencias

---

## 🐛 Problemas Comunes

### "GPS muy impreciso"
**Causa:** Precisión GPS > 50 metros
**Solución:**
- Asegúrate de estar en exteriores
- Espera a que el GPS se estabilice
- Recarga la página

### "Fuera del rango permitido"
**Causa:** Estás fuera del radio de la ubicación
**Solución:**
- Verifica que estés en la ubicación correcta
- El admin puede ajustar el radio en la configuración de ubicaciones

### "Ya tienes un checkpoint activo"
**Causa:** Intentas hacer check-in sin cerrar el anterior
**Solución:**
- Haz check-out primero
- O usa "Transferir" para cambiar de ubicación

### "No autorizado"
**Causa:** Sesión expirada
**Solución:**
- Vuelve a iniciar sesión

---

## 📞 Soporte

Si encuentras problemas:

1. **Revisa los logs:**
   ```bash
   tail -f /var/log/nginx/error.log
   ```

2. **Verifica la base de datos:**
   ```bash
   php /var/www/asistencia/test_checkpoint_system.php
   ```

3. **Consulta la documentación:**
   - CHECKPOINT_SYSTEM.md
   - MIGRATION_SUCCESS.md
   - CHECKPOINT_IMPLEMENTATION_COMPLETE.md

---

**Última actualización:** 2025-11-01
**Sistema:** Asistencia AlpeFresh v2.0
**URL:** https://asistencia.alpefresh.app
