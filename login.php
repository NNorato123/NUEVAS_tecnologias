<?php
// Archivo: controlador/login.php
session_start();
include 'config/Conexion.php';

$mensaje = '';
$tipo_mensaje = '';

// Verificar si ya está logueado
if (isset($_SESSION['usuario'])) {
    header('Location: vista/dashboard.php');
    exit();
}

// Mostrar mensajes si existen en la URL
$mensaje = $_GET['error'] ?? ($_GET['success'] ?? '');
$tipo_mensaje = isset($_GET['error']) ? 'error' : (isset($_GET['success']) ? 'success' : '');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - NuevasTecnologias</title>
    <link rel="stylesheet" href="css/logueo/login.css">
</head>

<body>
    <div class="container">
        <div class="logo">
            <h1>Bienvenido</h1>
            <p>Inicia sesión en NuevasTecnologias</p>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="controlador/ContLogin.php" id="loginForm">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="contrasena">Contraseña</label>
                <div style="position: relative;">
                    <input type="password" id="contrasena" name="contrasena" required autocomplete="current-password">
                    <span class="password-toggle" onclick="togglePassword()">👁️</span>
                </div>
            </div>

            <button type="submit" class="btn" id="loginBtn">
                Iniciar Sesión
                <span class="loading" id="loading"></span>
            </button>
        </form>

        <div class="forgot-password">
            <a href="recuperar-password.php">¿Olvidaste tu contraseña?</a>
        </div>

        <div class="links">
            <p>¿No tienes cuenta? <a href="vista/registrar.php">Regístrate aquí</a></p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('contrasena');
            const toggleIcon = document.querySelector('.password-toggle');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');

            btn.disabled = true;
            loading.style.display = 'inline-block';
            btn.innerHTML = 'Iniciando sesión... <span class="loading"></span>';
        });

        // Auto-hide success/error messages after 5 seconds
        const alert = document.querySelector('.alert');
        if (alert) {
            setTimeout(function() {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 300);
            }, 5000);
        }

        // Email validation on blur
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            if (email && !isValidEmail(email)) {
                this.style.borderColor = '#e53e3e';
                this.style.background = '#fee';
            } else {
                this.style.borderColor = '#e1e5e9';
                this.style.background = '#f8f9fa';
            }
        });

        function isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }

        // Focus on email input when page loads
        window.addEventListener('load', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>

</html>