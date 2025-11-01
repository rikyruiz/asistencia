# Header Architecture Documentation

## ✅ SINGLE SOURCE OF TRUTH

**ONE header file controls all navigation:** `/var/www/asistencia/includes/header.php`

## File Structure

```
/var/www/asistencia/
├── includes/
│   ├── header.php          ← MAIN HEADER (role-based navigation)
│   ├── head-common.php     ← Font Awesome + Fonts + Styles
│   └── styles.php          ← CSS styles
├── dashboard.php           ← Uses header.php
├── asistencias.php         ← Uses header.php
├── reportes.php            ← Uses header.php
├── ubicaciones.php         ← Uses header.php
└── usuarios.php            ← Uses header.php
```

## How It Works

### 1. In HTML <head> section (BEFORE body):
```php
<!-- Common head elements (Font Awesome, Google Fonts, Styles) -->
<?php include __DIR__ . '/includes/head-common.php'; ?>
```

### 2. In HTML <body> section (AT TOP):
```php
<?php include 'includes/header.php'; ?>
```

## Role-Based Navigation

### Empleado (Employee)
- ✅ Inicio (Dashboard)
- ✅ Asistencias
- ✅ [User Dropdown] → Mi Perfil, Cambiar Contraseña, Cerrar Sesión

### Supervisor
- ✅ Inicio (Dashboard)
- ✅ Asistencias
- ✅ Reportes
- ✅ Ubicaciones
- ✅ [User Dropdown] → Mi Perfil, Cambiar Contraseña, Cerrar Sesión

### Admin
- ✅ Inicio (Dashboard)
- ✅ Asistencias
- ✅ Reportes
- ✅ Ubicaciones
- ✅ **[Admin Dropdown]** → Monitor de Sesiones, Usuarios Pendientes, Usuarios, Empresas, Configuración
- ✅ [User Dropdown] → Mi Perfil, Cambiar Contraseña, Cerrar Sesión

## Code Location

### Navigation Structure (header.php lines 21-37)
```php
$navigationStructure = [
    'empleado' => [
        'Inicio' => ['url' => '/dashboard.php', 'icon' => 'fas fa-home'],
        'Asistencias' => ['url' => '/asistencias.php', 'icon' => 'fas fa-clock']
    ],
    'supervisor' => [
        'Inicio' => ['url' => '/dashboard.php', 'icon' => 'fas fa-home'],
        'Asistencias' => ['url' => '/asistencias.php', 'icon' => 'fas fa-clock'],
        'Reportes' => ['url' => '/reportes.php', 'icon' => 'fas fa-chart-bar'],
        'Ubicaciones' => ['url' => '/ubicaciones.php', 'icon' => 'fas fa-map-marked-alt']
    ],
    'admin' => [
        'Inicio' => ['url' => '/dashboard.php', 'icon' => 'fas fa-home'],
        'Asistencias' => ['url' => '/asistencias.php', 'icon' => 'fas fa-clock'],
        'Reportes' => ['url' => '/reportes.php', 'icon' => 'fas fa-chart-bar'],
        'Ubicaciones' => ['url' => '/ubicaciones.php', 'icon' => 'fas fa-map-marked-alt']
    ]
];
```

### Admin Dropdown (header.php lines 41-47)
```php
$adminDropdownItems = [
    'Monitor de Sesiones' => ['url' => '/admin-monitor.php', 'icon' => 'fas fa-chart-line'],
    'Usuarios Pendientes' => ['url' => '/admin/pending-users.php', 'icon' => 'fas fa-user-clock'],
    'Usuarios' => ['url' => '/usuarios.php', 'icon' => 'fas fa-users'],
    'Empresas' => ['url' => '/empresas.php', 'icon' => 'fas fa-building'],
    'Configuración' => ['url' => '/configuracion.php', 'icon' => 'fas fa-tools']
];
```

## How to Add New Menu Items

### For All Roles:
Edit `header.php` lines 21-37, add to all three role arrays

### For Specific Roles:
Edit `header.php` lines 21-37, add only to specific role arrays

### For Admin Dropdown Only:
Edit `header.php` lines 41-47, add to `$adminDropdownItems`

## Verification

Run this to verify header is being used:
```bash
grep -r "include.*header.php" /var/www/asistencia/*.php
```

All pages should show: `include 'includes/header.php';`

## Common Issues

### Icons not showing?
1. Check browser DevTools Network tab
2. Verify Font Awesome CDN is loaded: `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css`
3. Clear browser cache: Ctrl+Shift+R
4. Check `head-common.php` is included BEFORE `header.php`

### Wrong navigation showing?
1. Check session variable: `$_SESSION['user_rol']`
2. Logout and login again to refresh session
3. Check `header.php` line 15: `$userRole = $_SESSION['user_rol'] ?? 'guest';`

## Cache Prevention

All pages have aggressive cache headers to prevent stale navigation:
- In PHP pages: `header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");`
- In .htaccess: `Header set Cache-Control "no-cache, no-store, must-revalidate, max-age=0"`
- In head-common.php: Meta tags for cache prevention

---

**Last Updated:** 2025-11-01
**Verified Working:** ✅
