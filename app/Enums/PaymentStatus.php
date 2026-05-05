<?php

namespace App\Enums;

/**
 * Enum PaymentStatus
 * 
 * Define los estados posibles de un pago dentro del ecosistema del Bridge.
 * Este Enum centraliza la lógica de estados para garantizar la integridad
 * de la máquina de estados del sistema financiero.
 * 
 * @package App\Enums
 * @author Guillermo Alosilla <guille.alosillasm@gmail.com>
 */
enum PaymentStatus: string {
    /** 
     * El pago ha sido creado en el sistema pero aún no se recibe 
     * confirmación de la pasarela o banco. (Módulo A)
     */
    case PENDING = 'PENDING';

    /** 
     * Se ha recibido y validado satisfactoriamente la notificación 
     * de pago por parte del banco. (Módulo B)
     */
    case PAID = 'PAID';

    /** 
     * El pago presenta discrepancias durante la conciliación (Módulo D), 
     * como diferencias en el monto o moneda. Requiere revisión manual.
     */
    case OBSERVED = 'OBSERVED';

    /** 
     * El pago ha pasado el proceso de conciliación exitosamente y 
     * coincide plenamente con los registros bancarios.
     */
    case RECONCILED = 'RECONCILED';

    /**
     * Retorna una descripción amigable del estado para interfaces de usuario.
     * 
     * @return string
     */
    public function description(): string
    {
        return match($this) {
            self::PENDING    => 'Pendiente de procesamiento',
            self::PAID       => 'Confirmado por el banco',
            self::OBSERVED   => 'En revisión por discrepancia',
            self::RECONCILED => 'Conciliado y verificado',
        };
    }
}