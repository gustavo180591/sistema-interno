# Documentación del Sistema de Gestión Interna

## Descripción General

Este sistema de gestión interna está desarrollado con Symfony 6 y proporciona herramientas para la gestión de tickets, usuarios y departamentos. Está diseñado para facilitar el seguimiento de solicitudes, tareas y la comunicación entre diferentes áreas de la organización.

## Características Principales

### Gestión de Tickets
- Creación, edición y seguimiento de tickets
- Asignación de tickets a departamentos específicos
- Seguimiento del estado de los tickets
- Sistema de comentarios y actualizaciones

### Gestión de Usuarios
- Autenticación y autorización de usuarios
- Roles y permisos (Administrador, Usuario, etc.)
- Perfiles de usuario personalizables
- Historial de actividades

### Panel de Administración
- Gestión de usuarios y permisos
- Métricas y estadísticas de uso
- Configuración del sistema

## Requisitos del Sistema

- PHP 8.1 o superior
- SQLite3 (configuración por defecto) o MySQL 5.7+/MariaDB 10.3+
- Composer
- Node.js y Yarn (para los assets)
- Symfony CLI (opcional pero recomendado)

## Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [URL_DEL_REPOSITORIO]
   cd sistema-interno
   ```

2. **Instalar dependencias de PHP**
   ```bash
   composer install
   ```

3. **Instalar dependencias de JavaScript**
   ```bash
   yarn install
   yarn build
   ```

4. **Configurar el archivo .env**
   El sistema viene configurado por defecto con SQLite. Para usar MySQL, descomenta y configura la línea correspondiente en `.env`:
   ```bash
   # Para SQLite (configuración predeterminada)
   DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
   
   # Para MySQL (descomentar y configurar)
   # DATABASE_URL="mysql://usuario:contraseña@127.0.0.1:3306/nombre_bd?serverVersion=8.0.31&charset=utf8mb4"
   ```
   
   Luego copia el archivo a `.env.local` para personalizaciones locales:
   ```bash
   cp .env .env.local
   ```

5. **Configurar la base de datos**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. **Cargar datos de prueba (opcional)**
   ```bash
   php bin/console doctrine:fixtures:load
   ```

7. **Iniciar el servidor**
   ```bash
   symfony server:start
   ```

## Conexión a DBeaver

### Requisitos Previos
- Tener instalado DBeaver Community Edition (https://dbeaver.io/)
- Tener las credenciales de la base de datos
- Para SQLite: Solo necesitas la ruta al archivo `var/data.db`
- Para MySQL: Necesitarás host, puerto, nombre de la base de datos, usuario y contraseña

### Pasos para Conectar

1. **Abrir DBeaver**
   - Inicia DBeaver en tu computadora

2. **Crear una Nueva Conexión**
   - Haz clic en "Nueva Conexión" (icono de enchufe en la barra superior)
   - Para SQLite: Selecciona "SQLite" de la lista de bases de datos
   - Para MySQL: Selecciona "MySQL" de la lista de bases de datos

3. **Configurar la Conexión**
   
   **Para SQLite:**
   - **Database**: Haz clic en "Browse" y selecciona el archivo `var/data.db` en tu proyecto
   
   **Para MySQL:**
   - **Host**: `localhost` (o la dirección de tu servidor)
   - **Puerto**: `3306` (puerto por defecto de MySQL)
   - **Base de datos**: `sistema_interno` (o el nombre que hayas configurado)
   - **Usuario**: `sistema_user` (o el usuario que hayas configurado)
   - **Contraseña**: `clave123` (o la contraseña que hayas configurado)

4. **Configuración Avanzada (Opcional)**
   - Pestaña "Driver properties":
     - `useSSL`: `false` (si es desarrollo local)
     - `allowPublicKeyRetrieval`: `true`
   - Pestaña "SSH" (si usas túnel SSH)
     - Marca "Use SSH tunnel"
     - Configura los detalles de tu servidor SSH

5. **Probar la Conexión**
   - Haz clic en "Test Connection" para verificar que todo esté correcto
   - Si la prueba es exitosa, haz clic en "Finish"

6. **Explorar la Base de Datos**
   - Una vez conectado, podrás ver todas las tablas en el panel izquierdo
   - Haz doble clic en cualquier tabla para ver su contenido

## Estructura del Proyecto

```
sistema-interno/
├── assets/           # Archivos de frontend (JS, CSS, imágenes)
├── bin/              # Comandos de consola
├── config/           # Configuración de la aplicación
├── migrations/       # Migraciones de la base de datos
├── public/           # Punto de entrada de la aplicación
├── src/              # Código fuente de la aplicación
│   ├── Controller/   # Controladores
│   ├── Entity/       # Entidades de la base de datos
│   ├── Form/         # Formularios
│   └── Repository/   # Repositorios
├── templates/        # Plantillas Twig
└── tests/            # Pruebas automatizadas
```

## Comandos Útiles

- **Actualizar esquema de base de datos**:
  ```bash
  php bin/console doctrine:schema:update --force
  ```

- **Generar entidades**:
  ```bash
  php bin/console make:entity
  ```

- **Crear migración**:
  ```bash
  php bin/console make:migration
  ```

- **Ejecutar migraciones pendientes**:
  ```bash
  php bin/console doctrine:migrations:migrate
  ```

## Solución de Problemas Comunes

### Error de conexión a la base de datos
- Verifica que el servidor de base de datos esté en ejecución
- Comprueba las credenciales en `.env.local`
- Asegúrate de que el usuario tenga los permisos necesarios

### Problemas con las migraciones
- Si hay conflictos, intenta:
  ```bash
  php bin/console doctrine:database:drop --force
  php bin/console doctrine:database:create
  php bin/console doctrine:migrations:migrate
  ```

### Problemas con los assets
- Si los estilos no se cargan:
  ```bash
  yarn install
  yarn build
  ```

## Soporte

Para reportar problemas o solicitar ayuda, por favor abre un issue en el repositorio del proyecto o contacta al equipo de desarrollo.

---
*Última actualización: Agosto 2024*
