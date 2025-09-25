<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_ticket = $_POST['id_ticket'] ?? null;
    $id_tecnico = $_POST['id_tecnico'] ?? null;

    // Aquí defines a dónde redirigir (por defecto admin-tickets si no viene nada)
    $redirectPage = $_POST['redirect'] ?? 'admin-tickets';

    if ($id_ticket && $id_tecnico) {
        // Obtener el nombre del técnico asignado
        $stmt = $conexion->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id_tecnico);
        $stmt->execute();
        $stmt->bind_result($nombre_tecnico);
        $stmt->fetch();
        $stmt->close();

        // Asignar el técnico al ticket
        $stmt = $conexion->prepare("UPDATE tickets SET id_tecnico_asignado = ?, fecha_actualizacion = NOW() WHERE id = ?");
        $stmt->bind_param("ii", $id_tecnico, $id_ticket);

        if ($stmt->execute()) {
            $redirect = "../vista/$redirectPage.php?success=" . urlencode("Técnico $nombre_tecnico asignado al ticket #$id_ticket");
        } else {
            $redirect = "../vista/$redirectPage.php?error=" . urlencode("Error al asignar el técnico");
        }

        $stmt->close();
        header("Location: " . $redirect);
        exit();
    } else {
        header("Location: ../vista/$redirectPage.php?error=" . urlencode("Datos incompletos"));
        exit();
    }
} else {
    $redirectPage = $_POST['redirect'] ?? 'admin-tickets';
    header("Location: ../vista/$redirectPage.php?error=" . urlencode("Método inválido"));
    exit();
}
