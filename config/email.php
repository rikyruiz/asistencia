<?php
/**
 * Configuración de Email - Sistema de Asistencia
 * Usando credenciales de Hostinger SMTP
 */

return [
    // Configuración SMTP de Hostinger
    'smtp' => [
        'host' => 'smtp.hostinger.com',
        'port' => 587,
        'username' => 'notificaciones@alpefresh.app',
        'password' => 'Alpe25879*',
        'encryption' => 'tls',
        'from_email' => 'notificaciones@alpefresh.app',
        'from_name' => 'Sistema de Asistencia - AlpeFresh',
        'timeout' => 30
    ],

    // Plantillas de email
    'templates' => [
        'welcome' => [
            'subject' => 'Bienvenido al Sistema de Asistencia AlpeFresh',
            'priority' => 'normal'
        ],
        'password_reset' => [
            'subject' => 'Restablecer Contraseña - Sistema de Asistencia',
            'priority' => 'high'
        ],
        'account_approved' => [
            'subject' => 'Tu cuenta ha sido aprobada',
            'priority' => 'normal'
        ],
        'account_pending' => [
            'subject' => 'Registro recibido - Pendiente de aprobación',
            'priority' => 'normal'
        ],
        'admin_new_registration' => [
            'subject' => 'Nuevo registro pendiente de aprobación',
            'priority' => 'high'
        ]
    ],

    // Configuración de desarrollo/producción
    'environment' => [
        'force_smtp' => true,  // Siempre usar SMTP incluso en localhost
        'debug' => false,      // Mostrar información de debug
        'test_mode' => false,  // Si está activo, no envía emails reales
        'test_email' => null   // Email de prueba para modo test
    ]
];