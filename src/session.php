<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 15
 */

error_reporting(0);
session_start();

class Session {
    public function get_student_no() {
        return $_SESSION["student_no"];
    }

    public function delete_student_no() {
        $_SESSION["student_no"] = "";
        session_destroy();
    }

    public function set_student_no($student_no) {
        $_SESSION["student_no"] = $student_no;
    }

    public function set_level($level) {
        $_SESSION["level"] = $level;
    }

    public function get_level() {
        return isset($_SESSION["level"]) ? $_SESSION["level"] : 0;
    }
}

$session = new Session;
?>