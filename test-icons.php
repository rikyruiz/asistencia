<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Font Awesome Test</title>

    <!-- Test 1: Direct CDN include -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .test-box {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .icon-test {
            display: flex;
            gap: 20px;
            align-items: center;
            margin: 10px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .icon-test i {
            font-size: 24px;
            color: #003366;
        }
        .status-pass {
            color: #16a34a;
            font-weight: bold;
        }
        .status-fail {
            color: #dc2626;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🔍 Font Awesome Icon Test</h1>

    <div class="test-box">
        <h2>Testing Font Awesome 6.4.0 Icons</h2>
        <p>If you can see icons below, Font Awesome is working:</p>

        <div class="icon-test">
            <i class="fas fa-home"></i>
            <span>Home Icon (fas fa-home)</span>
            <span id="home-status"></span>
        </div>

        <div class="icon-test">
            <i class="fas fa-clock"></i>
            <span>Clock Icon (fas fa-clock)</span>
            <span id="clock-status"></span>
        </div>

        <div class="icon-test">
            <i class="fas fa-chart-bar"></i>
            <span>Chart Icon (fas fa-chart-bar)</span>
            <span id="chart-status"></span>
        </div>

        <div class="icon-test">
            <i class="fas fa-map-marked-alt"></i>
            <span>Map Icon (fas fa-map-marked-alt)</span>
            <span id="map-status"></span>
        </div>

        <div class="icon-test">
            <i class="fas fa-cog"></i>
            <span>Cog Icon (fas fa-cog) - Admin dropdown</span>
            <span id="cog-status"></span>
        </div>

        <div class="icon-test">
            <i class="fas fa-user"></i>
            <span>User Icon (fas fa-user)</span>
            <span id="user-status"></span>
        </div>
    </div>

    <div class="test-box">
        <h2>Include Test</h2>
        <h3>Testing head-common.php include:</h3>
        <?php
        echo "<pre>";
        echo "File exists: " . (file_exists(__DIR__ . '/includes/head-common.php') ? 'YES' : 'NO') . "\n";
        echo "Path: " . __DIR__ . '/includes/head-common.php' . "\n";

        if (file_exists(__DIR__ . '/includes/head-common.php')) {
            echo "\nFile contents preview:\n";
            echo htmlspecialchars(substr(file_get_contents(__DIR__ . '/includes/head-common.php'), 0, 500)) . "...";
        }
        echo "</pre>";
        ?>
    </div>

    <div class="test-box">
        <h2>Now test with head-common.php include:</h2>
        <?php include __DIR__ . '/includes/head-common.php'; ?>

        <div class="icon-test">
            <i class="fas fa-check-circle"></i>
            <span>Check Circle (After include)</span>
        </div>
    </div>

    <div class="test-box">
        <h2>Network Resources Loaded:</h2>
        <p>Check DevTools Network tab to see if Font Awesome CSS is loaded from CDN.</p>
        <p>Expected: https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css</p>
    </div>

    <script>
        // Check if Font Awesome loaded by checking computed styles
        function checkIcon(iconClass, statusId) {
            const icon = document.querySelector('.' + iconClass);
            if (icon) {
                const computedStyle = window.getComputedStyle(icon, ':before');
                const content = computedStyle.getPropertyValue('content');
                const status = document.getElementById(statusId);
                if (content && content !== 'none' && content !== 'normal') {
                    status.innerHTML = '<span class="status-pass">✅ LOADED</span>';
                } else {
                    status.innerHTML = '<span class="status-fail">❌ NOT LOADED</span>';
                }
            }
        }

        window.onload = function() {
            setTimeout(() => {
                checkIcon('fa-home', 'home-status');
                checkIcon('fa-clock', 'clock-status');
                checkIcon('fa-chart-bar', 'chart-status');
                checkIcon('fa-map-marked-alt', 'map-status');
                checkIcon('fa-cog', 'cog-status');
                checkIcon('fa-user', 'user-status');
            }, 500);
        };
    </script>
</body>
</html>