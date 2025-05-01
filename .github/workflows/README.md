# Configuración del CI/CD con GitHub Actions

Este documento proporciona instrucciones para configurar el flujo de CI/CD implementado con GitHub Actions.

## Configuración de Secretos

Para que el despliegue funcione correctamente, necesitas configurar los siguientes secretos en tu repositorio de GitHub:

1. Ve a tu repositorio > Settings > Secrets and variables > Actions
2. Haz clic en "New repository secret"
3. Agrega los siguientes secretos:

### Secretos para el Despliegue FTP

| Nombre | Descripción |
|--------|-------------|
| `FTP_SERVER` | La dirección del servidor FTP (ej: ftp.example.com) |
| `FTP_USERNAME` | El nombre de usuario para acceder al FTP |
| `FTP_PASSWORD` | La contraseña para acceder al FTP |
| `FTP_SERVER_DIR` | El directorio en el servidor FTP donde se subirán los archivos |

### Secretos para la Configuración de Producción

| Nombre | Descripción |
|--------|-------------|
| `APP_KEY` | La clave de aplicación Laravel cifrada en base64 |
| `APP_URL` | La URL de la aplicación en producción |
| `DB_HOST` | El host de la base de datos |
| `DB_DATABASE` | El nombre de la base de datos |
| `DB_USERNAME` | El usuario de la base de datos |
| `DB_PASSWORD` | La contraseña de la base de datos |

## Flujo de Trabajo

El workflow realiza las siguientes acciones:

1. **Etapa de Tests**:
   - Configura PHP y una base de datos MySQL
   - Crea un archivo .env de pruebas
   - Instala las dependencias de Composer
   - Configura el entorno de pruebas
   - Ejecuta las pruebas con PHPUnit

2. **Etapa de Despliegue** (solo si los tests pasan y el push es a la rama main/master):
   - Instala dependencias de producción
   - Crea un archivo .env de producción usando los secretos configurados
   - Optimiza la aplicación para producción
   - Sube los archivos al servidor FTP

## Solución de Problemas

Si el workflow falla, verifica:

1. Los secretos estén correctamente configurados
2. El usuario FTP tenga permisos de escritura en el directorio especificado
3. La versión de PHP en el servidor coincida con la configurada en el workflow

Para más información sobre GitHub Actions, consulta la [documentación oficial](https://docs.github.com/es/actions). 