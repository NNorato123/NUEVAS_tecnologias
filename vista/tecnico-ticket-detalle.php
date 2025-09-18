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

// Obtener SOLO tickets cerrados del técnico
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
        u_creador.email as usuario_email,
        COUNT(ct.id) as total_comentarios
    FROM tickets t
    LEFT JOIN categorias c ON t.id_categoria = c.id
    LEFT JOIN estados_ticket e ON t.id_estado = e.id
    LEFT JOIN usuarios u_creador ON t.id_usuario_creador = u_creador.id
    LEFT JOIN comentarios_ticket ct ON t.id = ct.id_ticket
    WHERE t.id_tecnico_asignado = ? AND t.id_estado = 5
    GROUP BY t.id
    ORDER BY t.fecha_resolucion DESC
";

$stmt = $conexion->prepare($query);
$stmt->bind_param("i", $id_tecnico);
$stmt->execute();
$result = $stmt->get_result();

$mensaje = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Tickets - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/tecnico-tickets.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <header class="header">
        <a href="dashboard.php" class="back-btn" title="Volver al Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Historial de Tickets</h1>
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

        <div class="tickets-container">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($ticket = $result->fetch_assoc()): ?>
                    <div class="ticket-card tecnico-card" data-priority="<?php echo $ticket['prioridad']; ?>">
                        <!-- Header -->
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
                            <h3 class="ticket-title"><?php echo htmlspecialchars($ticket['titulo']); ?></h3>
                            <p class="ticket-description">
                                <?php echo htmlspecialchars(substr($ticket['descripcion'], 0, 150)); ?>
                                <?php if (strlen($ticket['descripcion']) > 150): ?>...<?php endif; ?>
                            </p>
                            
                            <div class="ticket-meta">
                                <div class="meta-item user-info">
                                    <i class="bi bi-person"></i>
                                    <span>Cliente: <?php echo htmlspecialchars($ticket['usuario_creador']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-envelope"></i>
                                    <span><?php echo htmlspecialchars($ticket['usuario_email']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-tag"></i>
                                    <span><?php echo $ticket['categoria']; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="bi bi-calendar3"></i>
                                    <span>Creado: <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></span>
                                </div>
                                <div class="meta-item resolved-info">
                                    <i class="bi bi-check-circle-fill"></i>
                                    <span>Resuelto el <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_resolucion'])); ?></span>
                                </div>

                                <?php if ($ticket['total_comentarios'] > 0): ?>
                                    <div class="meta-item comments">
                                        <i class="bi bi-chat-dots-fill"></i>
                                        <span><?php echo $ticket['total_comentarios']; ?> comentario(s)</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones -->
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-tickets">
                    <div class="no-tickets-icon">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <h3>No tienes tickets resueltos</h3>
                    <p>Aquí aparecerán los tickets que hayas cerrado</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewTicketDetail(ticketId) {
            window.location.href = 'tecnico-ticket-detalle.php?id=' + ticketId;
        }
    </script>
</body>
</html>
