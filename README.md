# Mesa de Ayuda - Nuevas Tecnologías

Sistema web de gestión de tickets de soporte técnico para empresas o instituciones educativas. Permite a clientes crear tickets, a técnicos gestionarlos y a administradores supervisar usuarios y tickets.

## Características principales
- Registro y login de usuarios (cliente, técnico, administrador)
- Creación y seguimiento de tickets de soporte
- Asignación y cambio de estado de tickets
- Paneles diferenciados por rol
- Historial y comentarios en tickets
- Gestión de usuarios y roles (admin)
- Estadísticas rápidas y filtros avanzados

## Estructura del proyecto

NuevasTecnologias/
├── config/           # Configuración y conexión a base de datos
├── controlador/      # Lógica de negocio y controladores PHP
├── css/              # Hojas de estilo (por módulo y globales)
├── img/              # Imágenes y avatares
├── js/               # Scripts JS y librerías de terceros
├── vista/            # Vistas PHP (paneles, formularios, dashboard)
├── login.php         # Página de inicio de sesión


## Requisitos
- PHP 7.4 o superior
- MySQL/PHPMyAdmin
- Servidor web (XAMPP)
- Navegador moderno

## Instalación local
1. Clona este repositorio en tu servidor local:
   sh
   git clone https://github.com/NNorato123/NuevasTecnologias.git
   
2. Crea una base de datos llamada nuevastecnologias_1.0 y ejecuta el script SQL correspondiente (no incluido aquí).
3. Configura la conexión en config/Conexion.php si es necesario.
4. Inicia el servidor web y accede a http://localhost/nuevastecnologias2/NuevasTecnologias/login.php

## Roles y funcionalidades
- *Cliente*: puede registrar tickets, ver y comentar los propios.
- *Técnico*: gestiona tickets asignados, cambia estados, comenta y cierra tickets.
- *Administrador*: gestiona usuarios, asigna técnicos, supervisa y filtra todos los tickets.

## Principales rutas y archivos
- login.php: acceso al sistema
- vista/dashboard.php: panel principal según rol
- vista/crear-ticket.php: formulario de nuevo ticket
- vista/tecnico-tickets.php: tickets asignados al técnico
- vista/admin-tickets.php: gestión global de tickets (admin)
- vista/admin-usuarios.php: gestión de usuarios (admin)

## Dependencias y librerías
- Bootstrap Icons
- DataTables
- jQuery
- SweetAlert, Bootstrap Notify, entre otros (en js/lib/)

