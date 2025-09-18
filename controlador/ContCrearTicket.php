<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['id_usuario'])) {
        header('Location: ../login.php');
        exit();
    }

    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoria = $_POST['categoria'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $id_usuario = $_SESSION['id_usuario'];

    // Validaciones
    if (empty($titulo) || empty($descripcion) || empty($categoria)) {
        header('Location: ../vista/crear-ticket.php?error=Todos los campos obligatorios deben ser completados');
        exit();
    }

    if (strlen($titulo) < 10) {
        header('Location: ../vista/crear-ticket.php?error=El título debe tener al menos 10 caracteres');
        exit();
    }

    if (strlen($descripcion) < 20) {
        header('Location: ../vista/crear-ticket.php?error=La descripción debe tener al menos 20 caracteres');
        exit();
    }

    // Verificar que la categoría existe
    $stmt = $conexion->prepare("SELECT id FROM categorias WHERE id = ?");
    $stmt->bind_param("i", $categoria);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: ../vista/crear-ticket.php?error=Categoría inválida');
        exit();
    }

    // Insertar el ticket
    $stmt = $conexion->prepare("INSERT INTO tickets (titulo, descripcion, prioridad, id_categoria, id_usuario_creador) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssii", $titulo, $descripcion, $prioridad, $categoria, $id_usuario);

    if ($stmt->execute()) {
        $ticket_id = $conexion->insert_id;
        header('Location: ../vista/dashboard.php?success=Ticket #' . $ticket_id . ' creado exitosamente');
        exit();
    } else {
        header('Location: ../vista/crear-ticket.php?error=Error al crear el ticket: ' . $conexion->error);
        exit();
    }
}
?>