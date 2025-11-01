# Configuración de OAuth2 - Sistema de Asistencia AlpeFresh

## Resumen

El sistema de asistencia soporta autenticación mediante Google y Microsoft OAuth2. Esto permite a los usuarios iniciar sesión con sus cuentas corporativas sin necesidad de crear contraseñas adicionales.

## Flujo de Autenticación OAuth2

1. Usuario hace clic en "Iniciar con Google" o "Iniciar con Microsoft"
2. Se redirige al proveedor OAuth para autenticación
3. Usuario autoriza el acceso a su información básica
4. El proveedor redirige de vuelta con un código de autorización
5. Sistema intercambia el código por un token de acceso
6. Sistema obtiene la información del usuario
7. Se crea/actualiza el usuario en la base de datos
8. Se establece la sesión y redirige al dashboard

## URLs de Redirección Configuradas

### Google OAuth
- **Inicio:** `https://asistencia.alpefresh.app/auth/google`
- **Callback:** `https://asistencia.alpefresh.app/auth/google/callback`

### Microsoft OAuth
- **Inicio:** `https://asistencia.alpefresh.app/auth/microsoft`
- **Callback:** `https://asistencia.alpefresh.app/auth/microsoft/callback`

## Configuración de Google OAuth

### 1. Crear proyecto en Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un nuevo proyecto o selecciona uno existente
3. Ve a "APIs y servicios" > "Credenciales"

### 2. Configurar pantalla de consentimiento OAuth

1. En el menú lateral, selecciona "Pantalla de consentimiento OAuth"
2. Selecciona "Externo" si es para usuarios fuera de tu organización
3. Completa la información requerida:
   - Nombre de la aplicación: "Sistema de Asistencia AlpeFresh"
   - Correo de soporte
   - Dominios autorizados: `alpefresh.app`
4. Agrega los alcances (scopes):
   - `openid`
   - `email`
   - `profile`

### 3. Crear credenciales OAuth 2.0

1. Ve a "Credenciales" > "Crear credenciales" > "ID de cliente OAuth"
2. Tipo de aplicación: "Aplicación web"
3. Nombre: "AlpeFresh Asistencia"
4. URIs de redirección autorizados:
   ```
   https://asistencia.alpefresh.app/auth/google/callback
   ```
5. Guarda el Client ID y Client Secret

### 4. Configurar en el sistema

Edita `/var/www/asistencia/config/oauth.php` y reemplaza:
```php
'google' => [
    'client_id' => 'TU_CLIENT_ID_AQUI',
    'client_secret' => 'TU_CLIENT_SECRET_AQUI',
    ...
]
```

## Configuración de Microsoft OAuth

### 1. Registrar aplicación en Azure

1. Ve a [Azure Portal](https://portal.azure.com/)
2. Navega a "Azure Active Directory" > "Registros de aplicaciones"
3. Haz clic en "Nuevo registro"

### 2. Configurar la aplicación

1. Nombre: "AlpeFresh Sistema de Asistencia"
2. Tipos de cuenta compatibles: "Cuentas en cualquier directorio organizativo y cuentas personales de Microsoft"
3. URI de redirección:
   - Tipo: "Web"
   - URI: `https://asistencia.alpefresh.app/auth/microsoft/callback`
4. Registrar la aplicación

### 3. Configurar permisos de API

1. Ve a "Permisos de API"
2. Agrega permisos:
   - Microsoft Graph > Permisos delegados:
     - `openid`
     - `email`
     - `profile`
     - `User.Read`
3. Otorga consentimiento de administrador si es necesario

### 4. Crear secreto de cliente

1. Ve a "Certificados y secretos"
2. "Nuevo secreto de cliente"
3. Descripción: "AlpeFresh Asistencia"
4. Vencimiento: Según tu preferencia
5. Guarda el valor del secreto (solo se muestra una vez)

### 5. Configurar en el sistema

Edita `/var/www/asistencia/config/oauth.php` y reemplaza:
```php
'microsoft' => [
    'client_id' => 'TU_APPLICATION_ID_AQUI',
    'client_secret' => 'TU_CLIENT_SECRET_AQUI',
    ...
]
```

## Variables de Entorno (Recomendado)

Para mayor seguridad, usa variables de entorno en lugar de hardcodear las credenciales:

1. Crea archivo `.env` en `/var/www/asistencia/`:
```bash
GOOGLE_CLIENT_ID=tu_client_id_google
GOOGLE_CLIENT_SECRET=tu_secret_google
MICROSOFT_CLIENT_ID=tu_client_id_microsoft
MICROSOFT_CLIENT_SECRET=tu_secret_microsoft
```

2. Asegúrate que `.env` esté en `.gitignore`

3. El sistema ya está configurado para usar `getenv()` automáticamente

## Gestión de Usuarios OAuth

### Usuarios Nuevos
- Se crean automáticamente al primer login OAuth
- Se asigna empresa por defecto
- Rol inicial: "empleado"
- Código de empleado generado automáticamente

### Usuarios Existentes
- Se vinculan por correo electrónico
- Se actualiza proveedor OAuth y foto de perfil
- Se mantienen roles y permisos existentes

### Base de Datos

La tabla `usuarios` incluye campos OAuth:
- `oauth_provider`: Proveedor usado (google/microsoft)
- `oauth_provider_id`: ID único del usuario en el proveedor
- `foto_url`: URL de la foto de perfil del proveedor

## Solución de Problemas

### Error: "No se pudo iniciar el proceso de autenticación"
- Verifica que las credenciales OAuth estén configuradas correctamente
- Revisa los logs en `/var/log/apache2/error.log`

### Error: "Estado de sesión inválido"
- Problema con cookies/sesiones
- Verifica configuración de sesiones PHP
- Asegúrate que HTTPS esté habilitado

### Error: "Error obteniendo access token"
- Verifica las URLs de callback configuradas en el proveedor
- Asegúrate que coincidan exactamente con las configuradas
- Revisa que el Client Secret sea correcto

## Seguridad

1. **Siempre usa HTTPS** - OAuth requiere conexiones seguras
2. **Protege las credenciales** - Nunca las subas a repositorios públicos
3. **Valida el estado CSRF** - El sistema ya lo hace automáticamente
4. **Revisa permisos** - Solo solicita los scopes necesarios
5. **Monitorea accesos** - Revisa logs regularmente

## Testing

### Ambiente de desarrollo

Para probar localmente, configura URLs de callback adicionales:
- Google: `http://localhost/auth/google/callback`
- Microsoft: `http://localhost/auth/microsoft/callback`

### Verificación

1. Intenta login con cuenta Google
2. Verifica creación/actualización de usuario en BD
3. Confirma que la sesión se establece correctamente
4. Revisa que la foto de perfil se muestra (si está disponible)

## Soporte

Para problemas o preguntas sobre la configuración OAuth:
- Revisa los logs del sistema
- Consulta la documentación oficial de [Google OAuth2](https://developers.google.com/identity/protocols/oauth2)
- Consulta la documentación de [Microsoft Identity Platform](https://docs.microsoft.com/azure/active-directory/develop/)