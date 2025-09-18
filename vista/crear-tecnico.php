<?php
session_start();

// Verificar si el usuario está logueado y es administrador (id_rol = 3)
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 3) {
    header('Location: ../login.php');
    exit();
}


$error = $_GET['error'] ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Técnico - Mesa de Ayuda</title>
    <link rel="stylesheet" href="../css/dashboard/crear-tecnico.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <header class="header">
        <a href="admin-usuarios.php" class="back-btn" title="Volver a Usuarios">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1>Crear Nuevo Técnico</h1>
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
        </div>
    </header>

    <div class="container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <div class="form-header">
                <div class="form-icon">
                    <i class="bi bi-person-plus"></i>
                </div>
                <h2>Registro de Nuevo Técnico</h2>
                <p>Complete la información del técnico que se agregará al sistema</p>
            </div>

            <form method="POST" action="../controlador/ContCrearTecnico.php" class="tecnico-form">
    <input type="hidden" name="accion" value="crear">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">
                            <i class="bi bi-person"></i>
                            Nombre Completo
                        </label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               placeholder="Ingrese el nombre completo" 
                               required
                               maxlength="100">
                        <small class="form-help">Nombre y apellidos del técnico</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="email">
                            <i class="bi bi-envelope"></i>
                            Correo Electrónico
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="ejemplo@empresa.com" 
                               required
                               maxlength="100">
                        <small class="form-help">Email institucional del técnico</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">
                            <i class="bi bi-lock"></i>
                            Contraseña
                        </label>
                        <div class="password-input">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Mínimo 6 caracteres" 
                                   required
                                   minlength="6">
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small class="form-help">Contraseña inicial para el técnico</small>
                    </div>
                </div>

               

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="window.history.back()">
                        <i class="bi bi-x-circle"></i>
                        Cancelar
                    </button>
                    <button type="submit" class="btn-create">
                        <i class="bi bi-person-check"></i>
                        Crear Técnico
                    </button>
                </div>
            </form>

            <div class="info-panel">
                <h3><i class="bi bi-info-circle"></i> Información</h3>
                <ul>
                    <li>El técnico podrá iniciar sesión con el email y contraseña proporcionados</li>
                    <li>Se le asignará automáticamente el rol de técnico en el sistema</li>
                    <li>Podrá ver y gestionar los tickets que se le asignen</li>
                    <li>La contraseña puede ser cambiada por el técnico después del primer acceso</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        }

        // Validación en tiempo real
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ced4da';
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            
            if (password && password.length < 6) {
                this.style.borderColor = '#dc3545';
            } else {
                this.style.borderColor = '#ced4da';
            }
        });
    </script>
</body>
</html>