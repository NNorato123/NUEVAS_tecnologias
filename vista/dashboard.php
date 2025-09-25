<?php
session_start();

// Procesar cierre de sesión ANTES de cualquier otra verificación
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    header('Location: ../login.php');
    exit();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit();
}

$id_rol = $_SESSION['id_rol'] ?? 1; // 1=cliente, 2=tecnico, 3=administrador

// Obtener nombre del rol
include '../config/Conexion.php';
$stmt = $conexion->prepare("SELECT nombre FROM roles WHERE id = ?");
$stmt->bind_param("i", $id_rol);
$stmt->execute();
$result = $stmt->get_result();
$nombre_rol = $result->fetch_assoc()['nombre'] ?? 'Cliente';

// Obtener mensaje de éxito si existe
$mensaje = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Mesa de Ayuda - NuevasTecnologias</title>
    <link rel="stylesheet" href="../css/dashboard/inicio.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <header class="header">
        <div class="user-info">
            <div>
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                <div class="user-role">Rol: <?php echo ucfirst($nombre_rol); ?></div>
            </div>
        </div>
        <h1>Mesa de Ayuda</h1>
        <a href="#" class="logout-btn" id="logoutBtn" title="Cerrar Sesión">
            <i class="bi bi-door-open"></i>
        </a>
    </header>

    <div class="container">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <?php if ($id_rol == 1): // Cliente 
            ?>
                <!-- Opciones para cliente -->
                <div class="card new-ticket-card">
                    <h3>📝 Crear Nuevo Ticket</h3>
                    <p>¿Tienes un problema o solicitud? Haz clic para crear un nuevo ticket de soporte.</p>
                    <button onclick="window.location.href='crear-ticket.php'">Crear Ticket</button>
                </div>

                <div class="card my-tickets-card">
                    <h3>🎫 Mis Tickets</h3>
                    <p>Consulta el estado de tus solicitudes y obten informacion sobre tus tickets abiertos.</p>
                    <button onclick="window.location.href='mis-tickets.php'">Ver Mis Tickets</button>
                </div>
            <?php elseif ($id_rol == 2): // Técnico 
            ?>
                <!-- Opciones para técnico -->
                 <div class="card assigned-tickets-card">
                <h3>🎟️ Tickets Asignados</h3>
                <p>Revisa los tickets que tienes asignados para su resolución.</p>
                <button onclick="window.location.href='tecnico-tickets.php'">Ver Tickets Asignados</button>
                </div>

                <div class="card history-tickets-card">
                    <h3>📜 Historial de Tickets</h3>
                    <p>Consulta el historial de tickets que has atendido.</p>
                    <button onclick="window.location.href='tecnico-ticket-detalle.php'">Ver Historial</button>
                </div>

            <?php elseif ($id_rol == 3): // Administrador 
            ?>
                <!-- Opciones para administrador -->
                <div class="card user-management-card">
                    <h3>👥 Gestión de Usuarios</h3>
                    <p>Administración completa de los usuarios del sistema y gestión de roles.</p>
                    <button onclick="window.location.href='admin-usuarios.php'">Gestionar Usuarios</button>
                </div>

                <div class="card all-tickets-card">
                    <h3>📂 Todos los Tickets</h3>
                    <p>Visualiza y asigna técnicos por filtros de todos los tickets generados en el sistema.</p>
                    <button onclick="window.location.href='admin-tickets.php'">Ver Todos los Tickets</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Información adicional según el rol del usuario -->
        <?php 
        $info_file = '';
        switch($id_rol) {
            case 1: // Cliente
                $info_file = 'includes/info-cliente.php';
                break;
            case 2: // Técnico
                $info_file = 'includes/info-tecnico.php';
                break;
            case 3: // Administrador
                $info_file = 'includes/info-administrador.php';
                break;
        }
        
        if ($info_file && file_exists($info_file)) {
            include $info_file;
        }
        ?>
    </div>

    <footer>
        <p>Mesa de Ayuda &copy; <?php echo date('Y'); ?> - NuevasTecnologias</p>
    </footer>

    <!-- Modal personalizado para cerrar sesión -->
    <div id="logoutModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Cerrar Sesión</h3>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas cerrar tu sesión?</p>
            </div>
            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeLogoutModal()">Cancelar</button>
                <button class="btn-confirm" onclick="confirmLogout()">Cerrar Sesión</button>
            </div>
        </div>
    </div>

    <!-- Modal de éxito (alternativa más elegante) -->
    <?php if (!empty($mensaje)): ?>
    <div id="successModal" class="modal" style="display: flex;">
        <div class="modal-content success-modal">
            <div class="modal-header success-header">
                <i class="bi bi-check-circle-fill"></i>
                <h3>¡Éxito!</h3>
            </div>
            <div class="modal-body">
                <p><?php echo htmlspecialchars($mensaje); ?></p>
            </div>
            <div class="modal-footer">
                <button class="btn-success" onclick="closeSuccessModal()">Entendido</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openLogoutModal() {
            document.getElementById('logoutModal').style.display = 'flex';
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').style.display = 'none';
        }

        function confirmLogout() {
            window.location.href = '?logout=1';
        }

        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            openLogoutModal();
        });

        // Cerrar modal al hacer clic fuera de él
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('logoutModal');
            if (e.target === modal) {
                closeLogoutModal();
            }
        });

        // Script para el modal de éxito
        function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            // Limpiar la URL
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, document.title, url);
        }

        // Auto-cerrar el modal después de 5 segundos
        setTimeout(function() {
            const modal = document.getElementById('successModal');
            if (modal) {
                closeSuccessModal();
            }
        }, 3000);
    </script>
</body>

</html>