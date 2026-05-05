Módulo,Método,Endpoint,Descripción
A,POST,/api/v1/payments,Registra una intención de pago.
B,POST,/api/v1/bank/notifications,Recibe la confirmación del banco (Idempotente).
D,POST,/api/v1/bank/reconciliation,Carga masiva de movimientos para conciliación.
F,GET,/api/v1/settlements/candidates,Lista pagos confirmados antes de las 20:45 Lima.