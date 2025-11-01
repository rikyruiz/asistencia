<?php
/**
 * Fresh Page Check - Verify no caching is happening
 */
session_start();

// Ultra-aggressive cache prevention
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0, post-check=0, pre-check=0");
header("Pragma: no-cache");
header("Expires: Mon, 01 Jan 1990 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header('Content-Type: text/html; charset=UTF-8');

// Generate unique timestamp
$timestamp = date('Y-m-d H:i:s');
$microtime = microtime(true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Fresh Check - <?php echo $microtime; ?></title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .timestamp {
            font-size: 24px;
            font-weight: bold;
            color: #003366;
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: center;
        }
        .pass { color: #16a34a; font-weight: bold; }
        .fail { color: #dc2626; font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #003366;
            color: white;
        }
        .refresh-btn {
            padding: 15px 30px;
            background: #003366;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
        }
        .refresh-btn:hover {
            background: #004488;
        }
    </style>
</head>
<body>
    <h1>🕐 Fresh Page Check</h1>

    <div class="box">
        <h2>Page Generation Timestamp:</h2>
        <div class="timestamp">
            <?php echo $timestamp; ?><br>
            <small>Microtime: <?php echo $microtime; ?></small>
        </div>
        <p><strong>Instructions:</strong> This timestamp should change EVERY time you refresh the page. If it doesn't change, your browser is serving cached content.</p>
        <button onclick="location.reload(true)" class="refresh-btn">🔄 Hard Refresh This Page</button>
    </div>

    <div class="box">
        <h2>Your Current Session:</h2>
        <table>
            <tr>
                <th>Variable</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <tr>
                <td><code>$_SESSION['user_id']</code></td>
                <td><?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></td>
                <td><?php echo ($_SESSION['user_id'] ?? 0) == 13 ? '<span class="pass">✅ Correct (Cta Pba)</span>' : '<span class="fail">❌ Wrong</span>'; ?></td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_nombre']</code></td>
                <td><?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'NOT SET'); ?></td>
                <td><?php echo ($_SESSION['user_nombre'] ?? '') == 'Cta Pba' ? '<span class="pass">✅ Correct</span>' : '<span class="fail">❌ Wrong</span>'; ?></td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_rol']</code></td>
                <td><?php echo htmlspecialchars($_SESSION['user_rol'] ?? 'NOT SET'); ?></td>
                <td><?php echo ($_SESSION['user_rol'] ?? '') == 'empleado' ? '<span class="pass">✅ Correct</span>' : '<span class="fail">❌ Wrong</span>'; ?></td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Should Admin Dropdown Show?</h2>
        <table>
            <tr>
                <th>Condition</th>
                <th>Result</th>
            </tr>
            <tr>
                <td><code>$_SESSION['user_rol'] === 'admin'</code></td>
                <td>
                    <?php
                    $isAdmin = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin');
                    echo $isAdmin ? '<span class="fail">❌ TRUE (will show admin dropdown)</span>' : '<span class="pass">✅ FALSE (will NOT show admin dropdown)</span>';
                    ?>
                </td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_rol'] === 'empleado'</code></td>
                <td>
                    <?php
                    $isEmpleado = (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'empleado');
                    echo $isEmpleado ? '<span class="pass">✅ TRUE</span>' : '<span class="fail">❌ FALSE</span>';
                    ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>🚨 DEFINITIVE SOLUTION:</h2>
        <div style="background: #fff3cd; padding: 20px; border-radius: 6px; border-left: 4px solid #ffc107;">
            <h3>Your browser cache is STUCK. Here's what to do:</h3>

            <h4>Method 1: Complete Browser Reset</h4>
            <ol>
                <li>Press <kbd>Ctrl + Shift + Delete</kbd></li>
                <li>Select "All time" for time range</li>
                <li>Check these boxes:
                    <ul>
                        <li>✅ Browsing history</li>
                        <li>✅ Cookies and other site data</li>
                        <li>✅ Cached images and files</li>
                    </ul>
                </li>
                <li>Click "Clear data"</li>
                <li>Close ALL browser tabs</li>
                <li>Close browser completely</li>
                <li>Reopen browser</li>
                <li>Login again</li>
            </ol>

            <h4>Method 2: Use Different Browser (Quick Test)</h4>
            <p>Try Firefox, Edge, or Safari if you're on Chrome</p>

            <h4>Method 3: Incognito Mode (GUARANTEED)</h4>
            <ol>
                <li>Press <kbd>Ctrl + Shift + N</kbd> (Chrome) or <kbd>Ctrl + Shift + P</kbd> (Firefox)</li>
                <li>Go to: <code>https://asistencia.alpefresh.app/login.php</code></li>
                <li>Login as Cta Pba</li>
                <li>You will ONLY see "Inicio" and "Asistencias"</li>
            </ol>

            <h4>Method 4: Disable Browser Extensions</h4>
            <p>Some extensions cache aggressively. Try disabling all extensions temporarily.</p>
        </div>
    </div>

    <div style="margin-top: 20px;">
        <a href="/dashboard.php" style="padding: 10px 20px; background: #003366; color: white; text-decoration: none; border-radius: 6px;">
            Go to Dashboard →
        </a>
    </div>

    <script>
        // Force reload every 5 seconds to show timestamp changing
        let countdown = 5;
        const countdownEl = document.createElement('div');
        countdownEl.style.cssText = 'position: fixed; top: 20px; right: 20px; background: #003366; color: white; padding: 15px; border-radius: 6px; font-size: 16px;';
        document.body.appendChild(countdownEl);

        setInterval(() => {
            countdownEl.textContent = `Auto-refresh in ${countdown}s to prove page is fresh`;
            countdown--;
            if (countdown < 0) {
                location.reload(true);
            }
        }, 1000);
    </script>
</body>
</html>
