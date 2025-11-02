# Guía de Estilos - Sistema de Control de Asistencia
## Documento de Referencia de Diseño Visual

---

## 🎨 COLORES CORPORATIVOS

### Colores Principales

#### Navy (Azul Marino Corporativo)
```css
navy: #003366
```
- **Uso:** Color primario corporativo
- **Aplicaciones:** Encabezados, botones principales, navegación
- **Variantes:**
  - `primary-900`: #001a33 (Más oscuro)
  - `primary-800`: #003366 (Navy base)
  - `primary-700`: #003d99
  - `primary-600`: #0052cc
  - `primary-500`: #0066ff
  - `primary-400`: #3385ff
  - `primary-300`: #66a3ff
  - `primary-200`: #99c2ff
  - `primary-100`: #cce0ff
  - `primary-50`: #e6f0ff (Más claro)

#### Gold (Dorado Corporativo)
```css
gold: #fdb714
```
- **Uso:** Color de acento corporativo
- **Aplicaciones:** Botones de acción, highlights, badges importantes
- **Variantes:**
  - `accent-900`: #78350f (Más oscuro)
  - `accent-800`: #92400e
  - `accent-700`: #b45309
  - `accent-600`: #d97706
  - `accent-500`: #f59e0b
  - `accent-400`: #fdb714 (Gold base)
  - `accent-300`: #fcd34d
  - `accent-200`: #fde68a
  - `accent-100`: #fef3c7
  - `accent-50`: #fffbeb (Más claro)

### Colores de Estado

#### Success (Verde)
- `green-900`: #14532d
- `green-800`: #166534
- `green-700`: #15803d
- `green-600`: #16a34a
- `green-500`: #22c55e
- `green-200`: #bbf7d0
- `green-100`: #dcfce7
- `green-50`: #f0fdf4

#### Warning (Amarillo)
- `yellow-900`: #713f12
- `yellow-800`: #854d0e
- `yellow-700`: #a16207
- `yellow-600`: #ca8a04
- `yellow-500`: #eab308
- `yellow-400`: #facc15
- `yellow-200`: #fef08a
- `yellow-100`: #fef9c3
- `yellow-50`: #fefce8

#### Danger (Rojo)
- `red-900`: #7f1d1d
- `red-800`: #991b1b
- `red-700`: #b91c1c
- `red-600`: #dc2626
- `red-500`: #ef4444
- `red-300`: #fca5a5
- `red-200`: #fecaca
- `red-100`: #fee2e2
- `red-50`: #fef2f2

#### Info (Azul)
- `blue-900`: #1e3a8a
- `blue-800`: #1e40af
- `blue-700`: #1d4ed8
- `blue-600`: #2563eb
- `blue-500`: #3b82f6
- `blue-200`: #bfdbfe
- `blue-100`: #dbeafe
- `blue-50`: #eff6ff

#### Purple (Morado)
- `purple-900`: #581c87
- `purple-800`: #6b21a8
- `purple-700`: #7e22ce
- `purple-600`: #9333ea
- `purple-500`: #a855f7
- `purple-400`: #c084fc
- `purple-200`: #e9d5ff
- `purple-100`: #f3e8ff

#### Orange (Naranja)
- `orange-900`: #7c2d12
- `orange-800`: #9a3412
- `orange-700`: #c2410c
- `orange-600`: #ea580c
- `orange-500`: #f97316
- `orange-100`: #ffedd5
- `orange-50`: #fff7ed

### Colores Neutros (Grises)

```css
black: #000000
white: #ffffff
gray-900: #111827
gray-800: #1f2937
gray-700: #374151
gray-600: #4b5563
gray-500: #6b7280
gray-400: #9ca3af
gray-300: #d1d5db
gray-200: #e5e7eb
gray-100: #f3f4f6
gray-50: #f9fafb
```

---

## 📝 TIPOGRAFÍA

### Familia de Fuentes

```css
font-family: 'Inter', system-ui, -apple-system, sans-serif;
```

**Importación:**
```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap');
```

### Escalas de Tamaño

| Clase | Tamaño | Line Height | Uso |
|-------|--------|-------------|-----|
| `text-xs` | 0.75rem (12px) | 1rem | Texto muy pequeño, labels |
| `text-sm` | 0.875rem (14px) | 1.25rem | Texto secundario, ayuda |
| `text-base` | 1rem (16px) | 1.5rem | Texto normal del cuerpo |
| `text-lg` | 1.125rem (18px) | 1.75rem | Texto destacado |
| `text-xl` | 1.25rem (20px) | 1.75rem | Subtítulos pequeños |
| `text-2xl` | 1.5rem (24px) | 2rem | Subtítulos medianos |
| `text-3xl` | 1.875rem (30px) | 2.25rem | Títulos de sección |
| `text-4xl` | 2.25rem (36px) | 2.5rem | Títulos principales |
| `text-5xl` | 3rem (48px) | 1 | Héroes, landing |
| `text-6xl` | 3.75rem (60px) | 1 | Display grande |
| `text-7xl` | 4.5rem (72px) | 1 | Display muy grande |

### Pesos de Fuente

| Clase | Peso | Uso |
|-------|------|-----|
| `font-light` | 300 | Texto ligero, decorativo |
| `font-normal` | 400 | Texto normal |
| `font-medium` | 500 | Énfasis ligero |
| `font-semibold` | 600 | Subtítulos, énfasis |
| `font-bold` | 700 | Títulos, llamados de atención |
| `font-black` | 900 | Máximo énfasis |

### Line Heights

```css
leading-tight: 1.25
leading-relaxed: 1.625
leading-5: 1.25rem
```

### Letter Spacing

```css
tracking-tight: -0.025em
tracking-wider: 0.05em
```

---

## 🧩 COMPONENTES PREDEFINIDOS

### Cards (Tarjetas)

#### Card Base
```css
.card {
  background-color: white;
  border-radius: 0.75rem; /* rounded-xl */
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}
```

**HTML:**
```html
<div class="card p-6">
  <!-- Contenido -->
</div>
```

#### Card Hover
```css
.card-hover {
  transition: box-shadow 0.3s;
}

.card-hover:hover {
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
```

**HTML:**
```html
<div class="card card-hover p-6">
  <!-- Contenido con efecto hover -->
</div>
```

#### Stat Card (Card de Estadísticas)
```css
.stat-card {
  background-color: white;
  border-radius: 0.75rem;
  padding: 1.5rem;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}
```

**HTML:**
```html
<div class="stat-card">
  <h3 class="text-gray-500 text-sm font-medium">Total Empleados</h3>
  <p class="text-3xl font-bold text-navy mt-2">127</p>
</div>
```

### Botones

#### Botón Primario (Navy)
```css
.btn-primary {
  background-color: #003366;
  color: white;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}

.btn-primary:hover {
  background-color: #003d99;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
```

**HTML:**
```html
<button class="btn btn-primary">
  Registrar Entrada
</button>
```

#### Botón Secundario
```css
.btn-secondary {
  background-color: white;
  color: #003366;
  border: 2px solid #003366;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 500;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}
```

**HTML:**
```html
<button class="btn btn-secondary">
  Cancelar
</button>
```

#### Botón Accent (Gold)
```css
.btn-accent {
  background-color: #fdb714;
  color: #003366;
  padding: 0.75rem 1.5rem;
  border-radius: 0.5rem;
  font-weight: 600;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}

.btn-accent:hover {
  background-color: #f59e0b;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
}
```

**HTML:**
```html
<button class="btn btn-accent">
  Exportar Reporte
</button>
```

### Campos de Formulario

#### Input Field
```css
.input-field {
  width: 100%;
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.input-field:focus {
  border-color: transparent;
  ring: 2px solid #003366;
}
```

**HTML:**
```html
<input type="text"
       class="input-field"
       placeholder="Email">
```

### Badges (Etiquetas)

#### Badge Base
```css
.badge {
  display: inline-flex;
  align-items: center;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}
```

#### Badge Success (Verde)
```css
.badge-success {
  background-color: #dcfce7;
  color: #166534;
}
```

**HTML:**
```html
<span class="badge badge-success">Activo</span>
```

#### Badge Warning (Amarillo)
```css
.badge-warning {
  background-color: #fef9c3;
  color: #854d0e;
}
```

**HTML:**
```html
<span class="badge badge-warning">Pendiente</span>
```

#### Badge Danger (Rojo)
```css
.badge-danger {
  background-color: #fee2e2;
  color: #991b1b;
}
```

**HTML:**
```html
<span class="badge badge-danger">Inactivo</span>
```

---

## 🎭 EFECTOS Y ANIMACIONES

### Sombras (Shadows)

```css
shadow-sm: 0 1px 2px 0 rgba(0,0,0,0.05)
shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1)
shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1)
shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1)
shadow-2xl: 0 25px 50px -12px rgba(0,0,0,0.25)
```

### Transiciones

```css
transition: 0.15s
transition-all: all 0.15s
transition-colors: color, background-color, border-color 0.15s
transition-shadow: box-shadow 0.15s
transition-transform: transform 0.15s
```

**Duraciones:**
```css
duration-200: 0.2s
duration-300: 0.3s
duration-500: 0.5s
```

### Transformaciones

#### Hover Effects
```css
hover:scale-105 /* Escala 1.05 al hover */
hover:-translate-y-2 /* Mueve arriba 0.5rem */
hover:shadow-xl /* Sombra grande al hover */
```

**Ejemplo combinado:**
```html
<div class="card transition-all duration-300 hover:scale-105 hover:shadow-xl">
  <!-- Contenido -->
</div>
```

### Blur Effects

```css
blur: blur(8px)
blur-xl: blur(24px)
blur-2xl: blur(40px)
blur-3xl: blur(64px)
backdrop-blur-sm: blur(4px)
backdrop-blur-md: blur(12px)
```

---

## 📐 ESPACIADO Y LAYOUT

### Sistema de Espaciado

| Clase | Valor | Pixels |
|-------|-------|--------|
| `0.5` | 0.125rem | 2px |
| `1` | 0.25rem | 4px |
| `2` | 0.5rem | 8px |
| `3` | 0.75rem | 12px |
| `4` | 1rem | 16px |
| `6` | 1.5rem | 24px |
| `8` | 2rem | 32px |
| `10` | 2.5rem | 40px |
| `12` | 3rem | 48px |
| `16` | 4rem | 64px |
| `20` | 5rem | 80px |

### Padding
```css
p-{size}: padding all sides
px-{size}: padding horizontal
py-{size}: padding vertical
pt-{size}: padding top
pr-{size}: padding right
pb-{size}: padding bottom
pl-{size}: padding left
```

### Margin
```css
m-{size}: margin all sides
mx-{size}: margin horizontal
my-{size}: margin vertical
mt-{size}: margin top
mr-{size}: margin right
mb-{size}: margin bottom
ml-{size}: margin left
mx-auto: centrar horizontalmente
```

### Gap (Para Grid/Flex)
```css
gap-1: 0.25rem
gap-2: 0.5rem
gap-3: 0.75rem
gap-4: 1rem
gap-6: 1.5rem
gap-8: 2rem
gap-12: 3rem
```

---

## 🔄 BORDES Y REDONDEADOS

### Border Radius

```css
rounded: 0.25rem (4px)
rounded-md: 0.375rem (6px)
rounded-lg: 0.5rem (8px)
rounded-xl: 0.75rem (12px)
rounded-2xl: 1rem (16px)
rounded-3xl: 1.5rem (24px)
rounded-full: 9999px (círculo perfecto)
```

### Border Width

```css
border: 1px
border-0: 0px
border-2: 2px
border-t: top 1px
border-r: right 1px
border-b: bottom 1px
border-l: left 1px
border-l-4: left 4px
```

### Border Colors

```css
border-navy: #003366
border-gold: #fdb714
border-gray-200: #e5e7eb
border-gray-300: #d1d5db
border-white: #ffffff
```

---

## 🌈 GRADIENTES

### Gradientes Predefinidos

#### Navy Gradient
```css
bg-gradient-to-r from-navy via-navy to-blue-900
```

#### Gold Gradient
```css
bg-gradient-to-r from-gold to-yellow-600
```

#### Gradient Backgrounds (Sutiles)
```css
bg-gradient-to-br from-blue-50 via-gray-50 to-gray-100
bg-gradient-to-br from-green-50 via-yellow-50 to-orange-50
bg-gradient-to-r from-navy/10 to-blue-800/10
```

**Ejemplo de uso:**
```html
<div class="bg-gradient-to-br from-navy via-blue-900 to-purple-900 text-white p-12">
  <h1 class="text-4xl font-bold">Panel de Control</h1>
</div>
```

---

## 📱 RESPONSIVE DESIGN

### Breakpoints

| Breakpoint | Min Width | Devices |
|------------|-----------|---------|
| `sm:` | 640px | Tablets pequeñas |
| `md:` | 768px | Tablets |
| `lg:` | 1024px | Laptops |
| `xl:` | 1280px | Desktops |
| `2xl:` | 1536px | Desktops grandes |

### Ejemplos de Uso Responsive

```html
<!-- Grid responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
  <!-- Cards -->
</div>

<!-- Padding responsive -->
<div class="p-4 sm:p-6 lg:p-12">
  <!-- Contenido -->
</div>

<!-- Texto responsive -->
<h1 class="text-2xl sm:text-3xl lg:text-5xl">
  Título Responsive
</h1>

<!-- Ocultar/Mostrar elementos -->
<div class="hidden md:block">
  <!-- Visible solo en tablets y superiores -->
</div>

<div class="block md:hidden">
  <!-- Visible solo en móviles -->
</div>
```

---

## 🎯 PATRONES DE DISEÑO COMUNES

### Hero Section (Sección Principal)

```html
<section class="bg-gradient-to-br from-navy via-blue-900 to-purple-900 text-white py-16 lg:py-20">
  <div class="max-w-7xl mx-auto px-4">
    <h1 class="text-4xl lg:text-6xl font-bold mb-6">
      Sistema de Asistencia
    </h1>
    <p class="text-xl text-white/90 mb-8">
      Control preciso con geolocalización
    </p>
    <button class="btn btn-accent">
      Comenzar Ahora
    </button>
  </div>
</section>
```

### Dashboard Grid

```html
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
  <!-- Stat Cards -->
  <div class="stat-card">
    <div class="flex items-center justify-between">
      <div>
        <p class="text-gray-500 text-sm">Empleados Activos</p>
        <p class="text-3xl font-bold text-navy mt-2">48</p>
      </div>
      <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
        <!-- Icon -->
      </div>
    </div>
  </div>
</div>
```

### Form Layout

```html
<form class="space-y-6">
  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      Email
    </label>
    <input type="email"
           class="input-field"
           placeholder="usuario@ejemplo.com">
  </div>

  <div>
    <label class="block text-sm font-medium text-gray-700 mb-2">
      PIN (6 dígitos)
    </label>
    <input type="password"
           maxlength="6"
           class="input-field"
           placeholder="••••••">
  </div>

  <button type="submit" class="btn btn-primary w-full">
    Iniciar Sesión
  </button>
</form>
```

### Table Layout

```html
<div class="overflow-x-auto">
  <table class="w-full">
    <thead class="bg-gray-50 border-b border-gray-200">
      <tr>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Nombre
        </th>
        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
          Estado
        </th>
      </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
      <tr class="hover:bg-gray-50 transition-colors">
        <td class="px-6 py-4 whitespace-nowrap">
          Juan Pérez
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
          <span class="badge badge-success">Activo</span>
        </td>
      </tr>
    </tbody>
  </table>
</div>
```

### Modal/Dialog

```html
<!-- Overlay -->
<div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm z-40"></div>

<!-- Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6">
    <h2 class="text-2xl font-bold text-navy mb-4">
      Confirmar Acción
    </h2>
    <p class="text-gray-600 mb-6">
      ¿Estás seguro de que deseas continuar?
    </p>
    <div class="flex gap-3 justify-end">
      <button class="btn btn-secondary">Cancelar</button>
      <button class="btn btn-primary">Confirmar</button>
    </div>
  </div>
</div>
```

### Toast Notification

```html
<!-- Success -->
<div class="fixed top-4 right-4 bg-green-100 border-l-4 border-green-500 rounded-lg shadow-lg p-4 max-w-sm">
  <div class="flex items-start">
    <div class="flex-shrink-0">
      <!-- Icon Success -->
    </div>
    <div class="ml-3">
      <p class="text-sm font-medium text-green-800">
        Entrada registrada correctamente
      </p>
    </div>
  </div>
</div>

<!-- Error -->
<div class="fixed top-4 right-4 bg-red-100 border-l-4 border-red-500 rounded-lg shadow-lg p-4 max-w-sm">
  <div class="flex items-start">
    <div class="flex-shrink-0">
      <!-- Icon Error -->
    </div>
    <div class="ml-3">
      <p class="text-sm font-medium text-red-800">
        Error al procesar la solicitud
      </p>
    </div>
  </div>
</div>
```

---

## 🔍 UTILIDADES ESPECIALES

### Opacidad

```css
opacity-0: 0
opacity-10: 0.1
opacity-20: 0.2
opacity-50: 0.5
opacity-80: 0.8
opacity-90: 0.9
opacity-100: 1
```

### Z-Index

```css
z-10: 10
z-40: 40
z-50: 50
```

### Display

```css
hidden: display: none
block: display: block
inline: display: inline
inline-block: display: inline-block
flex: display: flex
inline-flex: display: inline-flex
grid: display: grid
```

### Flexbox

```css
/* Direction */
flex-row: horizontal
flex-col: vertical

/* Justify Content */
justify-start: flex-start
justify-center: center
justify-end: flex-end
justify-between: space-between

/* Align Items */
items-start: flex-start
items-center: center
items-end: flex-end
items-baseline: baseline
```

### Grid

```css
grid-cols-1: 1 column
grid-cols-2: 2 columns
grid-cols-3: 3 columns
grid-cols-4: 4 columns
grid-cols-5: 5 columns
grid-cols-6: 6 columns
```

---

## 📋 GUÍA RÁPIDA DE CLASES TAILWIND

### Combinaciones Comunes

#### Botón con Icono
```html
<button class="btn btn-primary inline-flex items-center gap-2">
  <svg class="w-5 h-5"><!-- icon --></svg>
  <span>Registrar</span>
</button>
```

#### Card con Gradiente de Fondo
```html
<div class="bg-gradient-to-br from-blue-50 to-gray-50 rounded-xl p-6 shadow-lg">
  <!-- Contenido -->
</div>
```

#### Input con Icono Interno
```html
<div class="relative">
  <input type="text" class="input-field pl-10" placeholder="Buscar...">
  <div class="absolute left-3 top-1/2 -translate-y-1/2">
    <svg class="w-5 h-5 text-gray-400"><!-- icon --></svg>
  </div>
</div>
```

#### Badge con Icono
```html
<span class="badge badge-success inline-flex items-center gap-1">
  <svg class="w-4 h-4"><!-- icon --></svg>
  <span>Activo</span>
</span>
```

---

## 🎨 TEMAS DE COLOR POR CONTEXTO

### Dashboard Empleado
- **Primario:** Navy (#003366)
- **Acción:** Green (#22c55e)
- **Alerta:** Yellow (#eab308)
- **Fondo:** Gray-50 (#f9fafb)

### Dashboard Admin
- **Primario:** Navy (#003366)
- **Acento:** Gold (#fdb714)
- **Info:** Blue (#3b82f6)
- **Fondo:** White (#ffffff)

### Clock In/Out
- **Entrada:** Green (#22c55e)
- **Salida:** Red (#ef4444)
- **Neutral:** Gray (#6b7280)
- **Activo:** Navy (#003366)

### Reportes
- **Primario:** Navy (#003366)
- **Gráficas:** Multicolor (Blue, Purple, Green, Orange)
- **Fondos:** White con borders Gray-200

---

## ✅ CHECKLIST DE IMPLEMENTACIÓN

### Al crear un nuevo componente:

- [ ] Usar colores corporativos (Navy y Gold)
- [ ] Aplicar rounded-lg o rounded-xl para esquinas
- [ ] Incluir sombras apropiadas (shadow-lg para cards)
- [ ] Hacer responsive con breakpoints (sm, md, lg)
- [ ] Agregar transiciones en interacciones (transition-all)
- [ ] Usar espaciado consistente (múltiplos de 4)
- [ ] Incluir estados hover y focus
- [ ] Validar contraste de texto (WCAG AA mínimo)
- [ ] Usar badges para estados
- [ ] Aplicar font-medium o font-semibold en títulos

---

**Documento preparado para:** Sistema de Control de Asistencia - Alpe Fresh Mexico
**Basado en:** Marketplace Design System
**Fecha:** Noviembre 2025
**Versión:** 1.0
