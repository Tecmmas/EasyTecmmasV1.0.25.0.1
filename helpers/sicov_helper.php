<?php

/**
 * Devuelve la fecha formateada, o '' si el valor no representa una fecha real.
 *
 * Pensada para el armado del payload de SICOV (traits Trait_Indra / Trait_Ci2),
 * donde un campo de fecha puede llegar vacío o con texto ("No Aplica") —caso de
 * los remolques / semirremolques, que no llevan SOAT ni certificado de gases—.
 *
 * Cubre los tres casos que hoy fallan o mienten:
 *   - '' / null / '0000-00-00'  -> ''  (date_create() los tomaria como "hoy"
 *                                       o como el anio -0001, no como false)
 *   - "No Aplica" u otro texto   -> ''  (date_create() devuelve false)
 *   - fecha valida               -> date_format(..., $formato)
 *
 * No reinterpreta el formato de entrada: usa date_create() tal cual, para no
 * alterar el valor de ningun campo de fecha que el sistema ya envie bien.
 *
 * Funcion pura: sin acceso a BD, sin estado.
 *
 * @param string|null $valor    Valor a formatear (fecha, '', null o texto).
 * @param string      $formato  Formato de date_format (ej. 'Y-m-d', 'Y/m/d').
 * @return string  Fecha formateada, o '' si el valor no es una fecha real.
 */
function fecha_sicov_o_vacio($valor, $formato)
{
    $valor = trim((string) $valor);
    if ($valor === '' || $valor === '0000-00-00') {
        return '';
    }

    $d = date_create($valor);
    if ($d === false) {
        return '';
    }

    return date_format($d, $formato);
}
