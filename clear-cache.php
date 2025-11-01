<?php
/**
 * Clear PHP OPcache and session cache
 * Visit this page to force clear all caches
 */

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    $opcache = "✅ OPcache cleared";
} else {
    $opcache = "⚠️ OPcache not enabled";
}

// Clear session cache
session_start();
$sessionBefore = $_SESSION;
session_destroy();
session_start();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cache Cleared</title>
    <style>
        body {
            font-family: monospace;
            padding: 40px;
            background: #f5f5f5;
            text-align: center;
        }
        .box {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .success {
            color: #16a34a;
            font-size: 24px;
            margin: 20px 0;
        }
        .info {
            color: #666;
            margin: 10px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #003366;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn:hover {
            background: #004488;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>🧹 Cache Cleared!</h1>

        <div class="success">
            <?php echo $opcache; ?>
        </div>

        <div class="info">
            ✅ Session destroyed (you'll need to login again)
        </div>

        <p style="margin-top: 30px; color: #666;">
            <strong>Next steps:</strong><br>
            1. Close this browser tab<br>
            2. Press Ctrl+Shift+Delete to clear browser cache<br>
            3. Clear "Cached images and files"<br>
            4. Go back to the site and login again
        </p>

        <a href="/login.php" class="btn">Go to Login</a>
    </div>
</body>
</html>
