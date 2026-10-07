<?php


function espejoDatabase()
{

    // echo '<pre>';
    // var_dump($data[0]['Slave_IO_State']);
    // echo '</pre>';
    
    $no_permitidas = array("Ñ", "ñ", "á", "é", "í", "ó", "ú", "Á", "É", "Í", "Ó", "Ú", "ñ", "À", "Ã", "Ì", "Ò", "Ù", "Ã™", "Ã ", "Ã¨", "Ã¬", "Ã²", "Ã¹", "ç", "Ç", "Ã¢", "ê", "Ã®", "Ã´", "Ã»", "Ã‚", "ÃŠ", "ÃŽ", "Ã”", "Ã›", "ü", "Ã¶", "Ã–", "Ã¯", "Ã¤", "«", "Ò", "Ã", "Ã„", "Ã‹", "'", "", "'", "-", "`","\n", "\r");
        $permitidas = array("N", "n", "a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "n", "N", "A", "E", "I", "O", "U", "a", "e", "i", "o", "u", "c", "C", "a", "e", "i", "o", "u", "A", "E", "I", "O", "U", "u", "o", "O", "i", "a", "e", "U", "I", "A", "E", "", "", "", "", "", "", "", "", "", "","","", "", "", "");
    $CI = &get_instance();
    $CI->session->set_userdata('espejoDatabase', 0);
    $CI->session->set_userdata('espejoDatabaseMesaje', "");
    $msj = "";
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

        $CI->load->model('Mindex');
        $var = $CI->Mindex->staTus();
        $data  = $var->result();
        // echo '<pre>';
        // var_dump($data);
        // echo '</pre>';
        if ($data[0]->Slave_IO_Running !== 'Yes' || $data[0]->Slave_SQL_Running !== 'Yes') {
            
            $CI->session->set_userdata('espejoDatabase', 1);
            $CI->session->set_userdata('espejoDatabaseMesaje', str_replace($no_permitidas, $permitidas, $data[0]->Last_Error));

            //echo "<script>localStorage.setItem('espejoDatabase', 1); localStorage.setItem('espejoDatabaseMesaje', ' "  .  str_replace("'", "", $data[0]->Last_Error)  . "');</script>";
        } else {
            //echo "<script>localStorage.setItem('espejoDatabase', 0); localStorage.setItem('espejoDatabaseMesaje', '');</script>";
            $CI->session->set_userdata('espejoDatabase', 0);
            $CI->session->set_userdata('espejoDatabaseMesaje', '');
        }
    } else {
        $CI->session->set_userdata('espejoDatabase', 0);
        $CI->session->set_userdata('espejoDatabaseMesaje', '');
        //echo  "<script>localStorage.setItem('espejoDatabase', 0); localStorage.setItem('espejoDatabaseMesaje', '');</script>";
    }


}
