<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar que el usuario esté logueado y sea administrador
    if (!isset($_SESSION['id_usuario']) || $_SESSION['rol'] !== 'administrador') {
        header('Location: ../login.php');
        exit();
    }

    $id_usuario = $_POST['id_usuario'] ?? '';
    $especialidad = trim($_POST['especialidad'] ?? '');

    // Validaciones
    if (empty($id_usuario) || empty($especialidad)) {
        header('Location: ../vista/admin-usuarios.php?error=Datos incompletos');
        exit();
    }

    // Verificar que el usuario existe y es usuario normal
    $stmt = $conexion->prepare("SELECT id, nombre FROM usuarios WHERE id = ? AND rol = 'usuario'");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: ../vista/admin-usuarios.php?error=Usuario no encontrado o ya es técnico/administrador');
        exit();
    }

    $usuario = $result->fetch_assoc();

    // Promover usuario a técnico
    $stmt = $conexion->prepare("UPDATE usuarios SET rol = 'tecnico', especialidad = ? WHERE id = ?");
    $stmt->bind_param("si", $especialidad, $id_usuario);

    if ($stmt->execute()) {
        header('Location: ../vista/admin-usuarios.php?success=Usuario ' . $usuario['nombre'] . ' promovido a técnico exitosamente');
    } else {
        header('Location: ../vista/admin-usuarios.php?error=Error al promover usuario: ' . $conexion->error);
    }
    exit();
}
?>