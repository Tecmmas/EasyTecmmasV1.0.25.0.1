<?php

defined('BASEPATH') or exit('No direct script access allowed');

trait Trait_Indra
{
    /**
     * Resuelve el direccionamiento alternativo de SICOV: acepta host, host:puerto o URL completa
     * (con o sin puerto). Retorna ['url' => WSDL, 'host' => host para fsockopen, 'port' => puerto].
     */
    private function sicovEndpoint($valor, $rutaPorDefecto)
    {
        $valor = trim(strval($valor));

        if (preg_match('#^https?://#i', $valor)) {
            $p     = parse_url($valor);
            $https = strtolower($p['scheme']) === 'https';
            $host  = $p['host'] ?? '';
            $port  = $p['port'] ?? ($https ? 443 : 80);
            $url   = $p['scheme'] . '://' . $host . (isset($p['port']) ? ':' . $p['port'] : '') . ($p['path'] ?? '');
            $url  .= isset($p['query']) ? '?' . $p['query'] : '?WSDL';

            return ['url' => $url, 'host' => ($https ? 'ssl://' : '') . $host, 'port' => $port];
        }

        $datos = explode(':', $valor);

        return [
            'url'  => 'http://' . $valor . $rutaPorDefecto,
            'host' => $datos[0],
            'port' => count($datos) > 1 ? $datos[1] : 80,
        ];
    }

    private function insertarEvento($idelemento, $cadena, $tipo, $enviado, $respuesta)
    {
        $data['idelemento'] = $idelemento;
        $data['cadena']     = $cadena;
        $data['tipo']       = $tipo;
        $data['enviado']    = $enviado;
        $data['respuesta']  = $respuesta;
        // var_dump($data);
        $this->MEventosindra->insert($data);
    }

    private function setDatCi2($campo, $valor)
    {
        //        $dato = $this->formato_texto($valor);
        $dato = preg_replace('/[\n\r\t]+/', '', $this->formato_texto($valor));
        $dato = preg_replace('/(\d)\*/', '$1', $dato);
        if ($this->ci2CampoEsNumericoPrueba($campo)) {
            $numeroFormateado = $this->ci2DecimalPorMagnitud($valor);
            if ($numeroFormateado !== '') {
                $dato = $numeroFormateado;
            }
        }
        if ($this->segundo_envio) {
            switch ($campo) {
                case 'usuario':
                    break;
                case 'clave':
                    break;
                case 'pPin':
                    break;
                case 'p3Plac':
                    break;
                case 'peConRun':
                    break;
                case 'pTw01':
                    break;
                default:
                    $dato = '';
                    break;
            }
        }
        $this->arrayCi2[$campo] = $dato;
    }

    private function mensajesCI2($codigo, $detalle)
    {
        $msg = 'Código de respuesta no válido: Revise la conexión con CI2';
        switch ($codigo) {
            case '0000':
                $msg = 'Transacción exitosa|' . $detalle;
                break;
            case '1000':
                $msg = 'Transacción Fallida|' . $detalle;
                break;
            case '1001':
                $msg = 'Dato no puede ser nulo|' . $detalle;
                break;
            case '1002':
                $msg = 'Valor no válido|' . $detalle;
                break;
            case '1003':
                $msg = 'Formato no válido|' . $detalle;
                break;
            case '1004':
                $msg = 'Campo obligatorio|' . $detalle;
                break;
            case '1005':
                $msg = 'Longitud no permitida|' . $detalle;
                break;
            case '1006':
                $msg = 'Dato no existe|' . $detalle;
                break;
            case '2001':
                $msg = 'Usuario y clave no válidos|' . $detalle;
                break;
            case '2002':
                $msg = 'Usuario no permitido|' . $detalle;
                break;
            case '2003':
                $msg = 'CDA no permitido|' . $detalle;
                break;
            case '2004':
                $msg = 'Vehículo no permitido|' . $detalle;
                break;
            case '2005':
                $msg = 'PIN ANULADO|' . $detalle;
                break;
            case '2006':
                $msg = 'PIN DISPONIBLE|' . $detalle;
                break;
            case '2007':
                $msg = 'PIN UTILIZADO|' . $detalle;
                break;
            case '2008':
                $msg = 'PIN REPORTADO CON FUR|' . $detalle;
                break;
            case '2009':
                $msg = 'PIN NO VALIDO|' . $detalle;
                break;
            case '2010':
                $msg = 'Pendiente por procesar|' . $detalle;
                break;
            case '2011':
                $msg = 'La solicitud está pendiente por reportar resultado|' . $detalle;
                break;
            case '2012':
                $msg = 'Código RUNT ya está registrado|' . $detalle;
                break;
        }
        return $msg;
    }

    //______________________________________________________________________________TRAMA INDRA
    private function buildIndra($data)
    {
        //------------------------------------------------------------------------------IdProveedor
        $IdProveedor = "862";
        //------------------------------------------------------------------------------Propietario
        switch ($data['propietario']->tipo_identificacion) {
            case '1':
                $tipoDocumento = "C";
                break;
            case '2':
                $tipoDocumento = "N";
                break;
            case '3':
                $tipoDocumento = "E";
                break;
            case '4':
                $tipoDocumento = "T";
                break;
            case '5':
                $tipoDocumento = "U";
                break;
            case '6':
                $tipoDocumento = "P";
                break;
        }


        $data['propietario']->correo = $data['propietario']->correo ?: "dato@gmail.com";
        $propietario = rtrim($data['propietario']->nombre1) . "" .
        rtrim($data['propietario']->nombre2) . "" .
        rtrim($data['propietario']->apellido1) . "" .
        rtrim($data['propietario']->apellido2) . ";" .
        $tipoDocumento . ";" .
        $data['propietario']->numero_identificacion . ";" .
        $data['propietario']->direccion . ";" .
        $data['propietario']->telefono1 . ";" .
        rtrim($data['ciudadPropietario']->nombre) . ";" .
        rtrim($data['departamentoPropietario']->nombre) . ";" .
        $data['propietario']->correo;
        //------------------------------------------------------------------------------Vehiculos old
        if ($data['vehiculo']->idservicio == 3) {
            $servicio = 1;
        } else if ($data['vehiculo']->idservicio == 4) {
            $servicio = 3;
        } else if ($data['vehiculo']->idservicio == 1) {
            $servicio = 4;
        } else {
            $servicio = $data['vehiculo']->idservicio;
        }

        if ($data['vehiculo']->idclase == 13) {
            $clase = 41;
        } else if ($data['vehiculo']->idclase == 16) {
            $clase = 43;
        } else if ($data['vehiculo']->idclase == 9) {
            $clase = 42;
        } else if ($data['vehiculo']->idclase == 15) {
            $clase = 24;
        } else {
            $clase = $data['vehiculo']->idclase;
        }

        if ($data['vehiculo']->idtipocombustible == 1) {
            $combustible = 3;
        } else if ($data['vehiculo']->idtipocombustible == 2) {
            $combustible = 1;
        } else if ($data['vehiculo']->idtipocombustible == 3) {
            $combustible = 2;
        } else {
            $combustible = $data['vehiculo']->idtipocombustible;
        }
        
        if ($data['vehiculo']->tiempos == 2 && $data['vehiculo']->tipo_vehiculo == 3) {
            $tiempos = 1;
        } else if ($data['vehiculo']->tiempos == 4 && $data['vehiculo']->tipo_vehiculo == 3) {
            $tiempos = 2;
        } else if ($data['vehiculo']->idtipocombustible == 2 && $data['vehiculo']->tipo_vehiculo != 3) {
            $tiempos = 3;
        } else if ($data['vehiculo']->idtipocombustible == 1 && $data['vehiculo']->tipo_vehiculo != 3) {
            $tiempos = 4;
        } else if ($data['vehiculo']->idtipocombustible == 5 && $data['vehiculo']->tipo_vehiculo != 3) {
            $tiempos = 5;
        } else {
            $tiempos = 6;
        }

        if ($data['vehiculo']->blindaje == "1") {
            $blindaje = "true";
        } else {
            $blindaje = "false";
        }
        if ($data['apro'] == 'APROBADO: SI__X__ NO_____') {
            $aprobado = "1";
        } else {
            $aprobado = "2";
        }
        switch ($data['vehiculo']->certificadoGas) {
            case 'SI ( ) NO ( ) N/A (X)':
                $conversionGas = "NO APLICA";
                break;
            case 'SI ( ) NO (X) N/A ( )':
                $conversionGas = "N";
                break;
            case 'SI (X) NO ( ) N/A ( )':
                $conversionGas = "S";
                break;
            default:
                $conversionGas = "N0 APLICA";
                break;
        }
        $fechacer = '';
        if ($conversionGas == 'N' || $conversionGas == 'NO APLICA') {
            $fechacer = '';
        } else {
            $fechacer = date_format(date_create($data['vehiculo']->fecha_final_certgas), 'Y-m-d');
        }

        
      

        // if ($data['vehiculo']->kilometraje === 'NO FUNCIONAL') {
        //     $data['vehiculo']->kilometraje = "0";
        // }
        if ($data['vehiculo']->kilometraje === '0') {
            $data['vehiculo']->kilometraje = "NO FUNCIONAL";
        }

        // if ($data['vehiculo']->potencia_motor === 'No aplica') {
        //     $data['vehiculo']->potencia_motor = "0";
        // }
        if ($data['vehiculo']->potencia_motor === 'No aplica') {
            $data['vehiculo']->potencia_motor = "";
        }
        $data['vehiculo']->usuario_registro = trim(preg_replace('/\s+/', ' ', $data['vehiculo']->usuario_registro));
        $vehiculo = $data['vehiculo']->usuario_registro . ";" .
        $data['vehiculo']->documento_usuario . ";" .
        $data['vehiculo']->numero_placa . ";" .
        $data['pais']->nombre . ";" .
        $servicio . ";" .
        $clase . ";" .
        $data['marca']->nombre . ";" .
        strtoupper($data['linea']->nombre) . ";" .
        $data['vehiculo']->ano_modelo . ";" .
        $data['vehiculo']->numero_tarjeta_propiedad . ";" .
        $data['vehiculo']->fecha_matricula . ";" .
        
        $data['color']->nombre . ";" .
        $combustible . ";" .
        $data['vehiculo']->numero_vin . ";" .
        $data['vehiculo']->numero_motor . ";" .
        $tiempos . ";" .
        $data['vehiculo']->cilindraje . ";" .
        $data['vehiculo']->kilometraje . ";" .
        $data['pasajeros'] . ";" .
        $blindaje . ";" .
        $data['ocasion'] . ";" .
        date_format(date_create($data['fechafur']), 'Y-m-d H:i:s') . ";" .
        $aprobado . ";" .
        $data['vehiculo']->potencia_motor . ";" .
        $data['vehiculo']->diseno . ";" .
        date_format(date_create($data['vehiculo']->fecha_vencimiento_soat), 'Y-m-d') . ";" .
            $conversionGas . ";" .
            $fechacer .';'.
            $peso = 200;

        //------------------------------------------------------------------------------Fotos
        $fotos = $data['vehiculo']->numero_placa . ";" .
        str_replace("@", "", $data['fotografia']->imagen1) . ";" .
        str_replace("@", "", $data['fotografia']->imagen2);
        //------------------------------------------------------------------------------Gases
        $diesel     = "";
        $gasesCarro = "";
        $gasesMoto  = "";
        if ($combustible == 3) {
            $data['opacidad']->operario = trim(preg_replace('/\s+/', ' ', $data['opacidad']->operario));
            $diesel = $data['opacidad']->operario . ";" .
            $data['opacidad']->documento . ";" .
            $data['opacidad']->temp_ambiente . ";" .
            $data['opacidad']->rpm_ralenti . ";" .
            $data['opacidad']->rpm_ciclo1 . ";" .
            $data['opacidad']->rpm_ciclo2 . ";" .
            $data['opacidad']->rpm_ciclo3 . ";" .
            $data['opacidad']->rpm_ciclo4 . ";" .
            $data['opacidad']->op_ciclo1N . ";" .
            $data['opacidad']->op_ciclo2N . ";" .
            $data['opacidad']->op_ciclo3N . ";" .
            $data['opacidad']->op_ciclo4N . ";" .
            $data['opacidad']->opacidad_totalN . ";" .
            $data['opacidad']->temp_inicial . ";" .
            $data['opacidad']->temp_final . ";" .
            $data['opacidad']->humedad . ";" .
            $data['vehiculo']->diametro_escape . ";" .
            $data['opacidad']->op_ciclo1 . ";" .
            $data['opacidad']->op_ciclo2 . ";" .
            $data['opacidad']->op_ciclo3 . ";" .
            $data['opacidad']->op_ciclo4 . ";" .
            $data['opacidad']->opacidad_total . "|" . //cambiar
            $data['opacidad']->fugasTuboEscape . ";" .
            $data['opacidad']->fugasSilenciador . ";" .
            $data['opacidad']->tapaCombustible . ";" .
            $data['opacidad']->tapaAceite . ";" .
            $data['opacidad']->sistemaMuestreo . ";" .
            $data['opacidad']->salidasAdicionales . ";" .
            $data['opacidad']->filtroAire . ";" .
            $data['opacidad']->sistemaRefrigeracion . ";" .
            $data['opacidad']->revolucionesFueraRango . "|";
            $diesel = str_replace(".", ",", str_replace("*", "", $diesel));
        } elseif ($combustible == 1 || $combustible == 2 || $combustible == 4) {
            if ($data['vehiculo']->tipo_vehiculo !== "3") {
                $data['gases']->operario = trim(preg_replace('/\s+/', ' ', $data['gases']->operario));
                $gasesCarro = $data['gases']->operario . "';" .
                $data['gases']->documento . ";" .
                $data['gases']->rpm_ralenti . ";" .
                $data['gases']->hc_ralenti . ";" .
                $data['gases']->co_ralenti . ";" .
                $data['gases']->co2_ralenti . ";" .
                $data['gases']->o2_ralenti . ";" .
                $data['gases']->rpm_crucero . ";" .
                $data['gases']->hc_crucero . ";" .
                $data['gases']->co_crucero . ";" .
                $data['gases']->co2_crucero . ";" .
                $data['gases']->o2_crucero . ";" .
                $data['gases']->dilusion . ";" .
                substr($data['vehiculo']->convertidorCat, 0, 1) . ";" .
                $data['gases']->temperatura . ";" .
                $data['gases']->temperatura_ambiente . ";" .
                $data['gases']->humedad . "|" .
                $data['gases']->fugasTuboEscape . ";" .
                $data['gases']->fugasSilenciador . ";" .
                $data['gases']->tapaCombustible . ";" .
                $data['gases']->tapaAceite . ";" .
                $data['gases']->salidasAdicionales . ";" .
                $data['gases']->presenciaHumos . ";" .
                $data['gases']->revolucionesFueraRango . ";" .
                $data['gases']->fallaSistemaRefrigeracion . "|";
                $gasesCarro = str_replace(".", ",", str_replace("*", "", $gasesCarro));
            } else {
                if ($data['gases']->temperatura == "¨0¨") {
                    $data['gases']->temperatura = "0.00";
                }
                $data['gases']->operario = trim(preg_replace('/\s+/', ' ', $data['gases']->operario));
                $gasesMoto = $data['gases']->operario . "';" .
                $data['gases']->documento . ";" .
                $data['gases']->temperatura . ";" .
                $data['gases']->rpm_ralenti . ";" .
                $data['gases']->hc_ralenti . ";" .
                $data['gases']->co_ralenti . ";" .
                $data['gases']->co2_ralenti . ";" .
                $data['gases']->o2_ralenti . ";" .
                $data['gases']->temperatura_ambiente . ";" .
                $data['gases']->humedad . "|" .
                $data['gases']->revolucionesFueraRango . ";" .
                $data['gases']->fugasTuboEscape . ";" .
                $data['gases']->fugasSilenciador . ";" .
                $data['gases']->tapaCombustible . ";" .
                $data['gases']->tapaAceite . ";" .
                $data['gases']->salidasAdicionales . ";" .
                $data['gases']->presenciaHumos . "|";
                $gasesMoto = str_replace(".", ",", str_replace("*", "", $gasesMoto));
            }
        } else {
            $diesel     = "||";
            $gasesCarro = "";
            $gasesMoto  = "";
        }
        //------------------------------------------------------------------------------Luces
        if(substr($data['luces']->simultaneaBaja, 0, 1) == ""){
            $simultaneaBaja = "N";
        }else{
            $simultaneaBaja = substr($data['luces']->simultaneaBaja, 0, 1);
            
        }
        //Cambio de envio de luces al lado izquiero
        if($data['vehiculo']->tipo_vehiculo == "3"){
            $data['luces']->valor_baja_izquierda_1  = $data['luces']->valor_baja_derecha_1;
            $data['luces']->valor_baja_derecha_1 = "";
            $data['luces']->inclinacion_baja_izquierda_1 = $data['luces']->inclinacion_baja_derecha_1;
            $data['luces']->inclinacion_baja_derecha_1 = "";
        } 
        $data['luces']->operario = trim(preg_replace('/\s+/', ' ', $data['luces']->operario));
        $luces = $data['luces']->operario . ";" .
        $data['luces']->documento . ";" .
        $data['luces']->valor_baja_derecha_1  . ";" .
        $data['luces']->valor_baja_derecha_2 . ";" .
        $data['luces']->valor_baja_derecha_3 . ";" .
        $simultaneaBaja . ";" .
        $data['luces']->valor_baja_izquierda_1 . ";" .
        $data['luces']->valor_baja_izquierda_2 . ";" .
        $data['luces']->valor_baja_izquierda_3 . ";" .
        $simultaneaBaja . ";" .
        $data['luces']->inclinacion_baja_derecha_1 . ";" .
        $data['luces']->inclinacion_baja_derecha_2 . ";" .
        $data['luces']->inclinacion_baja_derecha_3 . ";" .
        $data['luces']->inclinacion_baja_izquierda_1  . ";" .
        $data['luces']->inclinacion_baja_izquierda_2 . ";" .
        $data['luces']->inclinacion_baja_izquierda_3 . ";" .
        $data['luces']->intensidad_total . ";" .
        $data['luces']->valor_alta_derecha_1 . ";" .
        $data['luces']->valor_alta_derecha_2 . ";" .
        $data['luces']->valor_alta_derecha_3 . ";" .
        substr($data['luces']->simultaneaAlta, 0, 1) . ";" .
        $data['luces']->valor_alta_izquierda_1 . ";" .
        $data['luces']->valor_alta_izquierda_2 . ";" .
        $data['luces']->valor_alta_izquierda_3 . ";" .
        substr($data['luces']->simultaneaAlta, 0, 1) . ";" .
        $data['luces']->valor_antiniebla_derecha_1 . ";" .
        $data['luces']->valor_antiniebla_derecha_2 . ";" .
        $data['luces']->valor_antiniebla_derecha_3 . ";" .
        substr($data['luces']->simultaneaAntiniebla, 0, 1) . ";" .
        $data['luces']->valor_antiniebla_izquierda_1 . ";" .
        $data['luces']->valor_antiniebla_izquierda_2 . ";" .
        $data['luces']->valor_antiniebla_izquierda_3 . ";" .
        substr($data['luces']->simultaneaAntiniebla, 0, 1);
        $luces = str_replace(".", ",", str_replace("*", "", $luces));

    
        //------------------------------------------------------------------------------FAS

          //Cambio de envio de luces al lado izquiero
        if($data['vehiculo']->tipo_vehiculo == "3"){
            $data['frenos']->freno_1_izquierdo  = $data['frenos']->freno_1_derecho;
            $data['frenos']->freno_1_derecho = "";
            $data['frenos']->freno_2_izquierdo  = $data['frenos']->freno_2_derecho;
            $data['frenos']->freno_2_derecho = "";
            $data['frenos']->peso_1_izquierdo  = $data['frenos']->peso_1_derecho;
            $data['frenos']->peso_1_derecho = "";
            $data['frenos']->peso_2_izquierdo  = $data['frenos']->peso_2_derecho;
            $data['frenos']->peso_2_derecho = "";
            
        } 
        $data['frenos']->operario = trim(preg_replace('/\s+/', ' ', $data['frenos']->operario));
        $fas = $data['frenos']->operario . ";" .
        $data['frenos']->documento . ";" .
        $data['vehiculo']->numejes . ";" .
        $data['frenos']->eficacia_total . ";" .
        $data['frenos']->eficacia_auxiliar . ";" .
        $data['frenos']->desequilibrio_1 . ";" .
        $data['frenos']->desequilibrio_2 . ";" .
        $data['frenos']->desequilibrio_3 . ";" .
        $data['frenos']->desequilibrio_4 . ";" .
        $data['frenos']->desequilibrio_5 . ";" .
        '' . ";" .
        $data['frenos']->freno_1_izquierdo . ";" .
        $data['frenos']->freno_2_izquierdo . ";" .
        $data['frenos']->freno_3_izquierdo . ";" .
        $data['frenos']->freno_4_izquierdo . ";" .
        $data['frenos']->freno_5_izquierdo . ";" .
        '' . ";" .
        $data['frenos']->freno_1_derecho . ";" .
        $data['frenos']->freno_2_derecho . ";" .
        $data['frenos']->freno_3_derecho . ";" .
        $data['frenos']->freno_4_derecho . ";" .
        $data['frenos']->freno_5_derecho . ";" .
        '' . ";" .
        $data['frenos']->peso_1_derecho . ";" .
        $data['frenos']->peso_2_derecho . ";" .
        $data['frenos']->peso_3_derecho . ";" .
        $data['frenos']->peso_4_derecho . ";" .
        $data['frenos']->peso_5_derecho . ";" .
        '' . ";" .
        $data['frenos']->peso_1_izquierdo . ";" .
        $data['frenos']->peso_2_izquierdo . ";" .
        $data['frenos']->peso_3_izquierdo . ";" .
        $data['frenos']->peso_4_izquierdo . ";" .
        $data['frenos']->peso_5_izquierdo . ";" .
        '' . ";" .
        $data['alineacion']->alineacion_1 . ";" .
        $data['alineacion']->alineacion_2 . ";" .
        $data['alineacion']->alineacion_3 . ";" .
        $data['alineacion']->alineacion_4 . ";" .
        $data['alineacion']->alineacion_5 . ";" .
        '' . ";" .
        $data['suspension']->delantera_izquierda . ";" .
        $data['suspension']->trasera_izquierda . ";" .
        $data['suspension']->delantera_derecha . ";" .
        $data['suspension']->trasera_derecha . ";" .
        $data['frenos']->sum_freno_aux_derecho . ";" .
        $data['frenos']->sum_peso_derecho . ";" .
        $data['frenos']->sum_freno_aux_izquierdo . ";" .
        $data['frenos']->sum_peso_izquierdo;
        $fas = str_replace(".", ",", str_replace("*", "", $fas));
        //------------------------------------------------------------------------------Sensorial
        $defectos = "";
        if (count($data['defectosMecanizadosA']) > 0) {
            foreach ($data['defectosMecanizadosA'] as $def) {
                $defectos = $defectos . $def->codigo . "_";
            }
        }
        if (count($data['defectosMecanizadosB']) > 0) {
            foreach ($data['defectosMecanizadosB'] as $def) {
                $defectos = $defectos . $def->codigo . "_";
            }
        }
        if (count($data['defectosSensorialesA']) > 0) {
            foreach ($data['defectosSensorialesA'] as $def) {
                $defectos = $defectos . $def->codigo . "_";
            }
        }
        if (count($data['defectosSensorialesB']) > 0) {
            foreach ($data['defectosSensorialesB'] as $def) {
                $defectos = $defectos . $def->codigo . "_";
            }
        }
        if (count($data['defectosEnsenanzaA']) > 0) {
            foreach ($data['defectosEnsenanzaA'] as $def) {
                $defectos = $defectos . $def->codigo . "_";
            }
        }
        if (count($data['defectosEnsenanzaB']) > 0) {
            foreach ($data['defectosEnsenanzaB'] as $def) {
                $defectos = $defectos . $def->codigo . "_";
            }
        }

        if (strlen($defectos) > 0) {
            $defectos = substr($defectos, 0, strlen($defectos) - 1);
        }
        $data['sensorial']->operario = trim(preg_replace('/\s+/', ' ', $data['sensorial']->operario));
        $sensorial = $data['sensorial']->operario . ";" .
        $data['sensorial']->documento . ";" .
            $defectos;
        //------------------------------------------------------------------------------Taximetro
        $data['taximetro']->operario = trim(preg_replace('/\s+/', ' ', $data['taximetro']->operario));
        $taximetro = $data['taximetro']->operario . ";" .
        $data['taximetro']->documento . ";" .
        $data['taximetro']->aplicaTaximetro . ";" .
        $data['taximetro']->tieneTaximetro . ";" .
        $data['taximetro']->taximetroVisible . ";" .
        $data['taximetro']->r_llanta . ";" .
        $data['taximetro']->distancia . ";" .
        $data['taximetro']->tiempo;
        $taximetro = str_replace(".", ",", str_replace("*", "", $taximetro));
        //------------------------------------------------------------------------------Observaciones
        // $observaciones = '';
        // if (count($data['observaciones']) > 0) {
        //     foreach ($data['observaciones'] as $o) {
        //         $observaciones = $observaciones . "$o->codigo: $o->descripcion" . "_";
        //     }
        // }
        $observaciones = [];
            if (count($data['observaciones']) > 0) {
                foreach ($data['observaciones'] as $o) {
                    $o->descripcion = trim(preg_replace('/\s+/', ' ', $o->descripcion));
                    $observaciones[] = "$o->codigo: $o->descripcion";
                }
            }
            $observaciones = json_encode($observaciones, JSON_UNESCAPED_UNICODE);

        //  var_dump($observaciones);
        //------------------------------------------------------------------------------Certificado
        $certificado = $data['fur_aso'] . ";" .
        // $this->idCdaRUNT;
        $this->idCdaRUNT;
        
        //        $certificado = $data['numero_sustrato'] . ";" .
        //                $data['numero_consecutivo'] . ";" .
        //                $data['fur_aso'] . ";" .
        //                $this->idCdaRUNT;
        //------------------------------------------------------------------------------Estructura de llantas
        // var_dump($data['labrado']);
   //Cambio de envio de luces al lado izquiero
        if($data['vehiculo']->tipo_vehiculo == "3"){
            $data['labrado']->eje1_izquierdo  = $data['labrado']->eje1_derecho;
            $data['labrado']->eje1_derecho = "";
            $data['labrado']->eje2_izquierdo  = $data['labrado']->eje2_derecho;
            $data['labrado']->eje2_derecho = "";
             $this->llanta_1_I =  $this->llanta_1_D;
             $this->llanta_1_D = "";
             $this->llanta_2_IE = $this->llanta_2_DE;
            $this->llanta_2_DE = "";
        } 
        $data['sensorial']->operario = trim(preg_replace('/\s+/', ' ', $data['sensorial']->operario));
        $llantas = $data['sensorial']->operario . ";" .
        $data['sensorial']->documento . ";" .
        $data['labrado']->eje1_derecho . ";" .
        $data['labrado']->eje2_derecho . ";" .
        $data['labrado']->eje3_derecho . ";" .
        $data['labrado']->eje4_derecho . ";" .
        $data['labrado']->eje5_derecho . ";" .
        $data['labrado']->eje1_izquierdo . ";" .
        $data['labrado']->eje2_izquierdo . ";" .
        $data['labrado']->eje3_izquierdo . ";" .
        $data['labrado']->eje4_izquierdo . ";" .
        $data['labrado']->eje5_izquierdo . ";" .
        $data['labrado']->eje2_derecho_interior . ";" .
        $data['labrado']->eje3_derecho_interior . ";" .
        $data['labrado']->eje4_derecho_interior . ";" .
        $data['labrado']->eje5_derecho_interior . ";" .
        $data['labrado']->eje2_izquierdo_interior . ";" .
        $data['labrado']->eje3_izquierdo_interior . ";" .
        $data['labrado']->eje4_izquierdo_interior . ";" .
        $data['labrado']->eje5_izquierdo_interior . ";" .
        $data['labrado']->repuesto . ";" .
        $data['labrado']->repuesto2 . ";" .
        $this->rdnr($this->llanta_1_D ). ";" .
        $this->rdnr($this->llanta_2_DE) .";" .
        $this->rdnr($this->llanta_3_DE ). ";" .
        $this->rdnr($this->llanta_4_DE ). ";" .
        $this->rdnr($this->llanta_5_DE ). ";" .
        $this->rdnr($this->llanta_1_I) . ";" .
        $this->rdnr($this->llanta_2_IE ). ";" .
        $this->rdnr($this->llanta_3_IE) . ";" .
        $this->rdnr($this->llanta_4_IE) . ";" .
        $this->rdnr($this->llanta_5_IE) . ";" .
        $this->rdnr($this->llanta_2_DI) . ";" .
        $this->rdnr($this->llanta_3_DI) . ";" .
        $this->rdnr($this->llanta_4_DI) . ";" .
        $this->rdnr($this->llanta_5_DI) . ";" .
        $this->rdnr($this->llanta_2_II) . ";" .
        $this->rdnr($this->llanta_3_II) . ";" .
        $this->rdnr($this->llanta_4_II) . ";" .
        $this->rdnr($this->llanta_5_II) . ";" .
        $this->rdnr($this->llanta_R) . ";" .
        $this->rdnr($this->llanta_R2);

        $llantas =  str_replace(".", ",", str_replace("*", "", $llantas));

        // var_dump($this->llanta_5_IE);
        // var_dump($this->llanta_5_DI);

       

        
        //------------------------------------------------------------------------------Máquinas
        $luxometro       = "";
        $opacimetro      = "";
        $analizador      = "";
        $camara          = "";
        $taximetroMaq    = "";
        $frenometro      = "";
        $bascula         = "";
        $suspension      = "";
        $alineador       = "";
        $termohigrometro = "";
        $profundimetro   = "";
        $captador        = "";
        $pierey          = "";
        $elevador        = "";
        $detector        = "";
        $sensorRPM       = "";
        $sondaTMP        = "";

        if ($data["maquinas"]->nombreLuxometro !== '') {
            $maq       = explode("$", $data["maquinas"]->nombreLuxometro);

            $luxometro = <<<EOF
                {"nombre": "LUXOMETRO","marca": "$maq[1]","noserie": "$maq[2]",
                "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "2"},
EOF;
            $luxometro = strtoupper($luxometro);
        }
        if ($data["maquinas"]->nombreOpacimetro !== '') {
            $maq        = explode("$", $data["maquinas"]->nombreOpacimetro);
            $opacimetro = <<<EOF
                {"nombre": "OPACIMETRO","marca": "$maq[1]","noserie": "$maq[2]",
                "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "1"},
EOF;
            $opacimetro = strtoupper($opacimetro);
        }

        if ($data["maquinas"]->nombreGases !== '') {
        $maq = explode("$", $data["maquinas"]->nombreGases);
        
        // Validar si tiene 3 dígitos decimales
        if (preg_match('/\.\d{3}$/', $maq[4])) {
            $maq[4] = substr($maq[4], 0, -1); // Quitar último dígito
        }
        
        $maq[4] = str_replace('.', ',', $maq[4]);
        
        $analizador = <<<EOF
            {"nombre": "ANALIZADOR DE GASES","marca": "$maq[1]","noserie": "$maq[2]",
            "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
            "Esperiferico": "N","Prueba": "1"},
EOF;
        $analizador = strtoupper($analizador);
    }
//         if ($data["maquinas"]->nombreGases !== '') {
//             $maq        = explode("$", $data["maquinas"]->nombreGases);
//            $maq[4] = substr($maq[4], 0, -1);
//             $maq[4] = str_replace('.', ',', $maq[4]);
//             $analizador = <<<EOF
//                 {"nombre": "ANALIZADOR DE GASES","marca": "$maq[1]","noserie": "$maq[2]",
//                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
//                 "Esperiferico": "N","Prueba": "1"},
// EOF;
//             $analizador = strtoupper($analizador);
//         }
        
//         if ($data["maquinas"]->nombreFotos !== '') {
//             $maq    = explode("$", $data["maquinas"]->nombreFotos);
//             $camara = <<<EOF
//                 {"nombre": "$maq[0]","marca": "$maq[1]","noserie": "$maq[2]",
//                  "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
//                 "Esperiferico": "$maq[7]","Prueba": "4"},
// EOF;
//             $camara = strtoupper($camara);

//         }
        if ($data["maquinas"]->nombreTaximetro !== '') {
            $maq          = explode("$", $data["maquinas"]->nombreTaximetro);
            $taximetroMaq = <<<EOF
                {"nombre": "TAXIMETRO","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "5"},
EOF;
$taximetroMaq = strtoupper($taximetroMaq);
        }
        if ($data["maquinas"]->nombreFrenos !== '') {
            $maq        = explode("$", $data["maquinas"]->nombreFrenos);
            $frenometro = <<<EOF
                {"nombre": "FRENOMETRO","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "6"},
EOF;
            $frenometro = strtoupper($frenometro);
        }

//         if ($data["maquinas"]->nombreBascula !== '') {
//             $maq     = explode("$", $data["maquinas"]->nombreBascula);
//             $bascula = <<<EOF
//                 {"nombre": "$maq[0]","marca": "$maq[1]","noserie": "$maq[2]",
//                  "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
//                 "Esperiferico": "$maq[7]","Prueba": "6"},
// EOF;
//                 $bascula = strtoupper($bascula);
//         }
        if ($data["maquinas"]->nombreSuspension !== '') {
            $maq        = explode("$", $data["maquinas"]->nombreSuspension);
            $suspension = <<<EOF
                {"nombre": "BANCO DE SUSPENSION","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "8"},
EOF;
                $suspension = strtoupper($suspension);
        }
        if ($data["maquinas"]->nombreAlineador !== '') {
            $maq       = explode("$", $data["maquinas"]->nombreAlineador);
            $alineador = <<<EOF
                {"nombre": "MEDIDOR DE DESVIACION LATERAL","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "7"},
EOF;
                $alineador = strtoupper($alineador);
        }
        if ($data["maquinas"]->nombreTermohigrometro !== '') {
            $maq             = explode("$", $data["maquinas"]->nombreTermohigrometro);
            $termohigrometro = <<<EOF
                {"nombre": "TERMOHIGROMETRO","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "1"},
EOF;
                $termohigrometro = strtoupper($termohigrometro);
        }
        if ($data["maquinas"]->nombreProfundimetro !== '') {
            $maq           = explode("$", $data["maquinas"]->nombreProfundimetro);
            $profundimetro = <<<EOF
                {"nombre": "PROFUNDIMETRO","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "8"},
EOF;
                $profundimetro = strtoupper($profundimetro);
        }
        if ($data["maquinas"]->nombreCaptador !== '') {
            $maq      = explode("$", $data["maquinas"]->nombreCaptador);
            $captador = <<<EOF
                {"nombre": "MEDIDOR DE REVOLUCIONES","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "1"},
EOF;
                $captador = strtoupper($captador);
        }
        if ($data["maquinas"]->nombreDetector !== '') {
            $maq      = explode("$", $data["maquinas"]->nombreDetector);
            $detector = <<<EOF
                {"nombre": "DETECTOR DE HOLGURAS","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "8"},
EOF;
                $detector = strtoupper($detector);
        }
        if ($data["maquinas"]->nombreElevador !== '') {
            $maq      = explode("$", $data["maquinas"]->nombreElevador);
            $elevador = <<<EOF
                {"nombre": "ELEVADOR DE MOTOS","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "N","Prueba": "8"},
EOF;
                $elevador = strtoupper($elevador);
        }
//         if ($data["maquinas"]->nombrePiederey !== '') {
//             $maq    = explode("$", $data["maquinas"]->nombrePiederey);
//             $pierey = <<<EOF
//                 {"nombre": "$maq[0]","marca": "$maq[1]","noserie": "$maq[2]",
//                  "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
//                 "Esperiferico": "$maq[7]","Prueba": "8"},
// EOF;
//                 $pierey = strtoupper($pierey);
//         }
        if ($data["maquinas"]->nombreSensorRPM !== '') {
            $maq       = explode("$", $data["maquinas"]->nombreSensorRPM);
            $sensorRPM = <<<EOF
                {"nombre": "MEDIDOR DE REVOLUCIONES","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "S","Prueba": "8"},
EOF;
                $sensorRPM = strtoupper($sensorRPM);
        }
        if ($data["maquinas"]->nombreSondaTMP !== '') {
            $maq      = explode("$", $data["maquinas"]->nombreSondaTMP);
            $sondaTMP = <<<EOF
                {"nombre": "MEDIDOR DE TEMPERATURA","marca": "$maq[1]","noserie": "$maq[2]",
                 "pef": "$maq[4]","ltoe": "$maq[5]","NoSerieBench": "$maq[6]",
                "Esperiferico": "S","Prueba": "8"},
EOF;
                $sondaTMP = strtoupper($sondaTMP);
        }

        $maquinas = <<<EOF
{"Equipos": [$luxometro$opacimetro$analizador$taximetroMaq$frenometro$bascula$suspension$alineador$termohigrometro$profundimetro$captador$pierey$elevador$detector$sensorRPM$sondaTMP]}
EOF;
        //        $maquinas = <<<EOF
        //{"Equipos": [$luxometro$opacimetro$analizador$camara$taximetroMaq$frenometro$suspension$alineador$termohigrometro$profundimetro$captador$pierey$elevador$detector$sensorRPM$sondaTMP]}
        //EOF;
        // $maquinas = trim(preg_replace('/\s+/', ' ', $maquinas));
        // $maquinas = str_replace(",]}", "]}", $maquinas);
       $maquinas = preg_replace('/\s+/', ' ', trim($maquinas));
        // echo $maquinas;

        //------------------------------------------------------------------------------Software
        $software = str_replace(' ', '', strtoupper($data["software"]));
        //------------------------------------------------------------------------------Jefe linea
        $jefeLinea = $data['hojatrabajo']->jefelinea . ";" . $this->Musuarios->getXnombreID($data['hojatrabajo']->jefelinea);
        // var_dump($jefeLinea);
         $jefeLinea = str_replace(' ', '', strtoupper($jefeLinea));
         $propietario = str_replace(' ', '', strtoupper($propietario));

        // var_dump($vehiculo);
        
                     //------------------------------------------------------------------------------Numero fur
        $taxonomia = //
        $IdProveedor . "|" .
        $propietario . "|" .
        $vehiculo . "|" .
        $fotos . "|" .
        $diesel . $gasesCarro . $gasesMoto .
        $luces . "|" .
        $fas . "|" .
        $sensorial . "|" .
        $taximetro . "|" .
        $observaciones . "|" .
        $certificado . "|" .
        $llantas . "|" .
        $maquinas . "|" .
        $software . "|" .
        $jefeLinea . "|" .
        $data['fur_aso'] . ";" . trim(date_format(date_create($data['fechafur']), 'Y-m-d H:i:s'));
        $vehiculo_array = explode(';', $fotos);

// Imprimir como array
//  var_dump($vehiculo_array);

        if ($data['ocasion'] === 'true') {
            $reins   = '1';
            $ocasion = '2';
        } else {
            $reins   = '0';
            $ocasion = '1';
        }

            //  var_dump( preg_replace('/[\n\r\t]+/', '', $this->formato_texto($taxonomia)));


        $url            = 'http://' . $this->ipSicov . '/sicov.asmx?WSDL';
        $datos_conexion = explode(":", $this->ipSicov);
        $location       = null;
        if ($this->sicovModoAlternativo == '1' && $this->ipSicovAlternativo != '') {
            $ep             = $this->sicovEndpoint($this->ipSicovAlternativo, '/sicov.asmx?WSDL');
            $url            = $ep['url'];
            $datos_conexion = [$ep['host'], $ep['port']];
            // Con URL completa se fuerza el destino de la llamada SOAP (sin ?WSDL), sin depender del soap:address del WSDL
            if (preg_match('#^https?://#i', trim($this->ipSicovAlternativo))) {
                $location = preg_replace('/\?wsdl$/i', '', $url);
            }
        }
        $host = $datos_conexion[0];
        if (count($datos_conexion) > 1) {
            $port = $datos_conexion[1];
        } else {
            $port = 80;
        }
        $waitTimeoutInSeconds = 2;
        error_reporting(0);
        if ($fp = fsockopen($host, $port, $errCode, $errStr, $waitTimeoutInSeconds)) {
            $context = stream_context_create([
                'http' => [
                    'header' => "X-Service-Version: 19\r\n"
                ]
            ]);
            $opcionesSoap = [
                'stream_context' => $context,
                'trace' => true
            ];
            if ($location) {
                $opcionesSoap['location'] = $location;
            }
            $client                 = new SoapClient($url, $opcionesSoap);
            // $header = new SoapHeader('http://tempuri.org/', 'X-Service-Version', '19');
            // $client->__setSoapHeaders($header);
            $datos['idhojapruebas'] = $data['idhojapruebas'];
            $datos['reinspeccion']  = $reins;
            $msg                    = '';
            $encrptopenssl          = new Opensslencryptdecrypt();
            if (! $this->segundo_envio) {

                $this->sistemaOperativo = sistemaoperativo();

                // var_dump('indra');
                if ($this->sistemaOperativo == null || $this->sistemaOperativo == "") {
                    // var_dump('por null o vacio');
                    $key = "v239pShjXXXXXXXXXXXXXXXXXXXXXXXX";
                    $iv  = "sicovcontacindra";
                    $eve = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, preg_replace('/[\n\r\t]+/', '', $this->formato_texto($taxonomia)), MCRYPT_MODE_CBC, $iv);
                    $eve = base64_encode($eve);
                    $fur = [
                        'cadena' => $eve,
                    ];
                    $tipo = 'f';
                    $respuesta = $client->EnviarFurSicov($fur);
                    
                    $respuestaenvio = $respuesta->EnviarFurSicovResult;
                    // $respuesta = json_decode(json_encode($respuesta), true);
                    //  var_dump($respuestaenvio->codRespuesta);
                    if ($respuestaenvio->codRespuesta  == '1') {
                        $datos['sicov'] = '1';
                        $estado         = 'exito';
                        $msg            = 'Operación Exitosa';
                        if ($aprobado !== '1') {
                            $datos['estadototal'] = '3';
                        } else {
                            $datos['estadototal'] = '2';
                        }
                        $this->Mhojatrabajo->update_x($datos);
                        } else {
                            $msg    = 'Operación Fallida';
                            $estado = 'error';
                        }
                        $mensaje = $msg . '|' . $respuestaenvio->codRespuesta  . '|' . $ocasion . '|' . $estado . '|' . $respuestaenvio->msjRespuesta;
                        $this->insertarEvento($data['vehiculo']->numero_placa, $this->formato_texto($taxonomia), $tipo, '1', $mensaje);
                } else {
                    // var_dump('por sistema operativo');  
                    // file_put_contents('encdes/entradaFUR.txt', preg_replace('/[\n\r\t]+/', '', $this->formato_texto($taxonomia)));
                    // $url = 'http://localhost:8093/enc/encFur.php' . '?fur=' . "1";
                    // $eve = file_get_contents($url);

                    $url = 'http://' . $this->ipdocker . ':49000/fur-indra';
                    $ch = curl_init($url);

                    $fur = ['fur' => preg_replace('/[\n\r\t]+/', '', $this->formato_texto($taxonomia))]; // Cambiar 'cadena' a 'fur'
                    $jsonData = json_encode($fur);

                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'X-Service-Version: 19'
                    ]);

                    $response = curl_exec($ch);
                    $tipo = 'f';
                    $response= explode('"', $response); 
                    if ($response[0] == '1' || $response[0] == 1) {
                        $datos['sicov'] = '1';
                        $estado         = 'exito';
                        $msg            = 'Operación Exitosa';
                                if ($aprobado !== '1') {
                                    $datos['estadototal'] = '3';
                                } else {
                                    $datos['estadototal'] = '2';
                                }
                                $this->Mhojatrabajo->update_x($datos);
                    } else {
                                $msg    = 'Operación Fallida';
                                $estado = 'error';
                    }
                    curl_close($ch);
                    $mensaje = $msg . '|' . $response[0] . '|' . $ocasion . '|' . $estado . '|' . $response[1];
                    // var_dump($mensaje);
                    $this->insertarEvento($data['vehiculo']->numero_placa, $this->formato_texto($taxonomia), $tipo, '1', $mensaje);
                }

               

                
            } else {
                $extranjero = "S";
                if ($data['pais']->nombre == 'COLOMBIA') {
                    $extranjero = "N";
                }

                $tipo = 'r';
                $runt = [
                    'nombreEmpleado'       => $data['vehiculo']->usuario_registro,
                    'numeroIdentificacion' => $data['vehiculo']->documento_usuario,
                    'placa'                => $data['vehiculo']->numero_placa,
                    'extranjero'           => $extranjero,
                    'consecutivoRUNT'      => substr($data['numero_consecutivo'], 1),
                    'IdRunt'               => $this->idCdaRUNT,
                    'direccionIpEquipo'    => $_SERVER['REMOTE_ADDR'],
                ];
                $respuesta = $client->EnviarRuntSicov($runt);
                $respuesta = $respuesta->EnviarRuntSicovResult;
                if ($respuesta->codRespuesta == '1') {
                    $datos['sicov'] = '1';
                    $estado         = 'exito';
                    $msg            = 'Operación Exitosa';
                    if ($aprobado !== '1') {
                        $datos['estadototal'] = '7';
                    } else {
                        $datos['estadototal'] = '4';
                    }
                    if ($this->salaEspera2 == "1") {
                        $sala['idhojaprueba']  = $data['hojatrabajo']->idhojapruebas;
                        $sala['idtipo_prueba'] = "20";
                        $sala['estado']        = "1";
                        $sala['actualizado']   = "0";
                        $this->Mcontrol_salae->insertar($sala);
                    }
                    $this->Mhojatrabajo->update_x($datos);
                } else {
                    $msg    = 'Operación Fallida';
                    $estado = 'error';
                }
                $mensaje = $msg . '|' . $respuesta->codRespuesta . '|' . $ocasion . '|' . $estado . '|' . $respuesta->msjRespuesta;
                $this->insertarEvento($data['vehiculo']->numero_placa, $this->formato_texto($taxonomia), $tipo, '1', $mensaje);
                // Transacción exitosa|0000|1|exito|Transaccion Exitosa
                //                if ($this->CARinformeActivo == "1") {
                //                    $rta = $this->Mambientales->getEnvioCar($this->idprueba_gases);
                //                    if (count($rta) == 0) {
                //                        $envioCarMsg = $this->getInformeCarNew($data['hojatrabajo']->idhojapruebas);
                //                        $msg = $msg . " - Respuesta CAR: " . $envioCarMsg . " - ";
                //                    }
                //                }
            }
            
        } else {
            $mensaje = 'Operación fallida|0|' . $ocasion . '|error|Sin conexión a sicov';
            $this->insertarEvento($data['vehiculo']->numero_placa, '', 'f', '1', $mensaje);
        }
        if ($fp) {
            fclose($fp);
        }
        echo $mensaje;
    }

}
