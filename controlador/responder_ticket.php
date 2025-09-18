<?php
session_start();
include '../config/Conexion.php';

// Verificar que sea técnico y esté logueado
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 2) {
    header('Location: ../login.php');
    exit();
}

$id_tecnico = $_SESSION['id_usuario'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Caso 1: Agregar comentario al ticket
    if (isset($_POST['comentario']) && isset($_POST['id_ticket'])) {
        $id_ticket = $_POST['id_ticket'];
        $comentario = trim($_POST['comentario']);
        
        if (!empty($comentario)) {
            try {
                $conexion->begin_transaction();
                
                // Verificar que el ticket esté asignado al técnico
                $verificar_query = "SELECT id_estado FROM tickets WHERE id = ? AND id_tecnico_asignado = ?";
                $verificar_stmt = $conexion->prepare($verificar_query);
                $verificar_stmt->bind_param("ii", $id_ticket, $id_tecnico);
                $verificar_stmt->execute();
                $resultado = $verificar_stmt->get_result();
                
                if ($resultado->num_rows > 0) {
                    $ticket_data = $resultado->fetch_assoc();
                    
                    // Insertar comentario
                    $comentario_query = "INSERT INTO comentarios_ticket (id_ticket, id_usuario, comentario) VALUES (?, ?, ?)";
                    $comentario_stmt = $conexion->prepare($comentario_query);
                    $comentario_stmt->bind_param("iis", $id_ticket, $id_tecnico, $comentario);
                    $comentario_stmt->execute();
                    
                    // Si es el primer comentario del técnico y el estado no es "En Proceso", cambiarlo
                    if ($ticket_data['id_estado'] != 3) {
                        $update_estado_query = "UPDATE tickets SET id_estado = 3, fecha_actualizacion = NOW() WHERE id = ?";
                        $update_estado_stmt = $conexion->prepare($update_estado_query);
                        $update_estado_stmt->bind_param("i", $id_ticket);
                        $update_estado_stmt->execute();
                    }
                    
                    $conexion->commit();
                    header('Location: ../vista/tecnico-tickets.php?success=Comentario agregado correctamente');
                    exit();
                } else {
                    throw new Exception("Ticket no encontrado o no asignado");
                }
                
            } catch (Exception $e) {
                $conexion->rollback();
                header('Location: ../vista/tecnico-tickets.php?error=Error al agregar comentario');
                exit();
            }
        } else {
            header('Location: ../vista/tecnico-tickets.php?error=El comentario no puede estar vacío');
            exit();
        }
    }
    
    // Caso 2: Cambiar estado del ticket (Seguimiento o Cerrado)
    if (isset($_POST['cambiar_estado']) && isset($_POST['id_ticket']) && isset($_POST['nuevo_estado'])) {
        $id_ticket = $_POST['id_ticket'];
        $nuevo_estado = $_POST['nuevo_estado'];
        
        // Validar que el nuevo estado sea válido (4 = Seguimiento, 5 = Cerrado)
        if (in_array($nuevo_estado, [4, 5])) {
            try {
                $conexion->begin_transaction();
                
                // Verificar que el ticket esté asignado al técnico y en estado "En Proceso"
                $verificar_query = "SELECT id_estado FROM tickets WHERE id = ? AND id_tecnico_asignado = ? AND id_estado = 3";
                $verificar_stmt = $conexion->prepare($verificar_query);
                $verificar_stmt->bind_param("ii", $id_ticket, $id_tecnico);
                $verificar_stmt->execute();
                $resultado = $verificar_stmt->get_result();
                
                if ($resultado->num_rows > 0) {
                    // Actualizar estado del ticket
                    $fecha_resolucion = ($nuevo_estado == 5) ? "NOW()" : "NULL";
                    $update_query = "UPDATE tickets SET id_estado = ?, fecha_actualizacion = NOW()";
                    
                    if ($nuevo_estado == 5) {
                        $update_query .= ", fecha_resolucion = NOW()";
                    }
                    
                    $update_query .= " WHERE id = ?";
                    $update_stmt = $conexion->prepare($update_query);
                    $update_stmt->bind_param("ii", $nuevo_estado, $id_ticket);
                    $update_stmt->execute();
                    
                    // Agregar comentario automático del cambio de estado
                    $mensaje_estado = ($nuevo_estado == 4) ? "Ticket movido a Seguimiento" : "Ticket cerrado por el técnico";
                    $comentario_auto_query = "INSERT INTO comentarios_ticket (id_ticket, id_usuario, comentario) VALUES (?, ?, ?)";
                    $comentario_auto_stmt = $conexion->prepare($comentario_auto_query);
                    $comentario_auto_stmt->bind_param("iis", $id_ticket, $id_tecnico, $mensaje_estado);
                    $comentario_auto_stmt->execute();
                    
                    $conexion->commit();
                    
                    $mensaje_success = ($nuevo_estado == 4) ? "Ticket movido a Seguimiento" : "Ticket cerrado correctamente";
                    header("Location: ../vista/tecnico-tickets.php?success=$mensaje_success");
                    exit();
                } else {
                    throw new Exception("Ticket no encontrado, no asignado o no está en proceso");
                }
                
            } catch (Exception $e) {
                $conexion->rollback();
                header('Location: ../vista/tecnico-tickets.php?error=Error al cambiar estado del ticket');
                exit();
            }
        } else {
            header('Location: ../vista/tecnico-tickets.php?error=Estado no válido');
            exit();
        }
    }
}

// Si llegamos aquí, redirigir al dashboard
header('Location: ../vista/tecnico-tickets.php');
exit();
