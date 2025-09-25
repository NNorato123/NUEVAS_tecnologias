    <?php
    session_start();
    include '../config/Conexion.php';

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $email = trim($_POST['email'] ?? '');
        $contraseña = trim($_POST['contrasena'] ?? '');

        
        if (empty($email) || empty($contraseña)) {
            header('Location: ../login.php?error=Por favor complete todos los campos');
            exit();
        }
        
        // Consulta de usuario
        $stmt = $conexion->prepare("SELECT id, nombre, email, contraseña, id_rol FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $usuario = $result->fetch_assoc();
            
            if (password_verify($contraseña, $usuario['contraseña'])) {
                // Variables de sesión
                $_SESSION['id_usuario'] = $usuario['id'];
                $_SESSION['usuario'] = $usuario['nombre'];
                $_SESSION['email'] = $usuario['email'];
                $_SESSION['id_rol'] = $usuario['id_rol'];
                
                // Redireccionar al dashboard
                header('Location: ../vista/dashboard.php');

                exit();
            } else {
                header('Location: ../login.php?error=Credenciales incorrectas');
                exit();
            }
        } else {
            header('Location: ../login.php?error=Usuario no encontrado');
            exit();
        }
    } else {
        header('Location: ../vista/login.php');
        exit();
    }
    ?>
