<?php
/**
 * Email Test Script
 * Run this to test email configuration
 */

require_once 'app/config/config.php';
require_once 'app/helpers/EmailHelper.php';

echo "Testing email configuration...\n\n";
echo "SMTP Host: " . MAIL_HOST . "\n";
echo "SMTP Port: " . MAIL_PORT . "\n";
echo "SMTP User: " . MAIL_USERNAME . "\n";
echo "From Address: " . MAIL_FROM_ADDRESS . "\n\n";

// Test email address - change this to your email
$testEmail = "rikyruiz@gmail.com"; // Change this to your email

echo "Sending test email to: $testEmail\n\n";

$emailHelper = new EmailHelper();
$result = $emailHelper->send(
    $testEmail,
    "Test Email - Sistema de Asistencia",
    "<h1>Test Email</h1><p>If you receive this, your email configuration is working correctly!</p><p>Sistema: " . SITE_NAME . "</p>",
    true
);

if ($result) {
    echo "✓ Email sent successfully!\n";
    echo "Check your inbox (and spam folder) at: $testEmail\n";
} else {
    echo "✗ Failed to send email.\n";
    echo "Check the error log for details.\n";
}
