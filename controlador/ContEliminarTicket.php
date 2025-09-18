<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si el usuario está logueado
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ../login.php');
        exit();
    }

    $id_ticket = intval($_POST['id_ticket'] ?? 0);
    $id_usuario = $_SESSION['id_usuario'];
    $id_rol = $_SESSION['id_rol'];

    if ($id_ticket <= 0) {
        $redirect = ($id_rol == 3) ? '../vista/admin-tickets.php' : '../vista/mis-tickets.php';
        header('Location: ' . $redirect . '?error=ID de ticket no válido');
        exit();
    }

    // Verificar que el ticket existe
    $check_query = "SELECT id, id_usuario_creador, id_tecnico_asignado, id_estado FROM tickets WHERE id = ?";
    $check_stmt = $conexion->prepare($check_query);
    $check_stmt->bind_param("i", $id_ticket);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows == 0) {
        $redirect = ($id_rol == 3) ? '../vista/admin-tickets.php' : '../vista/mis-tickets.php';
        header('Location: ' . $redirect . '?error=El ticket no existe');
        exit();
    }

    $ticket = $result->fetch_assoc();

    // Verificar permisos según el rol
    if ($id_rol == 1) { // Cliente
        // Cliente solo puede eliminar sus propios tickets que NO estén asignados
        if ($ticket['id_usuario_creador'] != $id_usuario) {
            header('Location: ../vista/mis-tickets.php?error=No tienes permisos para eliminar este ticket');
            exit();
        }
        
        if (!empty($ticket['id_tecnico_asignado'])) {
            header('Location: ../vista/mis-tickets.php?error=No puedes eliminar un ticket que ya está asignado a un técnico');
            exit();
        }
    } elseif ($id_rol == 3) { // Administrador
        // Administrador puede eliminar cualquier ticket
    } else {
        // Técnicos no pueden eliminar tickets
        header('Location: ../vista/dashboard.php?error=No tienes permisos para eliminar tickets');
        exit();
    }

    // Comenzar transacción para eliminar todo lo relacionado
    $conexion->begin_transaction();

    try {
        // 1. Eliminar comentarios del ticket
        $delete_comments = $conexion->prepare("DELETE FROM comentarios_ticket WHERE id_ticket = ?");
        $delete_comments->bind_param("i", $id_ticket);
        $delete_comments->execute();

        // 2. Eliminar el ticket
        $delete_ticket = $conexion->prepare("DELETE FROM tickets WHERE id = ?");
        $delete_ticket->bind_param("i", $id_ticket);
        $delete_ticket->execute();

        // Confirmar transacción
        $conexion->commit();

        // Redireccionar según el rol
        if ($id_rol == 3) {
            header('Location: ../vista/admin-tickets.php?success=Ticket #' . $id_ticket . ' eliminado correctamente');
        } else {
            header('Location: ../vista/mis-tickets.php?success=Tu ticket #' . $id_ticket . ' ha sido eliminado');
        }

    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conexion->rollback();
        
        $redirect = ($id_rol == 3) ? '../vista/admin-tickets.php' : '../vista/mis-tickets.php';
        header('Location: ' . $redirect . '?error=Error al eliminar el ticket: ' . $e->getMessage());
    }

} else {
    // Método no permitido
    header('Location: ../vista/dashboard.php');
}
exit();
?>