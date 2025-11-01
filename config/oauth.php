<?php
/**
 * Configuración OAuth2 para Google y Microsoft
 * Sistema de Asistencia - AlpeFresh
 */

return [
    'google' => [
        // Para obtener estas credenciales:
        // 1. Ve a https://console.cloud.google.com/
        // 2. Crea un nuevo proyecto o selecciona uno existente
        // 3. Habilita Google+ API
        // 4. Crea credenciales OAuth 2.0
        // 5. Agrega https://asistencia.alpefresh.app/auth/google/callback como URI de redirección autorizada
        'client_id' => getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID_HERE',
        'client_secret' => getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET_HERE',
        'redirect_uri' => 'https://asistencia.alpefresh.app/auth/google/callback',
        'auth_url' => 'https://accounts.google.com/o/oauth2/v2/auth',
        'token_url' => 'https://oauth2.googleapis.com/token',
        'user_info_url' => 'https://www.googleapis.com/oauth2/v3/userinfo',
        'scopes' => 'openid email profile'
    ],

    'microsoft' => [
        // Para obtener estas credenciales:
        // 1. Ve a https://portal.azure.com/
        // 2. Navega a Azure Active Directory > App registrations
        // 3. Crea una nueva aplicación
        // 4. Agrega https://asistencia.alpefresh.app/auth/microsoft/callback como Redirect URI
        // 5. Crea un client secret en Certificates & secrets
        'client_id' => getenv('MICROSOFT_CLIENT_ID') ?: 'YOUR_MICROSOFT_CLIENT_ID_HERE',
        'client_secret' => getenv('MICROSOFT_CLIENT_SECRET') ?: 'YOUR_MICROSOFT_CLIENT_SECRET_HERE',
        'redirect_uri' => 'https://asistencia.alpefresh.app/auth/microsoft/callback',
        'tenant' => 'common', // o tu tenant específico
        'auth_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
        'token_url' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
        'user_info_url' => 'https://graph.microsoft.com/v1.0/me',
        'scopes' => 'openid email profile User.Read'
    ]
];