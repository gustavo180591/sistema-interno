# Sistema Interno

Aplicación web desarrollada con Symfony para la gestión interna de la empresa.

## Requisitos

- PHP 8.2 o superior
- Composer
- MySQL 8.0 o superior
- Docker y Docker Compose (opcional)

## Instalación

1. Clonar el repositorio:
   ```bash
   git clone https://github.com/gustavo180591/sistema-interno.git
   cd sistema-interno
   ```

2. Instalar dependencias de Composer:
   ```bash
   composer install
   ```

3. Configurar las variables de entorno:
   ```bash
   cp .env .env.local
   # Editar .env.local con tus credenciales
   ```

4. Iniciar los contenedores de Docker (opcional):
   ```bash
   docker compose up -d
   ```

5. Crear la base de datos y cargar el esquema:
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

6. Iniciar el servidor de desarrollo:
   ```bash
   symfony server:start -d
   ```

La aplicación estará disponible en: http://localhost:8000

## Estructura del Proyecto

- `src/` - Código fuente de la aplicación
- `templates/` - Plantillas Twig
- `public/` - Archivos públicos (CSS, JS, imágenes)
- `migrations/` - Migraciones de la base de datos
- `config/` - Configuración de la aplicación

## Licencia

Este proyecto está bajo la licencia MIT.
