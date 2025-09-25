<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

// Obtener categorías
include '../config/Conexion.php';
$categorias = $conexion->query("SELECT * FROM categorias ORDER BY nombre");

$mensaje = $_GET['error'] ?? ($_GET['success'] ?? '');
$tipo_mensaje = isset($_GET['error']) ? 'error' : (isset($_GET['success']) ? 'success' : '');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Crear Ticket - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/crear-ticket.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <header class="header">
        <a href="dashboard.php" class="back-btn" title="Volver al Dashboard">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Crear Nuevo Ticket</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="../controlador/ContCrearTicket.php" class="ticket-form">
            <div class="form-group">
                <label for="titulo">Título del Problema *</label>
                <input type="text" id="titulo" name="titulo" required
                    placeholder="Describe brevemente tu problema">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="categoria">Categoría *</label>
                    <select id="categoria" name="categoria" required>
                        <option value="">Selecciona una categoría</option>
                        <?php while ($cat = $categorias->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                title="<?php echo htmlspecialchars($cat['descripcion']); ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?> - <?php echo htmlspecialchars($cat['descripcion']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="prioridad">Prioridad *</label>
                    <select id="prioridad" name="prioridad" required>
                        <option value="baja">Baja</option>
                        <option value="media" selected>Media</option>
                        <option value="alta">Alta</option>
                        <option value="urgente">Urgente</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción Detallada *</label>
                <textarea id="descripcion" name="descripcion" required
                    placeholder="Describe detalladamente el problema que estás experimentando..."></textarea>
            </div>

            <div class="form-actions">
                <button type="button" onclick="window.location.href='dashboard.php'" class="btn-cancel">
                    Cancelar
                </button>
                <button type="submit" class="btn-submit">
                    <i class="bi bi-plus-circle"></i>
                    Crear Ticket
                </button>
            </div>
        </form>
    </div>
</body>

</html>