<?php

/**
 * Fecha máxima de primera revisión técnico-mecánica de un remolque / semirremolque.
 * Parágrafo transitorio del art. 3.3.12.7 de la Resolución 20263040030265 de 2026.
 *
 * Regla por año de la fecha de matrícula:
 *   - año <= 2010          -> 2027-05-31
 *   - 2011 a 2020          -> 2027-08-31
 *   - 2021 a 2025          -> 2027-12-31
 *   - año >= 2026          -> fecha_matricula + 2 años (art. 3.3.12.7, inciso 1)
 *   - sin fecha -> null. En este esquema `vehiculos.fecha_matricula` es DATE NOT NULL
 *     DEFAULT '1900-01-01', así que ese valor (y cualquier año <= 1900) es el
 *     centinela de "sin matrícula"; también '', null, '0000-00-00' y lo no parseable.
 *
 * Función pura: sin acceso a BD, sin estado. Solo usa la fecha recibida.
 *
 * @param string|null $fecha_matricula  Fecha de matrícula en formato 'Y-m-d' (o '' / null).
 * @return string|null  Fecha límite 'Y-m-d', o null si no se puede determinar.
 */
function fecha_limite_primera_revision_remolque($fecha_matricula)
{
    if (empty($fecha_matricula) || $fecha_matricula === '0000-00-00' || $fecha_matricula === '1900-01-01') {
        return null;
    }

    $ts = strtotime($fecha_matricula);
    if ($ts === false) {
        return null;
    }

    $anio = (int) date('Y', $ts);
    if ($anio <= 1900 || $anio > 2100) {
        return null;
    }

    if ($anio <= 2010) {
        return '2027-05-31';
    }
    if ($anio <= 2020) {
        return '2027-08-31';
    }
    if ($anio <= 2025) {
        return '2027-12-31';
    }

    return date('Y-m-d', strtotime('+2 years', $ts));
}
