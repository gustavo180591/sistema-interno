# Guía de Deployment - Sistema Interno

## Requisitos del servidor
- Docker y Docker Compose instalados
- Git
- Puerto 2000 disponible (o el puerto que prefieras)
- Puertos 3313 (MySQL) y 3000 (Socket.IO) disponibles

## Pasos para deployar

### 1. Clonar el repositorio
```bash
git clone [URL_DEL_REPOSITORIO] sistema-interno
cd sistema-interno
```

### 2. Configurar el archivo .env
```bash
cp env.example .env
```

Editar `.env` y asegurarse que tenga:
```
APP_ENV=dev
APP_SECRET=genera_una_clave_segura_aqui
DATABASE_URL="mysql://gustavo:12345678@database:3306/sistema-interno?serverVersion=8.0.37&charset=utf8mb4"
```

### 3. Construir y levantar los contenedores
```bash
# Construir las imágenes
docker compose build

# Levantar todos los servicios
docker compose up -d
```

### 4. Verificar que los contenedores estén funcionando
```bash
docker compose ps
```

Deberías ver 4 contenedores corriendo:
- sistema-interno-db (MySQL)
- sistema-interno-php (PHP-FPM)
- sistema-interno-nginx (Nginx)
- sistema-interno-socket (Socket.IO)

### 5. Verificar logs (opcional)
```bash
# Ver todos los logs
docker compose logs -f

# Ver logs de un servicio específico
docker compose logs -f php
```

## Acceso a la aplicación

- **Aplicación web**: http://[IP_DEL_SERVIDOR]:2000
- **Socket.IO**: http://[IP_DEL_SERVIDOR]:3000
- **MySQL**: [IP_DEL_SERVIDOR]:3313

## Credenciales por defecto

- **Email**: admin@sistema.com
- **Username**: admin
- **Password**: admin123
- **Roles**: ROLE_ADMIN, ROLE_AUDITOR

## Comandos útiles

### Detener la aplicación
```bash
docker compose down
```

### Reiniciar servicios
```bash
docker compose restart
```

### Ejecutar comandos de Symfony
```bash
# Limpiar caché
docker exec sistema-interno-php php bin/console cache:clear

# Ver rutas disponibles
docker exec sistema-interno-php php bin/console debug:router

# Crear un nuevo usuario admin
docker exec sistema-interno-php php bin/console app:create-admin email@ejemplo.com password username
```

### Ver logs del contenedor PHP
```bash
docker logs sistema-interno-php --tail 50 -f
```

### Acceder a la base de datos
```bash
docker exec -it sistema-interno-db mysql -uroot -p12345678 sistema-interno
```

## Personalización de puertos

Si necesitas cambiar los puertos, edita el archivo `compose.yaml`:

```yaml
services:
  nginx:
    ports:
      - "PUERTO_DESEADO:80"  # Cambiar 2000 por el puerto deseado
  
  database:
    ports:
      - "PUERTO_MYSQL:3306"  # Cambiar 3313 por el puerto deseado
  
  socket-server:
    ports:
      - "PUERTO_SOCKET:3000" # Cambiar 3000 por el puerto deseado
```

## Solución de problemas comunes

### Error de permisos
```bash
docker exec sistema-interno-php chown -R www-data:www-data var/
```

### Limpiar todo y empezar de nuevo
```bash
docker compose down -v  # Elimina también los volúmenes
docker compose up -d --build
```

### Verificar conexión a la base de datos
```bash
docker exec sistema-interno-php php bin/console doctrine:query:sql "SELECT 1"
```

## Backup de la base de datos

### Crear backup
```bash
docker exec sistema-interno-db mysqldump -uroot -p12345678 sistema-interno > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restaurar backup
```bash
docker exec -i sistema-interno-db mysql -uroot -p12345678 sistema-interno < backup.sql
```

## Monitoreo

### Ver uso de recursos
```bash
docker stats
```

### Ver logs en tiempo real
```bash
docker compose logs -f --tail=100
```

## Actualización del código

1. Detener los contenedores:
```bash
docker compose down
```

2. Actualizar el código:
```bash
git pull origin master
```

3. Reconstruir y levantar:
```bash
docker compose up -d --build
```

4. Limpiar caché:
```bash
docker exec sistema-interno-php php bin/console cache:clear
```