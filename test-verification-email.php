<?php
/**
 * Test Verification Email Template
 * This shows what the user receives when they register
 */

require_once 'app/config/config.php';
require_once 'app/helpers/EmailHelper.php';

// Simulate a new user registration
$testData = [
    'email' => 'rikyruiz@gmail.com', // Change to your email
    'nombre' => 'Ricardo',
    'apellidos' => 'Ruiz',
    'username' => 'rickytest',
    'numero_empleado' => 'EMP0001'
];

echo "========================================\n";
echo "Testing VERIFICATION EMAIL Template\n";
echo "========================================\n\n";

echo "Simulating registration for:\n";
echo "Name: {$testData['nombre']} {$testData['apellidos']}\n";
echo "Username: {$testData['username']}\n";
echo "Email: {$testData['email']}\n";
echo "Employee #: {$testData['numero_empleado']}\n\n";

// Generate a fake token (like the system does)
$token = bin2hex(random_bytes(32));

echo "Sending verification email...\n\n";

$emailHelper = new EmailHelper();
$result = $emailHelper->sendVerificationEmail(
    $testData['email'],
    $testData['nombre'],
    $token
);

if ($result) {
    echo "✓ Verification email sent successfully!\n\n";
    echo "The email contains:\n";
    echo "- Welcome message for {$testData['nombre']}\n";
    echo "- Verification button linking to: " . url('auth/verify/' . $token) . "\n";
    echo "- 24-hour expiration warning\n";
    echo "- Professional HTML design with Alpe Fresh branding\n\n";
    echo "Check your inbox at: {$testData['email']}\n";
    echo "(Don't forget to check spam folder!)\n";
} else {
    echo "✗ Failed to send verification email.\n";
}

echo "\n========================================\n";
echo "Testing PASSWORD RESET EMAIL Template\n";
echo "========================================\n\n";

echo "Simulating password reset request...\n\n";

$resetToken = bin2hex(random_bytes(32));

$result2 = $emailHelper->sendPasswordResetEmail(
    $testData['email'],
    $testData['nombre'],
    $resetToken
);

if ($result2) {
    echo "✓ Password reset email sent successfully!\n\n";
    echo "The email contains:\n";
    echo "- Personalized greeting for {$testData['nombre']}\n";
    echo "- Reset button linking to: " . url('auth/reset-password/' . $resetToken) . "\n";
    echo "- Security notice if they didn't request it\n";
    echo "- 24-hour expiration warning\n\n";
    echo "Check your inbox at: {$testData['email']}\n";
} else {
    echo "✗ Failed to send password reset email.\n";
}

echo "\n========================================\n";
echo "Testing WELCOME EMAIL Template\n";
echo "========================================\n\n";

echo "Simulating welcome email after verification...\n\n";

$result3 = $emailHelper->sendWelcomeEmail(
    $testData['email'],
    $testData['nombre'],
    $testData['numero_empleado']
);

if ($result3) {
    echo "✓ Welcome email sent successfully!\n\n";
    echo "The email contains:\n";
    echo "- Congratulations message\n";
    echo "- Employee number: {$testData['numero_empleado']}\n";
    echo "- List of features they can now use\n";
    echo "- Login button\n\n";
    echo "Check your inbox at: {$testData['email']}\n";
} else {
    echo "✗ Failed to send welcome email.\n";
}

echo "\n========================================\n";
echo "All test emails have been sent!\n";
echo "Check your inbox at: {$testData['email']}\n";
echo "========================================\n";
