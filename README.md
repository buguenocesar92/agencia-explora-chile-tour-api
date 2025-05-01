# Agencia Explora Chile Tour API

Esta es la API backend para la aplicación de gestión de la Agencia Explora Chile Tour, una empresa dedicada a la organización de tours y experiencias turísticas en Chile.

<p align="center">
<a href="https://github.com/yourusername/agencia-explora-chile-tour-api/actions"><img src="https://github.com/yourusername/agencia-explora-chile-tour-api/workflows/CI/CD%20Workflow/badge.svg" alt="Build Status"></a>
</p>

## Sobre el Proyecto

La API de Agencia Explora Chile Tour está construida con Laravel y proporciona las siguientes funcionalidades:

- Gestión de clientes
- Administración de tours y reservas
- Sistema de autenticación y autorización
- Reportes y estadísticas

## Arquitectura

El proyecto sigue una arquitectura basada en el patrón Controller-Service-Repository:

- **Controllers**: Manejan las solicitudes HTTP y delegan la lógica de negocio a los servicios
- **Services**: Contienen la lógica de negocio y orquestan las operaciones
- **Repositories**: Gestionan el acceso a datos y la persistencia

## Instalación y Configuración

### Requisitos Previos

- PHP >= 8.1
- MySQL >= 8.0
- Composer

### Pasos de Instalación

1. Clonar el repositorio:
```bash
git clone https://github.com/yourusername/agencia-explora-chile-tour-api.git
cd agencia-explora-chile-tour-api
```

2. Instalar dependencias:
```bash
composer install
```

3. Configurar el entorno:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configurar la base de datos en el archivo `.env`.

5. Ejecutar migraciones y seeders:
```bash
php artisan migrate --seed
```

6. Iniciar el servidor:
```bash
php artisan serve
```

## Pruebas

Para ejecutar las pruebas:

```bash
php artisan test
```

## CI/CD con GitHub Actions

Este proyecto implementa un flujo de Integración Continua y Despliegue Continuo (CI/CD) con GitHub Actions:

1. **Integración Continua**: Al hacer push o crear un pull request, se ejecutan automáticamente todas las pruebas.
2. **Despliegue Continuo**: Si las pruebas pasan y el push es a la rama main/master, se despliega la aplicación automáticamente al servidor FTP.

Para más detalles sobre la configuración del CI/CD, consulta [.github/workflows/README.md](.github/workflows/README.md).

## Licencia

Este proyecto es software propietario de Agencia Explora Chile Tour.
