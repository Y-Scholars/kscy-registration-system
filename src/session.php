<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 15
 */

session_start();

class Session {
    public function get_student_no() {
        return $_SESSION["student_no"];
    }

    public function set_student_no($student_no) {
        $_SESSION["student_no"] = $student_no;
    }

    public function set_admin_key($admin_key) {
        $_SESSION["admin_key"] = $admin_key;
    }

    public function get_admin_key() {
        return $_SESSION["admin_key"];
    }
}

$session = new Session;
?>