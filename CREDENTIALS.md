# Credenciales por defecto

## Usuario Administrador

- **Email**: admin@sistema.com
- **Username**: admin
- **Password**: admin123
- **Rol**: ROLE_ADMIN

Este usuario se crea automáticamente cuando se levanta el contenedor Docker.

## Personalizar credenciales

Puedes personalizar las credenciales del administrador editando el archivo `docker-entrypoint.sh` o ejecutando el comando manualmente:

```bash
docker exec sistema-interno-php php bin/console app:create-admin email@ejemplo.com nuevo_usuario nueva_password --force
```

## Crear usuarios adicionales

Para crear usuarios adicionales, puedes usar el mismo comando:

```bash
docker exec sistema-interno-php php bin/console app:create-admin otro@email.com otro_usuario otra_password
```