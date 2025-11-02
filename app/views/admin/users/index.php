<div class="max-w-7xl mx-auto px-4 py-8 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Usuarios</h1>
            <p class="text-gray-600 mt-1">Administra los usuarios del sistema</p>
        </div>
        <a href="<?= url('admin/createUser') ?>"
           class="inline-flex items-center px-4 py-2 bg-navy text-white rounded-lg hover:bg-primary-700 transition-colors">
            <i class="fas fa-user-plus mr-2"></i>
            Nuevo Usuario
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <form method="GET" action="<?= url('admin/users') ?>" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Role Filter -->
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                    Rol
                </label>
                <select id="role" name="role"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <option value="">Todos los roles</option>
                    <option value="superadmin" <?= $filters['role'] === 'superadmin' ? 'selected' : '' ?>>Superadmin</option>
                    <option value="admin" <?= $filters['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="inspector" <?= $filters['role'] === 'inspector' ? 'selected' : '' ?>>Inspector</option>
                    <option value="empleado" <?= $filters['role'] === 'empleado' ? 'selected' : '' ?>>Empleado</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Estado
                </label>
                <select id="status" name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <option value="">Todos</option>
                    <option value="1" <?= $filters['status'] === '1' ? 'selected' : '' ?>>Activos</option>
                    <option value="0" <?= $filters['status'] === '0' ? 'selected' : '' ?>>Inactivos</option>
                </select>
            </div>

            <!-- Location Filter -->
            <div>
                <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                    Ubicación
                </label>
                <select id="location" name="location"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-navy focus:border-transparent">
                    <option value="">Todas las ubicaciones</option>
                    <?php foreach ($locations as $location): ?>
                    <option value="<?= $location['id'] ?>" <?= $filters['location'] == $location['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($location['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <button type="submit"
                        class="w-full bg-navy text-white px-4 py-2 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Usuario
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rol
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            No. Empleado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-users text-6xl text-gray-300 mb-4"></i>
                            <p class="text-lg">No se encontraron usuarios</p>
                            <p class="text-sm mt-2">Ajusta los filtros o crea un nuevo usuario</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50">
                            <!-- User Info -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-navy rounded-full flex items-center justify-center text-white font-semibold">
                                        <?= strtoupper(substr($user['nombre'], 0, 1) . substr($user['apellidos'], 0, 1)) ?>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']) ?>
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            @<?= htmlspecialchars($user['username'] ?? 'N/A') ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($user['email']) ?>
                                </div>
                                <?php if ($user['email_verificado']): ?>
                                <div class="text-xs text-green-600">
                                    <i class="fas fa-check-circle mr-1"></i>Verificado
                                </div>
                                <?php else: ?>
                                <div class="text-xs text-orange-600">
                                    <i class="fas fa-exclamation-circle mr-1"></i>Sin verificar
                                </div>
                                <?php endif; ?>
                            </td>

                            <!-- Role -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $roleColors = [
                                    'superadmin' => 'bg-purple-100 text-purple-800',
                                    'admin' => 'bg-blue-100 text-blue-800',
                                    'inspector' => 'bg-yellow-100 text-yellow-800',
                                    'empleado' => 'bg-green-100 text-green-800'
                                ];
                                $roleNames = [
                                    'superadmin' => 'Superadmin',
                                    'admin' => 'Admin',
                                    'inspector' => 'Inspector',
                                    'empleado' => 'Empleado'
                                ];
                                $colorClass = $roleColors[$user['rol']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $colorClass ?>">
                                    <?= $roleNames[$user['rol']] ?? $user['rol'] ?>
                                </span>
                            </td>

                            <!-- Employee Number -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="font-mono"><?= htmlspecialchars($user['numero_empleado']) ?></span>
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['activo']): ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>Activo
                                </span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <i class="fas fa-times-circle mr-1"></i>Inactivo
                                </span>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="<?= url('admin/editUser/' . $user['id']) ?>"
                                       class="text-blue-600 hover:text-blue-800"
                                       title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if (getUserId() != $user['id']): ?>
                                    <button onclick="toggleUserStatus(<?= $user['id'] ?>, <?= $user['activo'] ? 'false' : 'true' ?>)"
                                            class="<?= $user['activo'] ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800' ?>"
                                            title="<?= $user['activo'] ? 'Desactivar' : 'Activar' ?>">
                                        <i class="fas fa-<?= $user['activo'] ? 'ban' : 'check' ?>"></i>
                                    </button>

                                    <button onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nombre'] . ' ' . $user['apellidos']) ?>')"
                                            class="text-red-600 hover:text-red-800"
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php else: ?>
                                    <span class="text-gray-400" title="No puedes modificar tu propio usuario">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Summary -->
    <?php if (!empty($users)): ?>
    <div class="mt-4 text-sm text-gray-600 text-center">
        Total de usuarios: <strong><?= count($users) ?></strong>
    </div>
    <?php endif; ?>
</div>

<script>
function toggleUserStatus(userId, activate) {
    const action = activate ? 'activar' : 'desactivar';
    if (!confirm(`¿Estás seguro de que deseas ${action} este usuario?`)) {
        return;
    }

    fetch(`<?= url('admin/toggleUserStatus/') ?>${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            <?= CSRF_TOKEN_NAME ?>: '<?= $csrf_token ?>',
            status: activate ? '1' : '0'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error al modificar el usuario');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}

function deleteUser(userId, userName) {
    if (!confirm(`¿Estás seguro de que deseas eliminar al usuario "${userName}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }

    fetch(`<?= url('admin/deleteUser/') ?>${userId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams({
            <?= CSRF_TOKEN_NAME ?>: '<?= $csrf_token ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error al eliminar el usuario');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la solicitud');
    });
}
</script>
