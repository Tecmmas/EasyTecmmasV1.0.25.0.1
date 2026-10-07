<?php
function validEspejo()
{
    $espejoDatabase = 0;
    $encrptopenssl = new Opensslencryptdecrypt();
    $json = $encrptopenssl->decrypt(file_get_contents('system/oficina.json', true), true);
    $ofc = json_decode($json, true);
    foreach ($ofc as $d) {
        if ($d['nombre'] == 'espejoDatabase') {
            $espejoDatabase = $d['valor'];
        }
    }

    if ($espejoDatabase == "1" || $espejoDatabase == 1) {
        // return $espejoDatabase;
        return $espejoDatabase;
    } else {
        return $espejoDatabase;
    }
}
