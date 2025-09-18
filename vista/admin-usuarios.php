<?php
session_start();

// Verificar si el usuario está logueado y es administrador (id_rol = 3)
if (!isset($_SESSION['usuario']) || $_SESSION['id_rol'] != 3) {
    header('Location: ../login.php');
    exit();
}

include '../config/Conexion.php';

// Obtener todos los usuarios (menos administradores)
$query = "
    SELECT 
        u.id,
        u.nombre,
        u.email,
        r.nombre AS rol,
        u.fcharegistro,
        COUNT(t.id) AS total_tickets
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id
    LEFT JOIN tickets t ON u.id = t.id_usuario_creador
    WHERE r.nombre != 'administrador'
    GROUP BY u.id, u.nombre, u.email, r.nombre, u.fcharegistro
    ORDER BY u.fcharegistro DESC
";

$result = $conexion->query($query);

// Consultar métricas rápidas
// Total usuarios normales (clientes)
$usuarios_query = "
    SELECT COUNT(*) as count 
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id
    WHERE r.nombre = 'cliente'
";
$usuarios_count = $conexion->query($usuarios_query)->fetch_assoc()['count'];

// Total técnicos
$tecnicos_query = "
    SELECT COUNT(*) as count 
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id
    WHERE r.nombre = 'tecnico'
";
$tecnicos_count = $conexion->query($tecnicos_query)->fetch_assoc()['count'];

// Total usuarios (clientes + técnicos, sin administradores)
$total_usuarios_query = "
    SELECT COUNT(*) as count 
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id
    WHERE r.nombre != 'administrador'
";
$total_usuarios = $conexion->query($total_usuarios_query)->fetch_assoc()['count'];

$mensaje = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Administrar Usuarios - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/admin-usuario.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    </head>
        <!-- DataTables CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
        <style>
            /* Integración visual DataTables con tu diseño */
            .dataTables_wrapper .dataTables_filter input {
                border-radius: 8px;
                border: 2px solid #e9ecef;
                padding: 8px 12px;
                font-size: 14px;
                margin-left: 0.5em;
            }
            .dataTables_wrapper .dataTables_length select {
                border-radius: 8px;
                border: 2px solid #e9ecef;
                padding: 6px 10px;
                font-size: 14px;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button {
                border-radius: 6px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white !important;
                margin: 0 2px;
                padding: 6px 12px;
                border: none;
            }
            .dataTables_wrapper .dataTables_paginate .paginate_button.current {
                background: linear-gradient(135deg, #764ba2 0%, #667eea 100%) !important;
                color: #fff !important;
            }
            .dataTables_wrapper .dataTables_info {
                margin-top: 10px;
                color: #333;
            }
            /* Filtros por columna */
            tfoot input {
                width: 100%;
                padding: 6px;
                box-sizing: border-box;
                border-radius: 6px;
                border: 1.5px solid #e9ecef;
                font-size: 13px;
            }
        </style>
</head>

<body>
    <header class="header">
        <a href="dashboard.php" class="back-btn" title="Volver al Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Administrar Usuarios</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="admin-header">
            <div class="stats" style="display: flex; align-items: center; gap: 2rem;">
                <h2 style="margin-right: 2rem;">Gestión de Usuarios</h2>
                <div class="stats-grid" style="display: flex; gap: 2rem;">
                    <div class="stat-card" style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                        <div>
                            <span class="number"><?php echo $total_usuarios; ?></span>
                            <span class="label">Total Usuarios</span>
                        </div>
                    </div>
                    <div class="stat-card" style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-person" style="font-size: 2rem;"></i>
                        <div>
                            <span class="number"><?php echo $usuarios_count; ?></span>
                            <span class="label">Usuarios</span>
                        </div>
                    </div>
                    <div class="stat-card" style="display: flex; align-items: center; gap: 0.75rem;">
                        <i class="bi bi-person-gear" style="font-size: 2rem;"></i>
                        <div>
                            <span class="number"><?php echo $tecnicos_count; ?></span>
                            <span class="label">Técnicos</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin-actions-header">
                <a href="crear-tecnico.php" class="btn-new">
                    <i class="bi bi-person-plus"></i>
                    Crear Nuevo Técnico
                </a>
            </div>
        </div>

        <div class="users-container">
            <?php if ($result->num_rows > 0): ?>
                <div class="users-table-container">
                    <table class="users-table" id="tabla-usuarios">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Email</th>
                                <th>Rol</th>
                                <th>Tickets Creados</th>
                                <th>Fecha Registro</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th><input type="text" placeholder="Filtrar ID" /></th>
                                <th><input type="text" placeholder="Filtrar Nombre" /></th>
                                <th><input type="text" placeholder="Filtrar Email" /></th>
                                <th><input type="text" placeholder="Filtrar Rol" /></th>
                                <th><input type="text" placeholder="Filtrar Tickets" /></th>
                                <th><input type="text" placeholder="Filtrar Fecha" /></th>
                                <th></th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php while ($usuario = $result->fetch_assoc()): ?>
                                <tr class="user-row" data-role="<?php echo $usuario['rol']; ?>">
                                    <td class="user-id">#<?php echo $usuario['id']; ?></td>
                                    <td class="user-name">
                                        <div class="user-info-cell">
                                            <i class="bi bi-person-circle user-icon"></i>
                                            <?php echo htmlspecialchars($usuario['nombre']); ?>
                                        </div>
                                    </td>
                                    <td class="user-email"><?php echo htmlspecialchars($usuario['email']); ?></td>
                                    <td class="user-role">
                                        <span class="role-badge role-<?php echo $usuario['rol']; ?>">
                                            <?php echo ucfirst($usuario['rol']); ?>
                                        </span>
                                    </td>
                                    <td class="user-tickets">
                                        <span class="tickets-count"><?php echo $usuario['total_tickets']; ?></span>
                                    </td>
                                    <td class="user-date">
                                        <?php echo date('d/m/Y', strtotime($usuario['fcharegistro'])); ?>
                                    </td>
                                    <td class="user-actions">
                                        <?php if (strtolower($usuario['rol']) === 'usuario' || strtolower($usuario['rol']) === 'cliente'): ?>
                                            <!-- Promover -->
                                            <form method="POST" action="../controlador/ContCrearTecnico.php" style="display:inline;">
                                                <input type="hidden" name="accion" value="promover">
                                                <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn-promote">
                                                    <i class="bi bi-arrow-up-circle"></i>
                                                    Promover a Técnico
                                                </button>
                                            </form>
                                        <?php elseif (strtolower($usuario['rol']) === 'tecnico'): ?>
                                            <!-- Degradar -->
                                            <form method="POST" action="../controlador/ContCrearTecnico.php" style="display:inline;">
                                                <input type="hidden" name="accion" value="degradar">
                                                <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                                <button type="submit" class="btn-demote">
                                                    <i class="bi bi-arrow-down-circle"></i>
                                                    Degradar a Usuario
                                                </button>
                                            </form>

                                        <?php else: ?>
                                            <span class="no-action">Es administrador</span>
                                        <?php endif; ?>

                                        <form method="POST" action="../controlador/ContCrearTecnico.php" style="display:inline;"
                                            onsubmit="return confirmarEliminacion('<?php echo htmlspecialchars($usuario['nombre']); ?>', <?php echo $usuario['total_tickets']; ?>);">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="id_usuario" value="<?php echo $usuario['id']; ?>">
                                            <button type="submit" class="btn-delete">
                                                <i class="bi bi-trash"></i>
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-users">
                    <div class="no-users-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <h3>No hay usuarios registrados</h3>
                    <p>No se han registrado usuarios en el sistema</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- jQuery y DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            var table = $('#tabla-usuarios').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                order: [], // No ordenar por defecto
                columnDefs: [
                    { orderable: false, targets: 6 } // Acciones no ordenable
                ]
            });

            // Filtros por columna
            $('#tabla-usuarios tfoot th').each(function (i) {
                var input = $(this).find('input');
                if (input.length) {
                    input.on('keyup change', function () {
                        if (table.column(i).search() !== this.value) {
                            table
                                .column(i)
                                .search(this.value)
                                .draw();
                        }
                    });
                }
            });
        });

        // Función para confirmar eliminación con advertencia de tickets
        function confirmarEliminacion(nombreUsuario, totalTickets) {
            let mensaje = `¿Estás seguro de que deseas eliminar al usuario "${nombreUsuario}"?`;
            
            if (totalTickets > 0) {
                mensaje += `\n\n⚠️ ADVERTENCIA: Este usuario tiene ${totalTickets} ticket(s) asociado(s).`;
                mensaje += `\nLos tickets serán transferidos a un usuario del sistema para mantener el historial.`;
            }
            
            mensaje += `\n\nEsta acción no se puede deshacer.`;
            
            return confirm(mensaje);
        }
    </script>
</body>

</html>