<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 15
 */

class Utils {
    public function assert($condition, $output) {
        if (!$condition) {
            echo $output;
            exit();
        }
    }

    public function purify($value) {
        return $value;
    }

    public function display($value) {
        echo(empty($value) ? "" : $value);
    }
}

$utils = new Utils;
?>