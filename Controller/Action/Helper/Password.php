<?php
require_once('Zend/Controller/Action/Helper/Abstract.php');

/**
 * Action Helper para tratar passwords
 * @author David Lores <david@irontec.com>
 */

class Iron_Controller_Action_Helper_Password extends Zend_Controller_Action_Helper_Abstract
{

    /**
     * Generar un password aleatorio
     *
     * @param $length Longitud del password. Mínimo 4
     * @param $types Tipos de caracteres que pueden formar el password
     * @param $repeat Si es true se pueden repetir caracteres. Si es false, no se pueden repetir
     * @return string
     */
    public function generatePassword($length = 6, $types = array(), $repeat = true)
    {
        /* Comprobamos los parámetros de longitud y tipos de caracteres */
        if (!is_array($types) || count($types) == 0) {
            $types = array('numbers', 'uppercase', 'lowercase', 'symbols');
        }
        $length = ($length < 4)? 4 : $length;
        /* Caracteres para generar la contraseña */
        $chars = array(
            'numbers' => range('0', '9'), /* 0 1 2 3 4 5 6 7 8 9 */
            'uppercase' => range('A', 'Z'), /* A B C D E F G H I J K L M N O P Q R S T U V W X Y Z */
            'lowercase' => range('a', 'z'), /* a b c d e f g h i j k l m n o p q r s t u v w x y z */
            'symbols' => range('!', '/') /* ! " # $ % & ' ( ) * + , - . / */
        );
        /* Guardamos en $values los caracteres que puede generar el password */
        $values = array();
        foreach ($types as $type) {
            if (isset($chars[$type])) {
                $values = array_merge($values, $chars[$type]);
            }
        }
        $max = count($values);
        /* Si no se puede repetir caracteres y hay menos caracteres que longitud del password, ponemos $repeat a true */
        if ($max < $length && $repeat === false) {
            $repeat = true;
        }
        $password = '';
        $used = array();
        /* Generamos el password */
        while (strlen($password) < $length) {
            $char = $values[mt_rand(0, $max - 1)];
            /* Si no se puede repetir y ya existe el caracter, continuamos al siguiente */
            if ($repeat === false && isset($used[$char])) {
                continue;
            }
            $password .= $char;
            $used[$char] = true;
        }
        return $password;
    }

    /**
     * Comprueba la robustez de un password. No terminado.
     * @param $password Password a comprobar
     * @return integer
     */
    public function checkStrength($password)
    {
        $length = strlen($password);

        if ($length == 0 ) {
            return 0;
        } elseif ($length <= 4) {
            return 10;
        }
        $numbers = false;
        $letters = false;
        $symbols = false;

        $chars = array();
        $total = $length * 4;
        for ($i = 0; $i < $length; $i++) {
            $char = $password{$i};
            /* Comprobamos si el caracter ya estaba */
            if (isset($chars[$char])) {
                $chars[$char]['count']++;
                continue;
            }
            if ($char >= '0' && $char <= '9') {
                if ($i > 0 && $i < ($length - 1)) {
                    $total += 2;
                }
                $numbers = true;
                $point = 4;
            } elseif (($char >= 'A' && $char <= 'Z') || ($char >= 'a' && $char <= 'z')) {
                $letters = true;
                $point = 2;
            } else {
                $symbols = true;
                $point = 6;
            }
            $chars[$char] = array('count' => 1, 'point' => $point);
        }
        foreach ($chars as $char) {
            $total += $char['point'];
            if ($char['count'] > 1) {
                $total -= $char['count'];
            }
        }
        /* Si solo es números o solo letras, restamos la longitud */
        if (($numbers && !$letters && !$symbols) || ($letters && !$numbers && !$symbols)) {
            $total -= $length * 2;
        }
        return $total;
    }
}
