<?php

/**
 * Indica si, con los valores medidos, hay dilucion de la muestra en un vehiculo
 * de encendido por chispa (Res. 762 de 2022, paragrafo de dilucion, previo al
 * art. 33 — verificar numeral en
 * https://www.alcaldiabogota.gov.co/sisjur/normas/Norma1.jsp?i=137537).
 *
 *   - Gasolina (2), GASO ELEC (10), GAS GASOL (4, bicombustible operando con
 *     gasolina, art. 33): dilucion si O2 > 5 % o CO2 < 7 %.
 *   - GNV (3) y GLP (9): dilucion si CO2 < 7 %, y solo si $aplicaGas.
 *   - Cualquier otro combustible: false.
 *
 * Los valores pueden venir con "*" (lo agrega evalGases cuando incumplen) o
 * vacios; los vacios / no numericos se ignoran, no cuentan como 0.
 * Limites estrictos: CO2 = 7.00 y O2 = 5.00 no son dilucion.
 *
 * Funcion pura: sin acceso a BD, sin estado.
 *
 * @param string|int $idtipocombustible  Codigo de combustible (recursos/combustible.json).
 * @param mixed      $co2_ralenti
 * @param mixed      $co2_crucero
 * @param mixed      $o2_ralenti
 * @param mixed      $o2_crucero
 * @param bool       $aplicaGas  Si la regla aplica a GNV/GLP (fecha de vigencia de la norma).
 * @return bool
 */
function hay_dilucion_muestra($idtipocombustible, $co2_ralenti, $co2_crucero, $o2_ralenti, $o2_crucero, $aplicaGas = true)
{
    $limiteCo2 = 7.0;
    $limiteO2  = 5.0;

    $comb = (string) $idtipocombustible;
    if (in_array($comb, ['2', '10', '4'], true)) {
        $revisaO2 = true;
    } elseif (in_array($comb, ['3', '9'], true) && $aplicaGas) {
        $revisaO2 = false;
    } else {
        return false;
    }

    foreach ([$co2_ralenti, $co2_crucero] as $v) {
        $n = _dilucion_a_numero($v);
        if ($n !== null && $n < $limiteCo2) {
            return true;
        }
    }

    if ($revisaO2) {
        foreach ([$o2_ralenti, $o2_crucero] as $v) {
            $n = _dilucion_a_numero($v);
            if ($n !== null && $n > $limiteO2) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Convierte un valor de gases ("6.50*", "7.00", "", null) a float, o null si
 * no es numerico.
 */
function _dilucion_a_numero($valor)
{
    $valor = trim(str_replace('*', '', (string) $valor));
    if ($valor === '' || ! is_numeric($valor)) {
        return null;
    }
    return (float) $valor;
}
