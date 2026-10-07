<?php

function sistemaoperativo(){
    $cmd = shell_exec('ipconfig');
    shell_exec('cd /var/www/html chmod -R 777 et ');
    if($cmd == "" || $cmd == null){

        return "";
    }else{
        return "windows";
    }
}