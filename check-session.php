<?php
/**
 * Session Diagnostic Tool
 * Use this to check current session values
 */

session_start();

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Diagnostic</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            max-width: 800px;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #003366;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #003366;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .logout-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #dc2626;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }
        .logout-btn:hover {
            background: #b91c1c;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 0.9em;
        }
        .status-yes {
            background: #dcfce7;
            color: #166534;
        }
        .status-no {
            background: #fee2e2;
            color: #991b1b;
        }
        .role-admin {
            background: #fef3c7;
            color: #92400e;
        }
        .role-supervisor {
            background: #dbeafe;
            color: #1e40af;
        }
        .role-empleado {
            background: #f3e8ff;
            color: #6b21a8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Session Diagnostic Tool</h1>

        <p><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

        <h2>Session Status:</h2>
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <span class="status status-yes">✅ LOGGED IN</span>
        <?php else: ?>
            <span class="status status-no">❌ NOT LOGGED IN</span>
        <?php endif; ?>

        <h2>Session Values:</h2>
        <table>
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($_SESSION)): ?>
                    <?php foreach ($_SESSION as $key => $value): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                            <td>
                                <?php
                                if ($key === 'user_rol') {
                                    $roleClass = 'role-' . $value;
                                    echo '<span class="status ' . $roleClass . '">' . htmlspecialchars($value) . '</span>';
                                } else if (is_bool($value)) {
                                    echo $value ? 'true' : 'false';
                                } else if (is_array($value)) {
                                    echo '<pre>' . print_r($value, true) . '</pre>';
                                } else {
                                    echo htmlspecialchars($value);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="2" style="text-align: center; color: #999;">
                            No session data found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <h2>Expected Values for User ID <?php echo $_SESSION['user_id'] ?? 'N/A'; ?>:</h2>
            <?php
            require_once 'config/database.php';
            $db = db();
            $userId = $_SESSION['user_id'] ?? 0;

            if ($userId) {
                $stmt = $db->prepare("
                    SELECT id, codigo_empleado, nombre, apellidos, email, rol, activo
                    FROM usuarios
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                $dbUser = $stmt->fetch();

                if ($dbUser):
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>Database Field</th>
                            <th>Value</th>
                            <th>Matches Session?</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>ID</strong></td>
                            <td><?php echo $dbUser['id']; ?></td>
                            <td>
                                <?php if ($dbUser['id'] == ($_SESSION['user_id'] ?? null)): ?>
                                    <span class="status status-yes">✅ Match</span>
                                <?php else: ?>
                                    <span class="status status-no">❌ Mismatch</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Nombre Completo</strong></td>
                            <td><?php echo htmlspecialchars($dbUser['nombre'] . ' ' . $dbUser['apellidos']); ?></td>
                            <td>
                                <?php if (($dbUser['nombre'] . ' ' . $dbUser['apellidos']) === ($_SESSION['user_nombre'] ?? null)): ?>
                                    <span class="status status-yes">✅ Match</span>
                                <?php else: ?>
                                    <span class="status status-no">❌ Mismatch</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Email</strong></td>
                            <td><?php echo htmlspecialchars($dbUser['email']); ?></td>
                            <td>
                                <?php if ($dbUser['email'] === ($_SESSION['user_email'] ?? null)): ?>
                                    <span class="status status-yes">✅ Match</span>
                                <?php else: ?>
                                    <span class="status status-no">❌ Mismatch</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Rol</strong></td>
                            <td><span class="status role-<?php echo $dbUser['rol']; ?>"><?php echo htmlspecialchars($dbUser['rol']); ?></span></td>
                            <td>
                                <?php if ($dbUser['rol'] === ($_SESSION['user_rol'] ?? null)): ?>
                                    <span class="status status-yes">✅ Match</span>
                                <?php else: ?>
                                    <span class="status status-no">❌ Mismatch - Session has: <?php echo htmlspecialchars($_SESSION['user_rol'] ?? 'N/A'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php
                endif;
            }
            ?>

            <a href="/logout.php" class="logout-btn">🚪 Logout</a>
        <?php endif; ?>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;">
            <a href="/dashboard.php" style="color: #003366;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
