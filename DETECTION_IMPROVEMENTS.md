# Mejoras en Detección de Estado Check-In/Check-Out

## Fecha: 2025-10-27

## Problema Original
El sistema no mostraba claramente si un usuario ya había marcado entrada (check-in) y debía marcar salida (check-out), o viceversa. Aparecía siempre como si solo pudiera marcar entrada.

## Solución Implementada

### 1. **Mejora en la Lógica de Detección** (`asistencias.php` líneas 143-174)

#### Antes:
```php
$tieneEntrada = $estadoActual && !$estadoActual['salida'];
```

#### Después:
```php
// Query mejorado con estado calculado
SELECT id, entrada, salida, ubicacion_id,
       CASE
           WHEN salida IS NULL THEN 'checked_in'
           ELSE 'completed'
       END as estado
FROM asistencias
WHERE usuario_id = ? AND DATE(entrada) = CURDATE()

// Detección más robusta usando is_null()
$tieneEntrada = $estadoActual && is_null($estadoActual['salida']);
$estadoActualTexto = $tieneEntrada
    ? 'Ya marcaste entrada. Ahora puedes marcar salida.'
    : 'No has marcado entrada hoy.';
```

**Ventajas:**
- Usa `is_null()` en lugar de evaluación booleana simple
- Calcula el estado directamente en SQL para mayor confiabilidad
- Genera texto descriptivo del estado actual

---

### 2. **Indicador Visual del Estado** (Desktop y Mobile)

Se agregó un banner de estado claramente visible **antes** de los botones de marcaje:

```php
<!-- Estado de Asistencia del Día -->
<div style="padding: 1rem; margin: 1rem 0; border-radius: 8px;
     text-align: center; font-weight: 600;
     background: [verde si tiene entrada, amarillo si no];
     border: 2px solid [...];">
    <i class="fas fa-[check-circle o clock]"></i>
    [Texto del estado]
    <br><small>Entrada: [hora]</small>
</div>
```

**Colores:**
- 🟢 **Verde** (`#dcfce7`) = Ya marcó entrada, debe marcar salida
- 🟡 **Amarillo** (`#fef3c7`) = No ha marcado entrada hoy

---

### 3. **Panel de Debug** (líneas 927-960)

Se agregó un panel de diagnóstico activable con `?debug=1` en la URL:

```
https://asistencia.alpefresh.app/asistencias.php?debug=1
```

**Muestra:**
- Usuario ID
- Si hay registro de hoy
- Hora de entrada y salida
- Estado calculado (checked_in / completed)
- Qué botón se debe mostrar
- Valor de `$tieneEntrada`

**Ejemplo de salida:**
```
🐛 DEBUG MODE                    Usuario ID: 12
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Registro encontrado:     ✓ SÍ
ID Registro:             7
Hora Entrada:            2025-10-27 13:00:29
Hora Salida:             NULL (sin salida)
Estado Calculado:        checked_in
Tiene Entrada Activa:    ✓ SÍ - Debe marcar SALIDA
Botón que se muestra:    MARCAR SALIDA
```

---

### 4. **Página de Diagnóstico** (`test-estado-asistencia.php`)

Nueva herramienta de diagnóstico completa para troubleshooting:

**Acceso:**
```
https://asistencia.alpefresh.app/test-estado-asistencia.php
```

**Funcionalidades:**
- Muestra información del usuario actual
- Estado detallado de la detección
- Estadísticas de registros de hoy
- Tabla con TODOS los registros del día
- Botones para navegar a diferentes vistas

**Ideal para:**
- Verificar por qué no detecta el estado
- Ver si hay múltiples registros duplicados
- Diagnosticar problemas de timezone
- Validar que la lógica funciona correctamente

---

## Cómo Usar las Mejoras

### Uso Normal:
1. Ir a `https://asistencia.alpefresh.app/asistencias.php`
2. Ver el indicador de estado (verde o amarillo)
3. El botón correcto se mostrará automáticamente:
   - **MARCAR ENTRADA** (si no has marcado hoy)
   - **MARCAR SALIDA** (si ya marcaste entrada)

### Para Diagnosticar Problemas:
1. **Modo Debug:**
   ```
   https://asistencia.alpefresh.app/asistencias.php?debug=1
   ```

2. **Test Completo:**
   ```
   https://asistencia.alpefresh.app/test-estado-asistencia.php
   ```

---

## Verificación de Funcionamiento

### Caso 1: Usuario SIN entrada de hoy
```sql
SELECT * FROM asistencias
WHERE usuario_id = 12 AND DATE(entrada) = '2025-10-27'
-- No hay registros
```

**Resultado esperado:**
- Indicador: 🟡 "No has marcado entrada hoy"
- Botón: "MARCAR ENTRADA"

### Caso 2: Usuario CON entrada, SIN salida
```sql
SELECT * FROM asistencias
WHERE usuario_id = 12 AND DATE(entrada) = '2025-10-27'
-- id=7, entrada='2025-10-27 13:00:29', salida=NULL
```

**Resultado esperado:**
- Indicador: 🟢 "Ya marcaste entrada. Ahora puedes marcar salida."
- Hora: "Entrada: 01:00 PM"
- Botón: "MARCAR SALIDA"

### Caso 3: Usuario CON entrada y salida completas
```sql
SELECT * FROM asistencias
WHERE usuario_id = 12 AND DATE(entrada) = '2025-10-27'
-- id=7, entrada='2025-10-27 08:00:00', salida='2025-10-27 17:00:00'
```

**Resultado esperado:**
- Indicador: 🟡 "No has marcado entrada hoy" (porque ya completó el ciclo)
- Botón: "MARCAR ENTRADA" (para nueva entrada si es necesario)

---

## Logs y Debugging

Los logs se escriben en el error log de PHP cuando `?debug=1` está activo:

```bash
# Ver logs en tiempo real
tail -f /var/log/apache2/error.log | grep "Estado actual"
```

**Ejemplo de log:**
```
Estado actual - Usuario ID: 12
Registro encontrado: SI
ID: 7
Entrada: 2025-10-27 13:00:29
Salida: NULL
Estado: checked_in
Tiene entrada sin salida: SI
```

---

## Posibles Problemas y Soluciones

### Problema: Siempre muestra "MARCAR ENTRADA"

**Diagnóstico:**
1. Ir a `test-estado-asistencia.php`
2. Verificar si hay registro de hoy
3. Verificar que `salida` sea NULL

**Posibles causas:**
- Usuario diferente al que tiene el registro
- Timezone incorrecto (CURDATE() no coincide)
- Registro con salida ya marcada

**Solución:**
```sql
-- Verificar timezone de MySQL
SELECT NOW(), CURDATE();

-- Ver registros del usuario
SELECT * FROM asistencias
WHERE usuario_id = [TU_USER_ID]
ORDER BY entrada DESC LIMIT 5;
```

### Problema: No muestra el indicador de estado

**Causa:** Cache del navegador

**Solución:**
1. Ctrl+F5 para forzar recarga
2. Limpiar cache del navegador
3. Verificar que el PHP no tiene errores con `?debug=1`

---

## Testing

### Test Manual:
1. Usuario marca entrada → debe ver indicador verde + botón "MARCAR SALIDA"
2. Usuario marca salida → indicador cambia a amarillo + botón "MARCAR ENTRADA"
3. Usuario intenta marcar entrada dos veces → muestra warning

### Test con Debug:
```bash
# 1. Login como usuario de prueba
# 2. Abrir en navegador:
https://asistencia.alpefresh.app/test-estado-asistencia.php

# 3. Verificar valores:
# - Registro encontrado: debe cambiar al marcar entrada
# - Tiene entrada activa: debe cambiar al marcar salida
# - Botón que se muestra: debe alternarse correctamente
```

---

## Archivos Modificados

1. **`asistencias.php`**
   - Líneas 143-174: Lógica de detección mejorada
   - Líneas 927-960: Panel de debug
   - Líneas 935-942: Indicador visual (desktop)
   - Líneas 1127-1134: Indicador visual (mobile)

2. **`test-estado-asistencia.php`** (NUEVO)
   - Herramienta completa de diagnóstico
   - Muestra toda la información relevante
   - Permite verificar el estado actual

3. **`DETECTION_IMPROVEMENTS.md`** (NUEVO)
   - Este documento de referencia

---

## Mantenimiento Futuro

### Si necesitas modificar la lógica de detección:

1. **Ubicación del código:** `asistencias.php` líneas 143-174
2. **Query principal:**
   ```php
   SELECT id, entrada, salida, ubicacion_id,
          CASE WHEN salida IS NULL THEN 'checked_in' ELSE 'completed' END as estado
   FROM asistencias
   WHERE usuario_id = ? AND DATE(entrada) = CURDATE()
   ```

3. **Variable clave:** `$tieneEntrada` (determina qué botón mostrar)

4. **Siempre testear con:**
   - `?debug=1` en asistencias.php
   - `test-estado-asistencia.php`
   - Verificar con diferentes usuarios

---

## Soporte

Si el problema persiste después de estas mejoras:

1. Activar modo debug: `?debug=1`
2. Capturar screenshot del panel de debug
3. Ejecutar `test-estado-asistencia.php`
4. Revisar logs de Apache/PHP
5. Verificar datos en la base de datos directamente:
   ```sql
   SELECT * FROM asistencias
   WHERE usuario_id = [ID] AND DATE(entrada) = CURDATE();
   ```

---

**Fecha de implementación:** 2025-10-27
**Versión:** 1.0
**Estado:** ✅ Implementado y probado
