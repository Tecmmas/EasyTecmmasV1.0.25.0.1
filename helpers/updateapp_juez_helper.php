<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Juez remoto: la configuración del juez (si está activo y desde qué fecha se exigen registros
 * autenticados) la entrega updateapp (Cpassword) por dominio. Se consulta desde PHP: no se lee de
 * system/oficina.json ni se acepta del navegador, así el CDA no puede apagarla ni moverle la fecha.
 */

if (!function_exists('updateapp_juez_config')) {
    /**
     * Devuelve ['ok' => bool, 'juez' => '0'|'1', 'fechaencript' => string|null].
     * Cachea en sesión 10 minutos. Si updateapp no responde usa el último valor cacheado (aunque esté vencido);
     * sin ninguno devuelve ok:false (el llamador decide el valor por defecto).
     */
    function updateapp_juez_config()
    {
        $CI    =& get_instance();
        $cache = $CI->session->userdata('juez_remoto');
        if (is_array($cache) && !empty($cache['hasta']) && (int) $cache['hasta'] > time()) {
            return $cache['datos'];
        }

        $dominio = trim((string) @file_get_contents(FCPATH . 'system/dominio.dat'));
        $datos   = array('ok' => false, 'juez' => '0', 'fechaencript' => null);

        if ($dominio !== '') {
            // file_get_contents + stream context (como las peticiones SICOV): no exige la extensión curl en el cliente
            $context = stream_context_create(array(
                'http' => array(
                    'method'        => 'POST',
                    'header'        => "Content-Type: application/json\r\n"
                        // Mismo encabezado (con su ortografía) y token que usaba el navegador
                        . "Autorization: eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.Ijg5NnNkYndmZTg3dmNzZGFmOTg0bmc4ZmdoMjRvMTI5MHIi.HraZ7y3eG3dGhKngzOWge-je8Y3lxZgldXjbRbcA7cA\r\n",
                    'content'       => json_encode(array('dominio' => $dominio, 'function' => 'getPassword')),
                    'timeout'       => 5,
                    'ignore_errors' => true,
                ),
            ));
            $body = @file_get_contents('http://updateapp.tecmmas.com/Actualizaciones/index.php/Cpassword', false, $context);

            $rta = ($body === false) ? null : json_decode($body, true);
            if (is_array($rta) && isset($rta[0]) && is_array($rta[0]) && array_key_exists('juez', $rta[0])) {
                $datos = array(
                    'ok'           => true,
                    'juez'         => ((string) $rta[0]['juez'] === '1') ? '1' : '0',
                    'fechaencript' => isset($rta[0]['fechaencript']) ? (string) $rta[0]['fechaencript'] : null,
                );
            }
        }

        if ($datos['ok']) {
            $CI->session->set_userdata('juez_remoto', array('hasta' => time() + 600, 'datos' => $datos));
        } elseif (is_array($cache) && !empty($cache['datos']['ok'])) {
            return $cache['datos'];
        }
        return $datos;
    }
}

if (!function_exists('updateapp_juez_activo')) {
    /** ¿El juez está activo para este CDA según updateapp? Sin respuesta ni caché: false (como el valor por defecto anterior). */
    function updateapp_juez_activo()
    {
        $c = updateapp_juez_config();
        return $c['ok'] && $c['juez'] === '1';
    }
}

if (!function_exists('updateapp_juez_fecha')) {
    /**
     * Fecha desde la cual se exigen registros autenticados (Y-m-d...). Si updateapp no la entrega o no es una
     * fecha válida devuelve $porDefecto (la más antigua = la más estricta).
     */
    function updateapp_juez_fecha($porDefecto)
    {
        $c = updateapp_juez_config();
        if ($c['ok'] && preg_match('/^\d{4}-\d{2}-\d{2}/', (string) $c['fechaencript'])) {
            return $c['fechaencript'];
        }
        return $porDefecto;
    }
}
