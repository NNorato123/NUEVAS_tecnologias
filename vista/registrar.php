<?php
$mensaje = $_GET['error'] ?? ($_GET['success'] ?? '');
$tipo_mensaje = isset($_GET['error']) ? 'error' : (isset($_GET['success']) ? 'success' : '');
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Registro - NuevasTecnologias</title>
    <link rel="stylesheet" href="../css/logueo/registrar.css">
</head>

<body>
    <div class="container">
        <div class="logo">
            <h1>Registro</h1>
            <p>Crea tu cuenta en NuevasTecnologias</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="../controlador/ContRegistrar.php">
            <div class="form-group">
                <label for="nombre">Nombre Completo</label>
                <input type="text" id="nombre" name="nombre"
                    value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required>
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <input type="password" id="contrasena" name="contrasena" required>
                <div class="password-requirements">
                    Mínimo 6 caracteres
                </div>
            </div>

            <div class="form-group">
                <label for="confirmar_contrasena">Confirmar Contraseña</label>
                <input type="password" id="confirmar_contrasena" name="confirmar_contrasena" required>
            </div>

            <button type="submit" class="btn">Registrarse</button>
        </form>

        <div class="links">
            <p>¿Ya tienes cuenta? <a href="../login.php">Inicia Sesión</a></p>
        </div>
    </div>
    <script>
        // Validación en tiempo real
        document.getElementById('confirmar_contrasena').addEventListener('input', function() {
            const password = document.getElementById('contrasena').value;
            const confirmPassword = this.value;

            if (password !== confirmPassword && confirmPassword.length > 0) {
                this.style.borderColor = '#e53e3e';
                this.style.background = '#fee';
            } else {
                this.style.borderColor = '#e1e5e9';
                this.style.background = '#f8f9fa';
            }
        });

        // Validación de longitud de contraseña
        document.getElementById('contrasena').addEventListener('input', function() {
            const requirements = document.querySelector('.password-requirements');

            if (this.value.length < 6 && this.value.length > 0) {
                requirements.style.color = '#e53e3e';
                this.style.borderColor = '#e53e3e';
            } else if (this.value.length >= 6) {
                requirements.style.color = '#38a169';
                this.style.borderColor = '#38a169';
            } else {
                requirements.style.color = '#666';
                this.style.borderColor = '#e1e5e9';
            }
        });
    </script>
</body>

</html>