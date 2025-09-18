<?php
session_start();

// Verificar si el usuario está logueado y es administrador
if (!isset($_SESSION['usuario']) || $_SESSION['id_rol'] != 3) {
    header('Location: ../logueo/login.php');
    exit();
}

include '../config/Conexion.php';

// Obtener todos los tickets con información completa (sin GROUP BY problemático)
$query = "
    SELECT 
        t.id,
        t.titulo,
        t.descripcion,
        t.prioridad,
        t.fecha_creacion,
        t.fecha_actualizacion,
        t.fecha_resolucion,
        c.nombre as categoria,
        e.nombre as estado,
        e.color as estado_color,
        u_creador.nombre as usuario_creador,
        u_tecnico.nombre as tecnico_asignado,
        u_tecnico.id as id_tecnico_asignado,
        (SELECT COUNT(*) FROM comentarios_ticket ct WHERE ct.id_ticket = t.id) as total_comentarios
    FROM tickets t
    LEFT JOIN categorias c ON t.id_categoria = c.id
    LEFT JOIN estados_ticket e ON t.id_estado = e.id
    LEFT JOIN usuarios u_creador ON t.id_usuario_creador = u_creador.id
    LEFT JOIN usuarios u_tecnico ON t.id_tecnico_asignado = u_tecnico.id
    ORDER BY t.fecha_creacion DESC
";

$result = $conexion->query($query);

// Obtener técnicos disponibles (tomando usuarios cuyo rol en la tabla roles es 'tecnico')
$tecnicos_query = "
    SELECT u.id, u.nombre
    FROM usuarios u
    INNER JOIN roles r ON u.id_rol = r.id
    WHERE r.nombre = 'tecnico'
    ORDER BY u.nombre
";
$tecnicos_result = $conexion->query($tecnicos_query);
$tecnicos = [];
if ($tecnicos_result) {
    while ($tecnico = $tecnicos_result->fetch_assoc()) {
        $tecnicos[] = $tecnico;
    }
}

$mensaje = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar Tickets - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/admin-tickets.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <header class="header">
        <a href="dashboard.php" class="back-btn" title="Volver al Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Administrar Tickets</h1>
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
            <div class="stats">
                <h2>Gestión de Tickets</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="bi bi-ticket-perforated"></i>
                        <div>
                            <span class="number"><?php echo $result ? $result->num_rows : 0; ?></span>
                            <span class="label">Total Tickets</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-clock"></i>
                        <div>
                            <?php
                            $pendientes_query = "SELECT COUNT(*) as count FROM tickets WHERE id_estado = 1";
                            $pendientes_result = $conexion->query($pendientes_query);
                            $pendientes = $pendientes_result ? $pendientes_result->fetch_assoc()['count'] : 0;
                            ?>
                            <span class="number"><?php echo $pendientes; ?></span>
                            <span class="label">Pendientes</span>
                        </div>
                    </div>
                    <div class="stat-card">
                        <i class="bi bi-person-x"></i>
                        <div>
                            <?php
                            $sin_asignar_query = "SELECT COUNT(*) as count FROM tickets WHERE id_tecnico_asignado IS NULL";
                            $sin_asignar_result = $conexion->query($sin_asignar_query);
                            $sin_asignar = $sin_asignar_result ? $sin_asignar_result->fetch_assoc()['count'] : 0;
                            ?>
                            <span class="number"><?php echo $sin_asignar; ?></span>
                            <span class="label">Sin Asignar</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel de Filtros -->
            <div class="filters-panel">
                <h3><i class="bi bi-funnel"></i> Filtros</h3>
                <div class="filters-grid">
                    <div class="filter-group">
                        <label>Por Estado:</label>
                        <select id="filtroEstado" onchange="aplicarFiltros()">
                            <option value="todos">Todos los estados</option>
                            <option value="Abierto">Abierto</option>
                            <option value="Asignado">Asignado</option>
                            <option value="En Proceso">En Proceso</option>
                            <option value="Seguimiento">Seguimiento</option>
                            <option value="Cerrado">Cerrado</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Por Prioridad:</label>
                        <select id="filtroPrioridad" onchange="aplicarFiltros()">
                            <option value="todos">Todas las prioridades</option>
                            <option value="urgente">Urgente</option>
                            <option value="alta">Alta</option>
                            <option value="media">Media</option>
                            <option value="baja">Baja</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Por Asignación:</label>
                        <select id="filtroAsignacion" onchange="aplicarFiltros()">
                            <option value="todos">Todos</option>
                            <option value="sin-asignar">Sin asignar</option>
                            <option value="asignados">Asignados</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label>Por Técnico:</label>
                        <select id="filtroTecnico" onchange="aplicarFiltros()">
                            <option value="todos">Todos los técnicos</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?php echo htmlspecialchars($tecnico['nombre']); ?>">
                                    <?php echo htmlspecialchars($tecnico['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="filter-actions">
                    <button class="btn-clear-filters" onclick="limpiarFiltros()">
                        <i class="bi bi-x-circle"></i> Limpiar Filtros
                    </button>
                    <div class="results-count">
                        <span id="contadorResultados">Mostrando todos los tickets</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="tickets-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($ticket = $result->fetch_assoc()): ?>
                    <div class="ticket-card admin-card" 
                         data-priority="<?php echo htmlspecialchars($ticket['prioridad']); ?>"
                         data-estado="<?php echo htmlspecialchars($ticket['estado']); ?>"
                         data-asignacion="<?php echo !empty($ticket['id_tecnico_asignado']) ? 'asignados' : 'sin-asignar'; ?>"
                         data-tecnico="<?php echo htmlspecialchars($ticket['tecnico_asignado'] ?? ''); ?>">
                        <!-- Header del ticket -->
                        <div class="ticket-header">
                            <div class="ticket-id">
                                <span class="id-label">#<?php echo htmlspecialchars($ticket['id']); ?></span>
                                <span class="priority priority-<?php echo htmlspecialchars($ticket['prioridad']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($ticket['prioridad'])); ?>
                                </span>
                            </div>
                            <div class="ticket-status" style="background-color: <?php echo htmlspecialchars($ticket['estado_color']); ?>;">
                                <?php echo htmlspecialchars($ticket['estado']); ?>
                            </div>
                        </div>

                        <!-- Contenido del ticket -->
                        <div class="ticket-content">
                            <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['titulo']); ?></h3>
                            <p class="ticket-description">
                                <?php echo htmlspecialchars(substr($ticket['descripcion'], 0, 120)); ?>
                                <?php if (strlen($ticket['descripcion']) > 120): ?>...<?php endif; ?>
                            </p>
                            
                            <div class="ticket-meta">
                                <div class="meta-item">
                                    <i class="bi bi-person"></i>
                                    <span>Creado por: <?php echo htmlspecialchars($ticket['usuario_creador'] ?? 'N/A'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-tag"></i>
                                    <span><?php echo htmlspecialchars($ticket['categoria'] ?? '-'); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span><?php echo !empty($ticket['fecha_creacion']) ? date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])) : '-'; ?></span>
                                </div>
                                
                                <?php if (!empty($ticket['tecnico_asignado'])): ?>
                                    <div class="meta-item assigned">
                                        <i class="bi bi-person-gear"></i>
                                        <span>Técnico: <?php echo htmlspecialchars($ticket['tecnico_asignado']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="meta-item no-assigned">
                                        <i class="bi bi-person-x"></i>
                                        <span>Sin técnico asignado</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ticket['total_comentarios'])): ?>
                                    <div class="meta-item comments">
                                        <i class="bi bi-chat-dots-fill"></i>
                                        <span><?php echo htmlspecialchars($ticket['total_comentarios']); ?> comentario(s)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones del administrador -->
                        <div class="admin-actions">
                            <div class="actions-left">
                                <?php if (empty($ticket['id_tecnico_asignado'])): ?>
                                    <button class="btn-assign" onclick="showAssignModal(<?php echo (int)$ticket['id']; ?>)">
                                        <i class="bi bi-person-plus"></i>
                                        Asignar Técnico
                                    </button>
                                <?php else: ?>
                                    <button class="btn-reassign" onclick="showAssignModal(<?php echo (int)$ticket['id']; ?>)">
                                        <i class="bi bi-arrow-repeat"></i>
                                        Reasignar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-tickets">
                    <div class="no-tickets-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3>No hay tickets en el sistema</h3>
                    <p>No se han creado tickets aún</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para asignar técnico -->
    <div id="assignModal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Asignar Técnico</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="assignForm" method="POST" action="../controlador/ContAsignarTecnico.php">
                <input type="hidden" id="ticketId" name="id_ticket">
                <div class="form-group">
                    <label for="tecnico">Seleccionar Técnico:</label>
                    <select name="id_tecnico" id="tecnico" required>
                        <option value="">Seleccione un técnico...</option>
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <option value="<?php echo (int)$tecnico['id']; ?>">
                                <?php echo htmlspecialchars($tecnico['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-confirm">Asignar</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        function viewTicket(ticketId) {
            window.location.href = 'ver-ticket.php?id=' + ticketId;
        }

        function showAssignModal(ticketId) {
            document.getElementById('ticketId').value = ticketId;
            document.getElementById('assignModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('assignModal').style.display = 'none';
        }

        // Función para confirmar eliminación de administrador
        function confirmDeleteAdmin(ticketId) {
            document.getElementById('ticketIdDeleteAdmin').value = ticketId;
            document.getElementById('deleteAdminModal').style.display = 'block';
        }

        function closeDeleteAdminModal() {
            document.getElementById('deleteAdminModal').style.display = 'none';
        }

        // Cerrar modal al hacer clic fuera
        window.onclick = function(event) {
            const assignModal = document.getElementById('assignModal');
            const deleteModal = document.getElementById('deleteAdminModal');
            
            if (event.target == assignModal) {
                assignModal.style.display = 'none';
            }
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        }

        // === SISTEMA DE FILTROS ===

        function aplicarFiltros() {
            const filtroEstado = document.getElementById('filtroEstado').value;
            const filtroPrioridad = document.getElementById('filtroPrioridad').value;
            const filtroAsignacion = document.getElementById('filtroAsignacion').value;
            const filtroTecnico = document.getElementById('filtroTecnico').value;

            const tickets = document.querySelectorAll('.ticket-card');
            let ticketsVisibles = 0;

            tickets.forEach(ticket => {
                let mostrar = true;

                // Filtro por estado
                if (filtroEstado !== 'todos') {
                    const estadoTicket = ticket.getAttribute('data-estado');
                    if (estadoTicket !== filtroEstado) {
                        mostrar = false;
                    }
                }

                // Filtro por prioridad
                if (filtroPrioridad !== 'todos') {
                    const prioridadTicket = ticket.getAttribute('data-priority');
                    if (prioridadTicket !== filtroPrioridad) {
                        mostrar = false;
                    }
                }

                // Filtro por asignación
                if (filtroAsignacion !== 'todos') {
                    const asignacionTicket = ticket.getAttribute('data-asignacion');
                    if (asignacionTicket !== filtroAsignacion) {
                        mostrar = false;
                    }
                }

                // Filtro por técnico
                if (filtroTecnico !== 'todos') {
                    const tecnicoTicket = ticket.getAttribute('data-tecnico');
                    if (tecnicoTicket !== filtroTecnico) {
                        mostrar = false;
                    }
                }

                // Mostrar u ocultar ticket con animación
                if (mostrar) {
                    ticket.style.display = 'block';
                    ticket.style.opacity = '0';
                    ticket.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        ticket.style.transition = 'all 0.3s ease';
                        ticket.style.opacity = '1';
                        ticket.style.transform = 'translateY(0)';
                    }, 50);
                    ticketsVisibles++;
                } else {
                    ticket.style.transition = 'all 0.3s ease';
                    ticket.style.opacity = '0';
                    ticket.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        ticket.style.display = 'none';
                    }, 300);
                }
            });

            // Actualizar contador de resultados
            actualizarContador(ticketsVisibles);
        }

        function limpiarFiltros() {
            // Resetear todos los filtros
            document.getElementById('filtroEstado').value = 'todos';
            document.getElementById('filtroPrioridad').value = 'todos';
            document.getElementById('filtroAsignacion').value = 'todos';
            document.getElementById('filtroTecnico').value = 'todos';

            // Mostrar todos los tickets
            const tickets = document.querySelectorAll('.ticket-card');
            tickets.forEach(ticket => {
                ticket.style.display = 'block';
                ticket.style.opacity = '1';
                ticket.style.transform = 'translateY(0)';
                ticket.style.transition = 'all 0.3s ease';
            });

            // Actualizar contador
            actualizarContador(tickets.length);
        }

        function actualizarContador(cantidad) {
            const contador = document.getElementById('contadorResultados');
            const total = document.querySelectorAll('.ticket-card').length;
            
            if (cantidad === total) {
                contador.textContent = `Mostrando todos los tickets (${total})`;
                contador.className = 'results-count';
            } else {
                contador.textContent = `Mostrando ${cantidad} de ${total} tickets`;
                contador.className = 'results-count filtered';
            }
        }

        // Inicializar contador al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const total = document.querySelectorAll('.ticket-card').length;
            actualizarContador(total);
        });

        // Atajos de teclado para filtros rápidos
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        document.getElementById('filtroAsignacion').value = 'sin-asignar';
                        aplicarFiltros();
                        break;
                    case '2':
                        e.preventDefault();
                        document.getElementById('filtroPrioridad').value = 'urgente';
                        aplicarFiltros();
                        break;
                    case '0':
                        e.preventDefault();
                        limpiarFiltros();
                        break;
                }
            }
        });
    </script>

    <style>
        /* Estilos para las acciones del administrador */
        .admin-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-top: 1px solid #eee;
            margin-top: 15px;
        }

        .actions-left, .actions-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Botón eliminar para admin */
        .btn-delete-admin {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-delete-admin:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        /* Modal de eliminación */
        .delete-modal {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .delete-modal .modal-header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .delete-modal .modal-body {
            padding: 25px;
        }

        .delete-modal .modal-body ul {
            margin: 15px 0;
            padding-left: 20px;
        }

        .delete-modal .modal-body li {
            margin-bottom: 8px;
        }

        .warning-note {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-top: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
    </style>
</body>
</html>