<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el usuario esté logueado y sea técnico
    if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'tecnico') {
        header('Location: ../login.php');
        exit();
    }

    $id_ticket = $_POST['id_ticket'] ?? '';
    $comentario = trim($_POST['comentario'] ?? '');
    $estado = $_POST['estado'] ?? '';
    $id_tecnico = $_SESSION['id_usuario'];

    // Validaciones
    if (empty($id_ticket) || empty($comentario) || empty($estado)) {
        $redirect = '../vista/tecnico-ticket-detalle.php?id=' . $id_ticket . '&error=Todos los campos son obligatorios';
        header('Location: ' . $redirect);
        exit();
    }

    if (strlen($comentario) < 10) {
        $redirect = '../vista/tecnico-ticket-detalle.php?id=' . $id_ticket . '&error=El comentario debe tener al menos 10 caracteres';
        header('Location: ' . $redirect);
        exit();
    }

    // Verificar que el ticket existe y está asignado al técnico
    $stmt = $conexion->prepare("SELECT id FROM tickets WHERE id = ? AND id_tecnico_asignado = ?");
    $stmt->bind_param("ii", $id_ticket, $id_tecnico);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: ../vista/tecnico-tickets.php?error=Ticket no encontrado o no asignado');
        exit();
    }

    // Verificar que el estado es válido
    $stmt = $conexion->prepare("SELECT id FROM estados_ticket WHERE id = ?");
    $stmt->bind_param("i", $estado);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $redirect = '../vista/tecnico-ticket-detalle.php?id=' . $id_ticket . '&error=Estado no válido';
        header('Location: ' . $redirect);
        exit();
    }

    // Iniciar transacción
    $conexion->begin_transaction();

    try {
        // Insertar comentario
        $stmt = $conexion->prepare("INSERT INTO comentarios_ticket (id_ticket, id_usuario, comentario, fecha_comentario) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $id_ticket, $id_tecnico, $comentario);
        $stmt->execute();

        // Actualizar estado del ticket
        $fecha_resolucion = ($estado == 4) ? "NOW()" : "NULL";
        $update_query = "UPDATE tickets SET id_estado = ?, fecha_actualizacion = NOW()";
        
        if ($estado == 4) {
            $update_query .= ", fecha_resolucion = NOW()";
        }
        
        $update_query .= " WHERE id = ?";
        
        $stmt = $conexion->prepare($update_query);
        $stmt->bind_param("ii", $estado, $id_ticket);
        $stmt->execute();

        // Confirmar transacción
        $conexion->commit();

        $redirect = '../vista/tecnico-tickets.php?success=Comentario añadido y ticket actualizado';
        header('Location: ' . $redirect);
        
    } catch (Exception $e) {
        // Revertir transacción
        $conexion->rollback();
        $redirect = '../vista/tecnico-ticket-detalle.php?id=' . $id_ticket . '&error=Error al procesar: ' . $e->getMessage();
        header('Location: ' . $redirect);
    }
    exit();
}
?>