<?php
session_start();
include '../config/Conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar login y rol admin
    if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 3) {
        header('Location: ../login.php');
        exit();
    }

    // Acción: crear / promover / degradar
    $accion = $_POST['accion'] ?? '';

    /* ======================================================
       1) CREAR NUEVO TÉCNICO
    ====================================================== */
    if ($accion === 'crear') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');


        if (empty($nombre) || empty($email) || empty($password)) {
            header('Location: ../vista/crear-tecnico.php?error=Todos los campos son obligatorios');
            exit();
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header('Location: ../vista/crear-tecnico.php?error=Email no válido');
            exit();
        }

        if (strlen($password) < 6) {
            header('Location: ../vista/crear-tecnico.php?error=La contraseña debe tener al menos 6 caracteres');
            exit();
        }

        // Verificar que no exista
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            header('Location: ../vista/crear-tecnico.php?error=El email ya está registrado');
            exit();
        }

        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, contraseña, id_rol, fcharegistro) 
                            VALUES (?, ?, ?, 2, NOW())");
        $stmt->bind_param("sss", $nombre, $email, $password_hash);


        if ($stmt->execute()) {
            $tecnico_id = $conexion->insert_id;
            header('Location: ../vista/admin-usuarios.php?success=Técnico creado con ID #' . $tecnico_id);
        } else {
            header('Location: ../vista/crear-tecnico.php?error=Error al crear técnico: ' . $conexion->error);
        }
        exit();
    }

    /* ======================================================
       2) PROMOVER USUARIO → TÉCNICO
    ====================================================== */
    if ($accion === 'promover') {
        $id_usuario = intval($_POST['id_usuario'] ?? 0);

        if ($id_usuario <= 0) {
            header('Location: ../vista/admin-usuarios.php?error=ID de usuario no válido');
            exit();
        }

        $stmt = $conexion->prepare("UPDATE usuarios SET id_rol = 2 WHERE id = ?");
        $stmt->bind_param("i", $id_usuario);

        if ($stmt->execute()) {
            header('Location: ../vista/admin-usuarios.php?success=Usuario promovido a técnico');
        } else {
            header('Location: ../vista/admin-usuarios.php?error=Error al promover: ' . $conexion->error);
        }
        exit();
    }

    /* ======================================================
       3) DEGRADAR TÉCNICO → USUARIO
    ====================================================== */
    if ($accion === 'degradar') {
        $id_usuario = intval($_POST['id_usuario'] ?? 0);

        if ($id_usuario <= 0) {
            header('Location: ../vista/admin-usuarios.php?error=ID de usuario no válido');
            exit();
        }

        $stmt = $conexion->prepare("UPDATE usuarios SET id_rol = 1 WHERE id = ?");
        $stmt->bind_param("i", $id_usuario);

        if ($stmt->execute()) {
            header('Location: ../vista/admin-usuarios.php?success=Técnico degradado a usuario');
        } else {
            header('Location: ../vista/admin-usuarios.php?error=Error al degradar: ' . $conexion->error);
        }
        exit();
    }
    /* ======================================================
   4) ELIMINAR USUARIO
====================================================== */
if ($accion === 'eliminar') {
    $id_usuario = intval($_POST['id_usuario'] ?? 0);

    if ($id_usuario <= 0) {
        header('Location: ../vista/admin-usuarios.php?error=ID de usuario no válido');
        exit();
    }

    // IMPORTANTE: no permitir que un admin elimine a otro admin
    $check = $conexion->prepare("SELECT id_rol, nombre FROM usuarios WHERE id = ?");
    $check->bind_param("i", $id_usuario);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if ($row['id_rol'] == 3) {
            header('Location: ../vista/admin-usuarios.php?error=No se puede eliminar a un administrador');
            exit();
        }
        $nombre_usuario = $row['nombre'];
    } else {
        header('Location: ../vista/admin-usuarios.php?error=Usuario no encontrado');
        exit();
    }

    // Iniciar transacción para operación atómica
    $conexion->begin_transaction();
    
    try {
        // 1. Buscar o crear usuario "Sistema" para transferir tickets
        $sistema_check = $conexion->prepare("SELECT id FROM usuarios WHERE email = 'sistema@mesadeayuda.local'");
        $sistema_check->execute();
        $sistema_result = $sistema_check->get_result();
        
        if ($sistema_result->num_rows == 0) {
            // Crear usuario Sistema si no existe
            $crear_sistema = $conexion->prepare("INSERT INTO usuarios (nombre, email, contraseña, id_rol, fcharegistro) VALUES ('Sistema (Usuario Eliminado)', 'sistema@mesadeayuda.local', '', 1, NOW())");
            $crear_sistema->execute();
            $id_sistema = $conexion->insert_id;
        } else {
            $id_sistema = $sistema_result->fetch_assoc()['id'];
        }

        // 2. Verificar si el usuario tiene tickets
        $check_tickets = $conexion->prepare("SELECT COUNT(*) as total FROM tickets WHERE id_usuario_creador = ?");
        $check_tickets->bind_param("i", $id_usuario);
        $check_tickets->execute();
        $tickets_count = $check_tickets->get_result()->fetch_assoc()['total'];

        // 3. Transferir tickets al usuario Sistema
        if ($tickets_count > 0) {
            $transferir_tickets = $conexion->prepare("UPDATE tickets SET id_usuario_creador = ? WHERE id_usuario_creador = ?");
            $transferir_tickets->bind_param("ii", $id_sistema, $id_usuario);
            $transferir_tickets->execute();
        }

        // 4. Eliminar el usuario
        $eliminar_usuario = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
        $eliminar_usuario->bind_param("i", $id_usuario);
        $eliminar_usuario->execute();

        // Confirmar transacción
        $conexion->commit();
        
        $mensaje_tickets = $tickets_count > 0 ? " ({$tickets_count} tickets transferidos al sistema)" : "";
        header('Location: ../vista/admin-usuarios.php?success=Usuario "' . $nombre_usuario . '" eliminado correctamente' . $mensaje_tickets);
        
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $conexion->rollback();
        header('Location: ../vista/admin-usuarios.php?error=Error al eliminar usuario: ' . $e->getMessage());
    }
    exit();
}


    // Acción desconocida
    header('Location: ../vista/admin-usuarios.php?error=Acción no válida');
    exit();
}
?>
