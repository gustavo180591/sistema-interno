# Sistema Interno

Este es un sistema interno de gestión de tickets desarrollado con Symfony.

## 🚀 Instalación

```bash
git clone https://github.com/gustavo180591/sistema-interno.git
cd sistema-interno
composer install
npm install && npm run build
```

Configurar el archivo `.env` con las credenciales de la base de datos y ejecutar:

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
```

## ▶️ Ejecución

```bash
symfony server:start
```

Acceder a `http://localhost:8000`

## 👥 Roles

- **ROLE_USER**: crear y gestionar sus propios tickets.  
- **ROLE_ADMIN**: acceso al panel de administración, gestión de usuarios y estadísticas.

## 📄 Documentación

La documentación completa se encuentra en [`docs/manual-usuario.md`](docs/manual-usuario.md).
