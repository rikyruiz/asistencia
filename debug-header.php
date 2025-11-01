<?php
/**
 * Debug Header Info
 */
session_start();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Header Debug</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .debug-box {
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
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>🔍 Header Debug Tool</h1>

    <div class="debug-box">
        <h2>Session Data:</h2>
        <table>
            <tr>
                <th>Variable</th>
                <th>Value</th>
                <th>Type</th>
            </tr>
            <tr>
                <td><code>$_SESSION['user_rol']</code></td>
                <td><strong><?php echo htmlspecialchars($_SESSION['user_rol'] ?? 'NOT SET'); ?></strong></td>
                <td><?php echo gettype($_SESSION['user_rol'] ?? null); ?></td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_id']</code></td>
                <td><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'NOT SET'); ?></td>
                <td><?php echo gettype($_SESSION['user_id'] ?? null); ?></td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_nombre']</code></td>
                <td><?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'NOT SET'); ?></td>
                <td><?php echo gettype($_SESSION['user_nombre'] ?? null); ?></td>
            </tr>
        </table>
    </div>

    <div class="debug-box">
        <h2>Admin Dropdown Logic Test:</h2>

        <p><strong>Condition:</strong> <code>$_SESSION['user_rol'] === 'admin'</code></p>

        <table>
            <tr>
                <th>Test</th>
                <th>Result</th>
            </tr>
            <tr>
                <td>Is <code>$_SESSION['user_rol']</code> set?</td>
                <td class="<?php echo isset($_SESSION['user_rol']) ? 'pass' : 'fail'; ?>">
                    <?php echo isset($_SESSION['user_rol']) ? '✅ YES' : '❌ NO'; ?>
                </td>
            </tr>
            <tr>
                <td>Value of <code>$_SESSION['user_rol']</code></td>
                <td><?php echo '<code>' . htmlspecialchars(var_export($_SESSION['user_rol'] ?? null, true)) . '</code>'; ?></td>
            </tr>
            <tr>
                <td>String length</td>
                <td><?php echo isset($_SESSION['user_rol']) ? strlen($_SESSION['user_rol']) : 'N/A'; ?> characters</td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_rol'] === 'admin'</code></td>
                <td class="<?php echo (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') ? 'fail' : 'pass'; ?>">
                    <?php echo (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin') ? '❌ TRUE (Admin dropdown WILL show)' : '✅ FALSE (Admin dropdown will NOT show)'; ?>
                </td>
            </tr>
            <tr>
                <td><code>$_SESSION['user_rol'] === 'empleado'</code></td>
                <td class="<?php echo (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'empleado') ? 'pass' : 'fail'; ?>">
                    <?php echo (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'empleado') ? '✅ TRUE' : '❌ FALSE'; ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="debug-box">
        <h2>Simulated Header Output:</h2>

        <p><strong>Based on current session, the header should show:</strong></p>

        <div style="background: #f9f9f9; padding: 15px; border-radius: 6px; border-left: 4px solid #003366;">
            <p>User: <strong><?php echo htmlspecialchars($_SESSION['user_nombre'] ?? 'Guest'); ?></strong></p>
            <p>Role: <strong><?php echo htmlspecialchars($_SESSION['user_rol'] ?? 'none'); ?></strong></p>
            <p>Admin Dropdown:
                <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
                    <span class="fail">❌ VISIBLE (This is WRONG for empleado!)</span>
                <?php else: ?>
                    <span class="pass">✅ HIDDEN (Correct for empleado)</span>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="debug-box">
        <h2>Byte-Level Analysis:</h2>
        <p>Checking for hidden characters or encoding issues...</p>

        <?php if (isset($_SESSION['user_rol'])): ?>
            <table>
                <tr>
                    <th>Character</th>
                    <th>ASCII/Hex</th>
                </tr>
                <?php
                $rol = $_SESSION['user_rol'];
                for ($i = 0; $i < strlen($rol); $i++) {
                    $char = $rol[$i];
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($char) . '</td>';
                    echo '<td>' . ord($char) . ' (0x' . dechex(ord($char)) . ')</td>';
                    echo '</tr>';
                }
                ?>
            </table>

            <p><strong>Trimmed value:</strong> <code><?php echo htmlspecialchars(trim($_SESSION['user_rol'])); ?></code></p>
            <p><strong>After trim, equals 'empleado'?</strong>
                <?php echo (trim($_SESSION['user_rol']) === 'empleado') ? '<span class="pass">✅ YES</span>' : '<span class="fail">❌ NO</span>'; ?>
            </p>
        <?php else: ?>
            <p>user_rol is not set!</p>
        <?php endif; ?>
    </div>

    <div class="debug-box">
        <h2>Action Required:</h2>

        <?php if (isset($_SESSION['user_rol']) && $_SESSION['user_rol'] === 'admin'): ?>
            <div style="background: #fee2e2; padding: 15px; border-radius: 6px; border-left: 4px solid #dc2626;">
                <p><strong>⚠️ PROBLEM DETECTED!</strong></p>
                <p>Session says user_rol = 'admin' but database shows user_id <?php echo $_SESSION['user_id']; ?> is 'empleado'!</p>
                <p><strong>Solution:</strong> Logout and login again to refresh the session.</p>
                <a href="/logout.php" style="display: inline-block; padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; margin-top: 10px;">
                    🚪 Logout Now
                </a>
            </div>
        <?php else: ?>
            <div style="background: #dcfce7; padding: 15px; border-radius: 6px; border-left: 4px solid #16a34a;">
                <p><strong>✅ SESSION IS CORRECT!</strong></p>
                <p>user_rol is NOT 'admin', so admin dropdown should be hidden.</p>
                <p>If you still see it, this is a browser cache issue.</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top: 20px;">
        <a href="/dashboard.php" style="color: #003366;">← Back to Dashboard</a> |
        <a href="/check-session.php" style="color: #003366;">Full Session Check</a>
    </div>
</body>
</html>
