<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

// Conexión
include '../config/Conexion.php';

// Obtener tickets del usuario
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
        u_tecnico.nombre as tecnico_asignado,
        COUNT(ct.id) as total_comentarios
    FROM tickets t
    LEFT JOIN categorias c ON t.id_categoria = c.id
    LEFT JOIN estados_ticket e ON t.id_estado = e.id
    LEFT JOIN usuarios u_tecnico ON t.id_tecnico_asignado = u_tecnico.id
    LEFT JOIN comentarios_ticket ct ON t.id = ct.id_ticket
    WHERE t.id_usuario_creador = ?
    GROUP BY t.id
    ORDER BY t.fecha_creacion DESC
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result = $stmt->get_result();

// Obtener comentarios
$comentarios_query = "
    SELECT 
        ct.id_ticket,
        ct.comentario,
        ct.fecha_comentario,
        u.nombre as autor,
        u.id_rol
    FROM comentarios_ticket ct
    INNER JOIN usuarios u ON ct.id_usuario = u.id
    INNER JOIN tickets t ON ct.id_ticket = t.id
    WHERE t.id_usuario_creador = ?
    ORDER BY ct.fecha_comentario ASC
";

$comentarios_stmt = $conexion->prepare($comentarios_query);
$comentarios_stmt->bind_param("i", $id_usuario);
$comentarios_stmt->execute();
$comentarios_result = $comentarios_stmt->get_result();

$comentarios_por_ticket = [];
while ($comentario = $comentarios_result->fetch_assoc()) {
    $comentarios_por_ticket[$comentario['id_ticket']][] = $comentario;
}

// Obtener técnicos disponibles
$tecnicos_query = "SELECT id, nombre FROM usuarios WHERE id_rol = 2";
$tecnicos_result = $conexion->query($tecnicos_query);
$tecnicos_disponibles = [];
while ($row = $tecnicos_result->fetch_assoc()) {
    $tecnicos_disponibles[] = $row;
}

$mensaje = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mis Tickets - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/crear-ticket.css">
    <link rel="stylesheet" href="../css/dashboard/mis-tickets.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body>
    <header class="header">
        <a href="dashboard.php" class="back-btn" title="Volver al Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Mis Tickets</h1>
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

        <div class="tickets-header">
            <div class="tickets-count">
                <h2>Mis Solicitudes de Soporte</h2>
                <span class="count-badge"><?php echo $result->num_rows; ?> ticket(s)</span>
            </div>
            <a href="crear-ticket.php" class="btn-new-ticket">
                <i class="bi bi-plus-circle"></i>
                Nuevo Ticket
            </a>
        </div>

        <div class="tickets-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($ticket = $result->fetch_assoc()): ?>
                    <div class="ticket-card" data-priority="<?php echo $ticket['prioridad']; ?>">
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

                        <div class="ticket-content">
                            <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['titulo']); ?></h3>
                            <p class="ticket-description">
                                <?php echo htmlspecialchars(substr($ticket['descripcion'], 0, 150)); ?>
                                <?php if (strlen($ticket['descripcion']) > 150): ?>...<?php endif; ?>
                            </p>

                            <div class="ticket-meta">
                                <div class="meta-item">
                                    <i class="bi bi-tag"></i>
                                    <span><?php echo $ticket['categoria']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></span>
                                </div>

                                <?php if ($ticket['tecnico_asignado']): ?>
                                    <div class="meta-item">
                                        <i class="bi bi-person-gear"></i>
                                        <span>Técnico: <?php echo htmlspecialchars($ticket['tecnico_asignado']); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="meta-item no-assigned">
                                        <i class="bi bi-person-x"></i>
                                        <span>Sin técnico asignado</span>
                                        <form method="POST" action="../controlador/ContAsignarTecnico.php" class="assign-form">
                                        <input type="hidden" name="id_ticket" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="redirect" value="mis-tickets">
                                        <select name="id_tecnico" required>
                                            <option value="">Selecciona un técnico</option>
                                            <?php foreach ($tecnicos_disponibles as $tecnico): ?>
                                                <option value="<?php echo $tecnico['id']; ?>">
                                                    <?php echo htmlspecialchars($tecnico['nombre']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn-assign">Asignar</button>
                                    </form>
                                    </div>
                                <?php endif; ?>

                                <!-- Botón de eliminar SIEMPRE visible -->
                                <form method="POST" action="../controlador/ContEliminarTicket.php" 
                                    onsubmit="return confirm('¿Seguro que deseas eliminar el ticket #<?php echo $ticket['id']; ?>?');" 
                                    class="delete-form">
                                    <input type="hidden" name="id_ticket" value="<?php echo $ticket['id']; ?>">
                                    <input type="hidden" name="redirect" value="mis-tickets">
                                    <button type="submit" class="btn-delete">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>

                                <?php if ($ticket['total_comentarios'] > 0): ?>
                                    <div class="meta-item comments">
                                        <i class="bi bi-chat-dots-fill"></i>
                                        <span><?php echo $ticket['total_comentarios']; ?> respuesta(s)</span>
                                    </div>
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
                    <h3>No tienes tickets creados</h3>
                    <p>¿Necesitas ayuda? Crea tu primer ticket de soporte</p>
                    <a href="crear-ticket.php" class="btn-create-first">
                        <i class="bi bi-plus-circle"></i>
                        Crear Mi Primer Ticket
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
