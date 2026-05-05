# Payment Bridge Challenge - Guillermo Alosilla

Este proyecto es una solución técnica para un **Bridge de Pagos** desarrollado en Laravel 11, diseñado para gestionar transacciones entre comercios y entidades bancarias de forma asíncrona, resiliente e íntegra.

## Tecnologías Utilizadas

*   **Framework:** Laravel 11
*   **Lenguaje:** PHP 8.2+
*   **Base de Datos:** PostgreSQL (para asegurar integridad referencial y manejo de zonas horarias)
*   **Contenerización:** Laravel Sail (Docker)
*   **Colas (Queues):** Database driver (para procesamiento asíncrono de notificaciones)

---

## Instalación y Ejecución

Sigue estos pasos para desplegar el entorno local:

1. **Clonar el repositorio:**
   ```bash
   git clone <URL_DE_TU_REPOSITORIO>
   cd payment-bridge-challenge

2. **Configuración del entorno:**
    cp .env.example .env

3. **Instalación de dependencias y levantar contenedores**
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install

    ./vendor/bin/sail up -d

4. **Ejecutar migraciones y seeders**
    ./vendor/bin/sail artisan migrate --seed

5. **Ejecutar el Queue Worker**
    ./vendor/bin/sail artisan queue:work

---

## Arquitectura y Decisiones Técnicas
1. **Base de Datos e Índices**
    Se optó por PostgreSQL debido a su manejo avanzado de tipos de datos y zonas horarias, crítico para el Módulo F (Liquidación).

    Se aplicaron índices únicos en payment_code y event_id para garantizar la integridad.

    Se utiliza una columna settled_at para controlar el estado de liquidación y evitar dobles pagos.

2. **Idempotencia (Módulo B y D)**
    Para prevenir el procesamiento duplicado de transacciones (muy común en reintentos de APIs bancarias), el sistema valida la existencia previa de event_id antes de actualizar cualquier estado de pago.

3. **Resiliencia en Notificaciones (Módulo E)**
    La integración con comercios externos se maneja mediante Laravel Jobs. Si el webhook del comercio falla, el Job implementa una política de reintentos asíncronos con un backoff de 60 segundos, evitando bloquear el flujo principal.

4. **Lógica de Cut-off (Módulo F)**
    La consulta de candidatos a liquidación filtra operaciones PAID considerando la hora de corte 20:45 (America/Lima). Se utiliza lógica SQL nativa para asegurar que la conversión de zona horaria sea precisa desde el motor de base de datos.

---

## Documentación de endpoints
Se incluye una colección de Postman en la carpeta /docs del repositorio para facilitar las pruebas de cada módulo:

    POST /api/v1/payments: Registro de intención de pago (Módulo A).

    POST /api/v1/bank/notifications: Notificación en tiempo real del banco (Módulo B).

    POST /api/v1/bank/reconciliation: Carga de movimientos para conciliación (Módulo D).

    GET /api/v1/settlements/candidates: Consulta de pagos listos para liquidación (Módulo F).