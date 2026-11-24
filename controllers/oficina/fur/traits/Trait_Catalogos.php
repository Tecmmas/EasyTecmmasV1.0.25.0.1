<?php

defined('BASEPATH') or exit('No direct script access allowed');

trait Trait_Catalogos
{
    public function getCda($fechainicial)
    {
        $result = $this->Mcda->getVersion($fechainicial);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    //------------------------------------------------------------------------------Obtener consecutivo
    public function getConsecutivo($idhojapruebas)
    {
        $data['idhojapruebas'] = $idhojapruebas;
        $result                = $this->MconsecutivoTC->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0]->idconsecutivotc;
        } else {
            return $idhojapruebas;
        }
    }

    //------------------------------------------------------------------------------Obtener número ONAC
    public function getNumeroOnac()
    {
        $data['idconfig_prueba'] = '183';
        $result                  = $this->Mconfig_prueba->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0]->valor;
        } else {
            return '';
        }
    }

    public function getSede()
    {
        $result = $this->Msede->get();
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getHojatrabajo($idhojapruebas)
    {
        $data['idhojapruebas'] = $idhojapruebas;
        $result                = $this->Mhojatrabajo->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getFechaReins($idhojapruebas)
    {
        $data['idhojapruebas'] = $idhojapruebas;
        $result                = $this->Mprueba->getLast($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getFechaLastReins($idhojapruebas, $order)
    {
        $data['idhojapruebas'] = $idhojapruebas;
        $result                = $this->Mprueba->getLastFecha($data, $order);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getVehiculo($idvehiculo)
    {
        $data['idvehiculo'] = $idvehiculo;
        $result             = $this->Mvehiculo->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getPropietario($idpropietario)
    {
        $data['idcliente'] = $idpropietario;
        $result            = $this->Mpropietario->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getCiudad($cod_ciudad)
    {
        $data['cod_ciudad'] = $cod_ciudad;
        $result             = $this->Mciudad->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar la ciudad del vehículo o el cliente. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            return 'NA';
        }
    }

    public function getDepartamento($cod_depto)
    {
        $data['cod_depto'] = $cod_depto;
        $result            = $this->Mdepartamento->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getPais($idpais)
    {
        $data['idpais'] = $idpais;
        $result         = $this->Mpais->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            return 'NA';
        }
    }

    public function getLinea($idlinea, $vehiculo)
    {
        if ($vehiculo->registrorunt == '1' && $vehiculo->migrateLineaMarca == '0') {
            $data['idlineaRUNT'] = $idlinea;
            $result              = $this->Mlinea->get($data);
        } else if ($vehiculo->registrorunt == '1' && $vehiculo->migrateLineaMarca == '1') {
            $data['idlinea'] = $idlinea;
            $result          = $this->Mlinea->getmigrateLineaMarca($data);
        } else {
            $data['idlinea'] = $idlinea;
            $result          = $this->Mlinea->get2($data);

        }

        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar línea y marca del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
        }
    }

    public function getMarca($idmarca, $vehiculo)
    {
        // var_dump($vehiculo);
        // var_dump($idmarca);
        if ($vehiculo->registrorunt == '1' && $vehiculo->migrateLineaMarca == '0') {

            $data['idmarcaRUNT'] = $idmarca;
            $result              = $this->Mmarca->get($data);
        } else if ($vehiculo->registrorunt == '1' && $vehiculo->migrateLineaMarca == '1') {
            // echo 'elseif';
            $data['idmarca'] = $idmarca;
            $result          = $this->Mmarca->getmigrateLineaMarca($data);
        } else {
            $data['idmarca'] = $idmarca;
            $result          = $this->Mmarca->get2($data);
        }
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar línea y marca del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            // echo "<script>alert('Por favor Actualizar líneas y marcas del vehículo.');</script>";
            // return 'NA';
        }
    }

    public function getServicio($idservicio)
    {
        $data['idservicio'] = $idservicio;
        $result             = $this->Mservicio->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar el servicio del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            // return 'NA';
        }
    }

    public function getClase($idclase)
    {
        $data['idclase'] = $idclase;
        $result          = $this->Mclase->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar la clase del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            // return 'NA';
        }
    }

    public function getColor($idcolor, $vehiculo)
    {
        if ($vehiculo->registrorunt == '1') {
            $data['idcolorRUNT'] = $idcolor;
            $result              = $this->Mcolor->get($data);
        } else {
            $data['idcolor'] = $idcolor;
            $result          = $this->Mcolor->get2($data);
        }
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar el color del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            // return 'NA';
        }
    }

    public function getCombustible($idcombustible)
    {
        $data['idtipocombustible'] = $idcombustible;
        $result                    = $this->Mcombustible->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar el combustible del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            // return 'NA';
        }
    }

    public function getCarroceria($idcarroceria)
    {
        $data['idcarroceria'] = $idcarroceria;
        $result               = $this->Mcarroceria->get($data);
        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            echo "<script>
            alert('Error al cargar el FUR: Se requiere actualizar la carroceria del vehículo. Complete esta información en el sistema y si el problema continúa, contacte a soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
            // return 'NA';
        }
    }
    public function getUsuario($IdUsuario)
    {
        $data['IdUsuario'] = $IdUsuario;
        $result            = $this->Musuarios->get($data);

        if ($result->num_rows() > 0) {
            $r = $result->result();
            return $r[0];
        } else {
            // Mostrar alerta y redirigir con JavaScript usando base_url()
            echo "<script>
            alert('No se encontró información del usuario registrado. Esto puede deberse a que no se realizó la prerevisión o no se actualizó la información del vehículo antes de su asignación.\\\\n\\\\nPor favor:\\\\n1. Realice la prerevisión correspondiente\\\\n2. Actualice la información del vehículo desde oficina\\\\n\\\\nSi el problema persiste, comuníquese con soporte técnico.');
            window.location.href = '" . base_url('index.php/oficina/CGestion') . "';
        </script>";
            exit;
        }
    }

    //------------------------------------------------------------------------------REGISTROS AUTENTICADOS

    private function autenticarResultado($r, $placa, $tipo_prueba, $fechaPrueba)
    {
        // La prueba visual (tipo 8) no se valida contra el juez: sus textos libres (p. ej. comentarios con saltos de línea) dan falsos positivos.
        if ((string) $tipo_prueba === '8') {
            return;
        }
        if ($r->autentico !== "1" && $this->fechaJuez <= $fechaPrueba) {
            array_push($this->resultadosAlterados, (object) [
                "placa"       => $placa,
                "tipo_prueba" => $tipo_prueba,
                "old"         => $r->old,
                "new"         => $r->new,
            ]);
        }
    }

    private function autenticarPrueba($placa, $idprueba)
    {
        $aPrueba = $this->Mprueba->getxId($idprueba);
        if ($aPrueba->num_rows() > 0) {
            $p = $aPrueba->result();
            if ($p[0]->autentico !== "1" && $this->fechaJuez <= $p[0]->fechainicial) {
                array_push($this->pruebasAlteradas, (object) [
                    "placa"       => $placa,
                    "tipo_prueba" => "p",
                    "old"         => $p[0]->old,
                    "new"         => $p[0]->new,
                ]);
            }
        }
    }

    //------------------------------------------------------------------------------CORREGIR FUR
    private function corregirFur($asterisco, $idprueba, $estado)
    {
        // echo 'asterisco' . $asterisco .'<br>';
        // echo 'idprueba' . $idprueba. '<br>';
        // echo 'estado' . $estado. '<br>';
        if ($asterisco && $estado == '2') {
            $this->Mprueba->actualizarEstado($idprueba, '1');
            $estado             = '1';
            $this->furCorregido = "1";
        }
        if (! $asterisco && $estado == '1') {
            $this->Mprueba->actualizarEstado($idprueba, '2');
            $estado             = '2';
            $this->furCorregido = "1";
        }
        return $estado;
    }

    //------------------------------------------------------------------------------LUCES
    public $ifdefLuzMinBaja;
    public $ifdefInclinacionLuces;
    public $ifdefIntensidadTotal;
    public $asteriscoLuces = false;

}
