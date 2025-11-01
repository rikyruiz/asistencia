# 🎯 Instrucciones de Prueba en Vivo - Sistema de Checkpoints

**URL del Sistema:** https://asistencia.alpefresh.app
**Fecha:** 2025-11-01
**Estado:** ✅ Listo para probar

---

## 🔐 Credenciales de Prueba

### Usuario de Prueba (con datos simulados):
```
Email: acastaneda@alpefresh.com.mx
Rol: Empleado
Tiene: 3 checkpoints creados hoy (datos de simulación)
```

### Usuario Admin:
```
Email: admin@alpefresh.app
Rol: Admin
```

*(Usa las contraseñas que ya tienes configuradas en el sistema)*

---

## 📝 Guía de Prueba Paso a Paso

### **PASO 1: Acceder al Sistema** ✅

1. Abre tu navegador (Chrome, Firefox, Safari, Edge)
2. Ve a: **https://asistencia.alpefresh.app/login.php**
3. Inicia sesión con cualquier usuario activo

---

### **PASO 2: Ir a Control de Checkpoints** ✅

**Opción A - URL Directa:**
```
https://asistencia.alpefresh.app/asistencias_checkpoint.php
```

**Opción B - Navegación:**
- Busca en el menú: "Asistencias" o "Control de Asistencia"
- Click en el enlace al nuevo sistema de checkpoints

---

### **PASO 3: Ver Datos de Prueba** 👀

Si iniciaste sesión como **Adrian (acastaneda@alpefresh.com.mx)**, deberías ver:

**En las Estadísticas (arriba):**
- ✅ **3 Checkpoints Hoy**
- ✅ **8h 30m Horas Trabajadas**
- ✅ **Estado: Libre** (todos cerrados)

**En la Timeline (lado derecho):**
```
• Checkpoint #1 - CAT HEB
  09:00 - 11:30 (2h 30m) ✅

• Checkpoint #2 - Oficina Remota
  12:00 - 14:00 (2h 0m) ✅

• Checkpoint #3 - Alpe Fresh Guadalajara
  14:00 - 18:00 (4h 0m) ✅
```

**Transferencia Registrada:**
- 🔄 14:00: Oficina Remota → Alpe Fresh Guadalajara
- 💬 Motivo: "Reunión con cliente"

---

### **PASO 4: Crear un Nuevo Checkpoint** 🆕

Ahora vamos a crear un checkpoint REAL en vivo:

1. **Selecciona una ubicación** de la lista (click en cualquier tarjeta)
   - La tarjeta seleccionada se marcará con borde dorado

2. **Click en "Hacer Check-In"**
   - El navegador pedirá permiso para acceder a tu ubicación GPS
   - **Importante:** Click en "Permitir"

3. **Espera la validación GPS**
   - Verás un mensaje: "Obteniendo ubicación GPS..."
   - El estado cambiará según la precisión:
     - 🟢 Verde: GPS excelente (< 20m)
     - 🟡 Amarillo: GPS bueno (20-50m)
     - 🔴 Rojo: GPS pobre (> 50m)

4. **Confirmación**
   - Si todo está bien: ✅ "Check-in registrado exitosamente"
   - Aparecerá un nuevo checkpoint en la timeline
   - Las estadísticas se actualizarán

**Nota sobre GPS:**
- Si estás en una oficina/interior, el GPS puede ser impreciso
- Para pruebas, el sistema puede rechazar GPS con precisión > 50m
- Si tienes problemas, intenta desde una ventana o exterior

---

### **PASO 5: Probar Check-Out** 🚪

1. Con un checkpoint activo, verás el botón **"Hacer Check-Out"**
2. Click en el botón
3. Confirma la acción
4. Verás:
   - Horas trabajadas en ese checkpoint
   - Total de horas del día
   - El checkpoint se marca como completado en la timeline

---

### **PASO 6: Probar Transferencia** 🔄

Para probar la transferencia entre ubicaciones:

1. **Crea un checkpoint** (check-in en ubicación A)
2. **Selecciona otra ubicación diferente** (ubicación B)
3. **Click en "Transferir a Otra Ubicación"**
4. En el modal que aparece:
   - Escribe un motivo (opcional): Ej. "Visita a cliente", "Cambio de proyecto"
   - Click en "Confirmar Transferencia"

**Qué Sucede:**
- ✅ Checkpoint anterior se cierra automáticamente
- ✅ Nuevo checkpoint se crea en la ubicación B
- ✅ Transferencia se registra en la tabla `location_transfers`
- ✅ Todo en una sola acción

---

## 🧪 Escenarios de Prueba Sugeridos

### **Escenario 1: Día Completo** ⭐ Recomendado

Simula un día de trabajo completo:

```
1. Check-in en Oficina A (09:00)
2. Espera 10 segundos
3. Check-out de Oficina A
4. Check-in en Oficina B (cliente)
5. Espera 10 segundos
6. Transfer a Oficina C (con motivo: "Reunión")
7. Check-out de Oficina C
```

**Verifica:**
- ✅ Todos los checkpoints aparecen numerados (1, 2, 3)
- ✅ Horas se calculan correctamente
- ✅ Transferencia aparece en la lista
- ✅ Total de horas suma correctamente

---

### **Escenario 2: GPS Fuera de Rango** 🚫

Prueba la validación de distancia:

```
1. Selecciona una ubicación
2. Intenta check-in estando LEJOS de esa ubicación
```

**Resultado esperado:**
- ❌ Error: "Fuera del rango permitido"
- Mensaje muestra: "Distancia: XXm, Radio permitido: YYm"

---

### **Escenario 3: Checkpoint Activo** ⚠️

Prueba la protección de checkpoints duplicados:

```
1. Haz check-in en una ubicación
2. SIN hacer check-out, intenta otro check-in
```

**Resultado esperado:**
- ❌ Error: "Ya tienes un checkpoint activo. Primero debes hacer checkout."

---

### **Escenario 4: Múltiples Checkpoints** 📊

Crea varios checkpoints para ver la timeline:

```
1. Check-in/out en ubicación 1
2. Check-in/out en ubicación 2
3. Check-in/out en ubicación 3
```

**Verifica:**
- ✅ Timeline muestra todos en orden cronológico
- ✅ Cada uno tiene su número de secuencia
- ✅ Stats muestran total correcto

---

## 🎨 Elementos de UI a Verificar

### **Tarjetas de Ubicación:**
- [ ] Muestran nombre, dirección, radio y tipo
- [ ] Cambian a borde dorado al seleccionar
- [ ] Hover hace efecto de elevación

### **Botones de Acción:**
- [ ] Cambian según estado (Check-in vs Check-out)
- [ ] Botón Transfer solo aparece con checkpoint activo
- [ ] Animación de "Procesando..." al hacer click

### **GPS Status:**
- [ ] Muestra precisión en metros
- [ ] Color cambia según calidad (verde/amarillo/rojo)
- [ ] Mensajes claros de error

### **Timeline:**
- [ ] Checkpoints aparecen en orden
- [ ] Punto pulsante para checkpoint activo
- [ ] Muestra entrada, salida y horas
- [ ] Colores diferenciados (activo vs completado)

### **Modal de Transferencia:**
- [ ] Se abre correctamente
- [ ] Campo de motivo funciona
- [ ] Se cierra al hacer click fuera
- [ ] Botones funcionan

---

## 📱 Pruebas en Móvil

### **Responsive Design:**

1. Abre en tu teléfono: https://asistencia.alpefresh.app/asistencias_checkpoint.php
2. Verifica:
   - [ ] Layout se ajusta a pantalla pequeña
   - [ ] Botones son fáciles de tocar
   - [ ] GPS funciona en móvil
   - [ ] Tarjetas se apilan verticalmente
   - [ ] Todo es legible

---

## 🔍 Verificación en Base de Datos

Después de crear checkpoints, verifica en la base de datos:

```sql
-- Ver tus checkpoints de hoy
SELECT
    checkpoint_sequence,
    ub.nombre as ubicacion,
    TIME(hora_entrada) as entrada,
    TIME(hora_salida) as salida,
    horas_trabajadas,
    is_active
FROM registros_asistencia ra
LEFT JOIN ubicaciones ub ON ra.ubicacion_id = ub.id
WHERE usuario_id = YOUR_USER_ID
  AND fecha = CURDATE()
  AND session_type = 'checkpoint'
ORDER BY checkpoint_sequence;

-- Ver transferencias
SELECT
    TIME(transfer_time) as hora,
    ub_from.nombre as desde,
    ub_to.nombre as hacia,
    transfer_reason
FROM location_transfers lt
LEFT JOIN ubicaciones ub_from ON lt.from_ubicacion_id = ub_from.id
JOIN ubicaciones ub_to ON lt.to_ubicacion_id = ub_to.id
WHERE usuario_id = YOUR_USER_ID
  AND DATE(transfer_time) = CURDATE();
```

---

## 🐛 Troubleshooting

### **"Página no encontrada"**
- Verifica la URL: https://asistencia.alpefresh.app/asistencias_checkpoint.php
- Asegúrate de haber iniciado sesión

### **"GPS no disponible"**
- Verifica que el navegador tenga permiso de ubicación
- En Chrome: chrome://settings/content/location
- Prueba desde otro navegador

### **"Fuera del rango permitido"**
- Normal si estás lejos de la ubicación real
- Usa datos de prueba simulados o ajusta el radio de la ubicación

### **Botones no responden**
- Abre la consola del navegador (F12)
- Busca errores en JavaScript
- Verifica que `/api/checkpoint.php` sea accesible

---

## ✅ Checklist Final

Marca lo que hayas probado:

**Funcionalidad:**
- [ ] Login funciona
- [ ] Página carga correctamente
- [ ] Ubicaciones se muestran
- [ ] GPS se solicita correctamente
- [ ] Check-in funciona
- [ ] Check-out funciona
- [ ] Transferencia funciona
- [ ] Timeline se actualiza
- [ ] Stats son correctas

**UI/UX:**
- [ ] Diseño se ve bien
- [ ] Colores y estilos correctos
- [ ] Responsive en móvil
- [ ] Animaciones funcionan
- [ ] Mensajes claros

**Datos:**
- [ ] Checkpoints se guardan en DB
- [ ] Secuencia es correcta
- [ ] Horas se calculan bien
- [ ] Transferencias se registran

---

## 📞 Soporte

Si encuentras algún problema, revisa:

1. **Logs del navegador:** F12 → Console
2. **Logs del servidor:** `/var/log/nginx/error.log`
3. **Documentación:**
   - TESTING_GUIDE.md
   - CHECKPOINT_SYSTEM.md

---

**¡Disfruta probando el sistema de checkpoints!** 🚀

**URL:** https://asistencia.alpefresh.app/asistencias_checkpoint.php
