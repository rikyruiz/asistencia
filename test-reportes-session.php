<?php
/**
 * Test what happens when navigating to reportes
 */
session_start();

header('Content-Type: text/html; charset=UTF-8');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes Session Test</title>
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
    </style>
</head>
<body>
    <h1>🔍 Reportes Page - Session Test</h1>

    <div class="box">
        <h2>Current Session (Before reportes.php logic):</h2>
        <table>
            <tr>
                <th>Key</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>user_id</td>
                <td class="<?php echo ($_SESSION['user_id'] ?? 0) == 13 ? 'pass' : 'fail'; ?>">
                    <?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?>
                    <?php echo ($_SESSION['user_id'] ?? 0) == 13 ? ' ✅' : ' ❌'; ?>
                </td>
            </tr>
            <tr>
                <td>user_nombre</td>
                <td class="<?php echo ($_SESSION['user_nombre'] ?? '') == 'Cta Pba' ? 'pass' : 'fail'; ?>">
                    <?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'NOT SET'); ?>
                    <?php echo ($_SESSION['user_nombre'] ?? '') == 'Cta Pba' ? ' ✅' : ' ❌'; ?>
                </td>
            </tr>
            <tr>
                <td>user_rol</td>
                <td class="<?php echo ($_SESSION['user_rol'] ?? '') == 'empleado' ? 'pass' : 'fail'; ?>">
                    <?php echo htmlspecialchars($_SESSION['user_rol'] ?? 'NOT SET'); ?>
                    <?php echo ($_SESSION['user_rol'] ?? '') == 'empleado' ? ' ✅ CORRECT' : ' ❌ WRONG'; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <h2>Test: Include Header</h2>
        <p>Let's see what happens when we include the header.php file...</p>

        <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; margin: 10px 0;">
            <?php
            $page_title = 'Test Reportes';
            $page_subtitle = 'Testing Session';

            // Capture output
            ob_start();
            require_once 'includes/header.php';
            $headerOutput = ob_get_clean();

            // Check if Admin dropdown is in the output
            $hasAdminDropdown = (strpos($headerOutput, 'Admin ▼') !== false);
            ?>

            <p><strong>Header Output Contains "Admin ▼":</strong>
                <span class="<?php echo $hasAdminDropdown ? 'fail' : 'pass'; ?>">
                    <?php echo $hasAdminDropdown ? '❌ YES (WRONG!)' : '✅ NO (Correct)'; ?>
                </span>
            </p>

            <p><strong>Session After Header Include:</strong></p>
            <table>
                <tr>
                    <td>user_id</td>
                    <td><?php echo $_SESSION['user_id'] ?? 'NOT SET'; ?></td>
                </tr>
                <tr>
                    <td>user_rol</td>
                    <td class="<?php echo ($_SESSION['user_rol'] ?? '') == 'empleado' ? 'pass' : 'fail'; ?>">
                        <?php echo htmlspecialchars($_SESSION['user_rol'] ?? 'NOT SET'); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <h2>Actual Header Output:</h2>
        <div style="border: 2px solid #ddd; padding: 10px;">
            <?php echo $headerOutput; ?>
        </div>
    </div>

    <div class="box">
        <h2>Diagnosis:</h2>
        <?php if ($hasAdminDropdown && $_SESSION['user_rol'] == 'empleado'): ?>
            <div style="background: #fee2e2; padding: 15px; border-radius: 6px;">
                <p><strong>⚠️ BUG CONFIRMED!</strong></p>
                <p>Session says 'empleado' but header shows Admin dropdown!</p>
                <p>This means the PHP condition is NOT working correctly.</p>
            </div>
        <?php elseif (!$hasAdminDropdown && $_SESSION['user_rol'] == 'empleado'): ?>
            <div style="background: #dcfce7; padding: 15px; border-radius: 6px;">
                <p><strong>✅ WORKING CORRECTLY!</strong></p>
                <p>Session is 'empleado' and Admin dropdown is NOT in output.</p>
                <p>If you see it in your browser, it's definitely browser cache.</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="/reportes.php" style="padding: 10px 20px; background: #003366; color: white; text-decoration: none; border-radius: 6px;">
            Go to Actual Reportes Page →
        </a>
    </div>
</body>
</html>
