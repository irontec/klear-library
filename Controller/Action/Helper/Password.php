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
     * Basado en http://www.passwordmeter.com/ menos las Sequential y los Requirements
     * Comprueba la robustez de un password. Devuelve un integer de 0 a 100, que indica el % de robustez.
     * De 0 a 20: muy débil
     * De 20 a 40: débil
     * De 40 a 60: buena
     * De 60 a 80: fuerte
     * De 80 a 100: muy fuerte
     * @param $password Password a comprobar
     * @return integer
     */
    public function checkStrength($password)
    {
        $length = strlen($password);

        $numbers = $upper = $lower = $symbols = 0; /* total de cada tipo */
        $cNumber = $cUpper =$cLower = 0; /* consecutivos de cada tipo */
        $nRepInc = $nRepChar = 0;
        $last = false; /* para saber que tipo es el caracter anterior */

        $chars = array();
        $total = $length * 4; /* Número de caracteres por 4 */

        for ($i = 0; $i < $length; $i++) {
            $char = $password{$i};

            if ($char >= '0' && $char <= '9') {
                /* Si no está en los extremos, sumamos 2 */
                if ($i > 0 && $i < ($length -1)) {
                    $total += 2;
                }
                /* Si el anterior era number, restamos 2 por consecutivo */
                if ($last == 'number') {
                    $total -= 2;
                }
                $numbers++;
                $last = 'number';
            } elseif ($char >= 'A' && $char <= 'Z') {
                /* Si el anterior era upper, restamos 2 por consecutivo */
                if ($last == 'upper') {
                    $total -= 2;
                }
                $upper++;
                $last = 'upper';
            } elseif ($char >= 'a' && $char <= 'z') {
                /* Si el anterior era lower, restamos 2 por consecutivo */
                if ($last == 'lower') {
                    $total -= 2;
                }
                $lower++;
                $last = 'lower';
            } else {
                /* Si no está en los extremos, sumamos 2 */
                if ($i > 0 && $i < ($length -1)) {
                    $total += 2;
                }
                $symbols++;
                $total += 6;
                $last = 'symbol';
            }

            /* comprobamos caracteres repetidos */
            $bCharExists = false;
            for ($k = 0; $k < $length; $k++) {
                if ($char == $password{$k} && $i != $k) {
                    $bCharExists = true;
                    /*
                    Calculate icrement deduction based on proximity to identical characters
                    Deduction is incremented each time a new match is discovered
                    Deduction amount is based on total password length divided by the
                    difference of distance between currently selected match
                    */
                    $nRepInc += abs($length / ($k - $i));
                }
            }
            if ($bCharExists) {
                $nRepChar++;
                $nUnqChar = $length - $nRepChar;
                $nRepInc = ($nUnqChar) ? ceil($nRepInc / $nUnqChar) : ceil($nRepInc);
            }
        }
        $total -= $nRepInc;
        /* Solo sumamos los numeros si no hay solo numeros */
        if ($numbers && ($upper || $lower || $symbols)) {
            $total += ($numbers * 4);
        }
        /* Para mayusculas y minusculas (si hay), se suma al total la diferencia del total de caracteres
        menos mayusculas/minusculas multiplicado por 2 */
        if ($upper > 0) {
            $total += (($length - $upper) * 2);
        }
        if ($lower > 0) {
            $total += (($length - $lower) * 2);
        }

        /* Si solo es números o solo letras, restamos la longitud */
        if (($numbers && !$upper && !$lower && !$symbols) || (($lower || $upper) && !$numbers && !$symbols)) {
            $total -= $length;
        }
        if ($total < 0) {
            $total = 0;
        } elseif ($total > 100) {
            $total = 100;
        }
        return $total;
    }
}
