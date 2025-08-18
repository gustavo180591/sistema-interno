# 📘 Manual de Usuario - Sistema Interno

Este manual describe cómo utilizar el sistema tanto para **usuarios** como para **administradores**.

---

## 👤 Usuario (ROLE_USER)

### 1. Iniciar Sesión
1. Acceder a la página principal del sistema.
2. Introducir usuario y contraseña.
3. Hacer clic en **Iniciar sesión**.

![Login](img/login.png)

---

### 2. Crear un Ticket
1. Ir a la sección **Nuevo Ticket**.
2. Completar los campos obligatorios:
   - **Descripción**
   - **Departamento**
   - **Estado inicial: pendiente**
3. Opcional: asociar un **Número de pedido**.
4. Guardar.

![Crear Ticket](img/crear-ticket.png)

---

### 3. Ver y Gestionar Tickets
- Ver todos los tickets creados.  
- Consultar tickets donde seas colaborador.  
- Cambiar estado de tickets propios.  
- Agregar tareas asociadas.  

![Lista de Tickets](img/lista-tickets.png)

---

## 👨‍💼 Administrador (ROLE_ADMIN)

### 1. Acceso al Panel de Administración
- Disponible en el menú superior.  
- Solo accesible para usuarios con `ROLE_ADMIN`.  

![Panel Admin](img/panel-admin.png)

---

### 2. Gestión de Usuarios
- Crear nuevos usuarios.  
- Editar roles y datos de usuarios existentes.  
- Eliminar usuarios.  

![Gestión Usuarios](img/gestion-usuarios.png)

---

### 3. Gestión de Tickets
- Visualizar todos los tickets del sistema.  
- Eliminar cualquier ticket.  
- Generar reportes y estadísticas.  

![Reportes](img/reportes.png)

---

## 📂 Notas

- Las capturas de pantalla deben almacenarse en `docs/img/`  
- Ejemplos sugeridos: `login.png`, `crear-ticket.png`, `lista-tickets.png`, `panel-admin.png`, `gestion-usuarios.png`, `reportes.png`
