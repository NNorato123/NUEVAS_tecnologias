<?php
session_start();

// Verificar si el usuario está logueado y es técnico
if (!isset($_SESSION['usuario']) || $_SESSION['id_rol'] != 2) {
    header('Location: ../login.php');
    exit();
}

$id_tecnico = $_SESSION['id_usuario'];

// Conexión
include '../config/Conexion.php';

// Consulta de tickets asignados
$query = "
    SELECT 
        t.id,
        t.titulo,
        t.descripcion,
        t.prioridad,
        t.fecha_creacion,
        t.fecha_resolucion,
        c.nombre AS categoria,
        e.nombre AS estado,
        e.color AS estado_color,
        u_creador.nombre AS usuario_creador,
        u_creador.email AS usuario_email,
        COUNT(ct.id) AS total_comentarios
    FROM tickets t
    LEFT JOIN categorias c ON t.id_categoria = c.id
    LEFT JOIN estados_ticket e ON t.id_estado = e.id
    LEFT JOIN usuarios u_creador ON t.id_usuario_creador = u_creador.id
        LEFT JOIN comentarios_ticket ct ON t.id = ct.id_ticket
        WHERE t.id_tecnico_asignado = ?
            AND t.id_estado <> 5
    GROUP BY t.id
    ORDER BY 
        CASE t.prioridad 
            WHEN 'urgente' THEN 1 
            WHEN 'alta' THEN 2 
            WHEN 'media' THEN 3 
            WHEN 'baja' THEN 4 
        END,
        t.fecha_creacion ASC
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_tecnico);
$stmt->execute();
$result = $stmt->get_result();

// Mensajes
$mensaje = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Tickets Asignados - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/tecnico-tickets.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="dashboard.php" class="back-btn" title="Volver al Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Mis Tickets Asignados</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        </div>
    </header>

    <div class="container">
        <!-- Alertas -->
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

        <!-- Panel de estadísticas -->
        <div class="tecnico-header">
            <h2>Panel del Técnico</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <i class="bi bi-ticket-perforated"></i>
                    <div>
                        <span class="number"><?php echo $result->num_rows; ?></span>
                        <span class="label">Tickets Asignados</span>
                    </div>
                </div>
                <div class="stat-card urgent">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                        <?php
                        $urgent_query = "SELECT COUNT(*) AS count FROM tickets WHERE id_tecnico_asignado = ? AND prioridad = 'urgente' AND id_estado <> 5";
                        $urgent_stmt = $conexion->prepare($urgent_query);
                        $urgent_stmt->bind_param("i", $id_tecnico);
                        $urgent_stmt->execute();
                        $urgent_count = $urgent_stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <span class="number"><?php echo $urgent_count; ?></span>
                        <span class="label">Urgentes</span>
                    </div>
                </div>
                <div class="stat-card resolved">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>
                        <?php
                        $resolved_query = "SELECT COUNT(*) AS count FROM tickets WHERE id_tecnico_asignado = ? AND id_estado = 5";
                        $resolved_stmt = $conexion->prepare($resolved_query);
                        $resolved_stmt->bind_param("i", $id_tecnico);
                        $resolved_stmt->execute();
                        $resolved_count = $resolved_stmt->get_result()->fetch_assoc()['count'];
                        ?>
                        <span class="number"><?php echo $resolved_count; ?></span>
                        <span class="label">Cerrados</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tickets -->
        <div class="tickets-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($ticket = $result->fetch_assoc()): ?>
                    <div class="ticket-card tecnico-card" data-priority="<?php echo $ticket['prioridad']; ?>">
                        <!-- Header del ticket -->
                        <div class="ticket-header">
                            <div class="ticket-id">
                                <span class="id-label">#<?php echo $ticket['id']; ?></span>
                                <span class="priority priority-<?php echo $ticket['prioridad']; ?>">
                                    <?php echo ucfirst($ticket['prioridad']); ?>
                                </span>
                            </div>
                            <div class="ticket-status" style="background-color: <?php echo $ticket['estado_color']; ?>;">
                                <?php echo $ticket['estado']; ?>
                            </div>
                        </div>

                        <!-- Contenido -->
                        <div class="ticket-content">
                            <h3><?php echo htmlspecialchars($ticket['titulo']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($ticket['descripcion'], 0, 150)); ?><?php if (strlen($ticket['descripcion']) > 150) echo "..."; ?></p>
                            
                            <div class="ticket-meta">
                                <div><i class="bi bi-person"></i> Cliente: <?php echo htmlspecialchars($ticket['usuario_creador']); ?></div>
                                <div><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($ticket['usuario_email']); ?></div>
                                <div><i class="bi bi-tag"></i> <?php echo $ticket['categoria']; ?></div>
                                <div><i class="bi bi-calendar3"></i> Creado: <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></div>
                                <?php if ($ticket['total_comentarios'] > 0): ?>
                                    <div><i class="bi bi-chat-dots-fill"></i> <?php echo $ticket['total_comentarios']; ?> comentario(s)</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="ticket-actions">
                            <?php if ($ticket['estado'] === 'Cerrado'): ?>
                                <!-- Ticket cerrado -->
                                <button class="btn-closed" disabled>
                                    <i class="bi bi-check-circle-fill"></i> Cerrado
                                </button>
                            <?php elseif ($ticket['estado'] === 'Seguimiento'): ?>
                                <!-- Ticket en seguimiento -->
                                <button class="btn-tracking" disabled>
                                    <i class="bi bi-eye-fill"></i> En Seguimiento
                                </button>
                                <button class="btn-respond" onclick="toggleResponderForm(<?php echo $ticket['id']; ?>)">
                                    <i class="bi bi-chat-left-text"></i> Comentar
                                </button>
                            <?php elseif ($ticket['estado'] === 'En Proceso'): ?>
                                <!-- Ticket en proceso: puede comentar y cambiar estado -->
                                <button class="btn-respond" onclick="toggleResponderForm(<?php echo $ticket['id']; ?>)">
                                    <i class="bi bi-chat-left-text"></i> Comentar
                                </button>
                                <button class="btn-tracking" onclick="cambiarEstado(<?php echo $ticket['id']; ?>, 4)">
                                    <i class="bi bi-eye"></i> A Seguimiento
                                </button>
                                <button class="btn-close" onclick="cambiarEstado(<?php echo $ticket['id']; ?>, 5)">
                                    <i class="bi bi-check-circle"></i> Cerrar
                                </button>
                            <?php else: ?>
                                <!-- Estados: Abierto, Asignado - solo puede responder -->
                                <button class="btn-respond" onclick="toggleResponderForm(<?php echo $ticket['id']; ?>)">
                                    <i class="bi bi-chat-left-text"></i> Responder
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Formulario Respuesta -->
                        <div id="responder-form-<?php echo $ticket['id']; ?>" class="responder-form" style="display:none;">
                            <form method="POST" action="../controlador/responder_ticket.php">
                                <input type="hidden" name="id_ticket" value="<?php echo $ticket['id']; ?>">
                                <textarea name="comentario" required placeholder="Escribe tu respuesta..." rows="3"></textarea>
                                <button type="submit" class="btn-save">
                                    <i class="bi bi-send"></i> Enviar Respuesta
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-tickets">
                    <i class="bi bi-inbox"></i>
                    <h3>No tienes tickets asignados</h3>
                    <p>Cuando te asignen tickets aparecerán aquí</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de confirmación personalizado -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <i class="bi bi-exclamation-triangle-fill modal-icon"></i>
                <h3 id="modalTitle">Confirmar Acción</h3>
            </div>
            <div class="modal-body">
                <p id="modalMessage">¿Estás seguro de realizar esta acción?</p>
            </div>
            <div class="modal-footer">
                <button id="modalCancel" class="btn-cancel">
                    <i class="bi bi-x-circle"></i> Cancelar
                </button>
                <button id="modalConfirm" class="btn-confirm">
                    <i class="bi bi-check-circle"></i> Confirmar
                </button>
            </div>
        </div>
    </div>


    <script>
    // Variables globales para el modal
    let pendingAction = null;

    function toggleResponderForm(ticketId) {
        const form = document.getElementById("responder-form-" + ticketId);
        form.style.display = (form.style.display === "none") ? "block" : "none";
    }

    function cambiarEstado(ticketId, nuevoEstado) {
        const estados = {
            4: 'Seguimiento',
            5: 'Cerrado'
        };
        
        const estadoNombre = estados[nuevoEstado];
        const mensajes = {
            4: `¿Deseas mover este ticket a <strong>Seguimiento</strong>?<br><small>El ticket seguirá activo pero en modo de seguimiento.</small>`,
            5: `¿Deseas <strong>CERRAR</strong> este ticket?<br><small>Esta acción marcará el ticket como completado.</small>`
        };

        // Configurar el modal
        const modal = document.getElementById('confirmModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalMessage = document.getElementById('modalMessage');
        const modalConfirm = document.getElementById('modalConfirm');
        const modalCancel = document.getElementById('modalCancel');

        modalTitle.textContent = `Cambiar Estado a ${estadoNombre}`;
        modalMessage.innerHTML = mensajes[nuevoEstado];
        
        // Configurar botón de confirmación
        modalConfirm.className = nuevoEstado === 4 ? 'btn-confirm seguimiento' : 'btn-confirm';
        modalConfirm.innerHTML = nuevoEstado === 4 ? 
            '<i class="bi bi-eye"></i> A Seguimiento' : 
            '<i class="bi bi-check-circle"></i> Cerrar Ticket';

        // Guardar la acción pendiente
        pendingAction = () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '../controlador/responder_ticket.php';
            
            const inputTicket = document.createElement('input');
            inputTicket.type = 'hidden';
            inputTicket.name = 'id_ticket';
            inputTicket.value = ticketId;
            
            const inputEstado = document.createElement('input');
            inputEstado.type = 'hidden';
            inputEstado.name = 'nuevo_estado';
            inputEstado.value = nuevoEstado;
            
            const inputCambiar = document.createElement('input');
            inputCambiar.type = 'hidden';
            inputCambiar.name = 'cambiar_estado';
            inputCambiar.value = '1';
            
            form.appendChild(inputTicket);
            form.appendChild(inputEstado);
            form.appendChild(inputCambiar);
            
            document.body.appendChild(form);
            form.submit();
        };

        // Mostrar modal
        showModal();
    }

    function showModal() {
        const modal = document.getElementById('confirmModal');
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    function hideModal() {
        const modal = document.getElementById('confirmModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            pendingAction = null;
        }, 300);
    }

    // Event listeners para el modal
    document.addEventListener('DOMContentLoaded', function() {
        const modalConfirm = document.getElementById('modalConfirm');
        const modalCancel = document.getElementById('modalCancel');
        const modal = document.getElementById('confirmModal');

        modalConfirm.addEventListener('click', function() {
            if (pendingAction) {
                hideModal();
                setTimeout(pendingAction, 300); // Ejecutar después de que se cierre el modal
            }
        });

        modalCancel.addEventListener('click', hideModal);

        // Cerrar modal al hacer clic fuera de él
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideModal();
            }
        });

        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                hideModal();
            }
        });
    });
    </script>
</body>
</html>
