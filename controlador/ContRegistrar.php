<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $email = $_POST['email'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';

    // Validaciones
    if (empty($nombre) || empty($email) || empty($contrasena) || empty($confirmar_contrasena)) {
        header('Location: ../vista/registrar.php?error=Todos los campos son obligatorios');
        exit();
    }
    if ($contrasena !== $confirmar_contrasena) {
        header('Location: ../vista/registrar.php?error=Las contraseñas no coinciden');
        exit();
    }
    if (strlen($contrasena) < 6) {
        header('Location: ../vista/registrar.php?error=La contraseña debe tener al menos 6 caracteres');
        exit();
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header('Location: ../vista/registrar.php?error=El email no es válido');
        exit();
    }

    // Verificar si el email ya existe
    $stmt = $conexion->prepare("SELECT email FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header('Location: ../vista/registrar.php?error=Este email ya está registrado');
        exit();
    }

    // Encriptar contraseña
    $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);

    // Insertar nuevo usuario con fecha actual
    $fecha_registro = date('Y-m-d H:i:s'); // <-- AGREGA ESTA LÍNEA
    $id_rol = 1; // 1 = cliente
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, contraseña, fcharegistro, id_rol) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $nombre, $email, $contrasena_hash, $fecha_registro, $id_rol);

    if ($stmt->execute()) {
        header('Location: ../login.php?success=Registro exitoso. Inicia sesión');
        exit();
    } else {
        header('Location: ../vista/registrar.php?error=Error al registrar usuario: ' . $conexion->error);
        exit();
    }
}
?>