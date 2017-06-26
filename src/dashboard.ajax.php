<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 22
 */

error_reporting(0);

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

function process() {

    global $db;
    global $session;
    global $utils;
    global $strings;

    // 파라미터 체크
    if (!isset($_POST["action"]) || !isset($_POST["type"]) || !isset($_POST["no"])) {
        return array(
            "result" => "error",
            "message" => "파라미터가 충분하지 않습니다."
        );
    }

    $user_action = trim($_POST["action"]);
    $user_type = trim($_POST["type"]);
    $user_no = trim($_POST["no"]);

    if ($user_action == "load") {

        // 접근 권한 검사
        if ($session->get_level() < 1)  {
            return array(
                "result" => "error",
                "message" => "접근 권한이 없습니다."
            );
        }

        $response = $db->in('kscy_' . $user_type . 's')
                        ->select('*')
                        ->where('no', '=', $user_no)
                        ->go_and_get();
        
        $response["desired_session"] = translate($response["desired_session"], "session_names");
        $response["survey"] = translate($response["survey"], "survey_names");
        $response["level"] = translate($response["level"], "level_names");

        if ($response) {
            return $response;
        }
    }
    
    else if ($user_action == "save") {

        // 접근 권한 검사
        if ($session->get_level() < 2)  {
            return array(
                "result" => "error",
                "message" => "접근 권한이 없습니다."
            );
        }

        if (!isset($_POST["key"]) || !isset($_POST["value"])) {
            return array(
                "result" => "error",
                "message" => "파라미터가 충분하지 않습니다."
            );
        }
        $user_key = trim($_POST["key"]);
        $user_value = trim($_POST["value"]);

        $response = $db->in('kscy_' . $user_type . 's')
                        ->update($user_key, $user_value)
                        ->where('no', '=', $user_no)
                        ->go();

        $log = $db->in('kscy_logs')
                  ->insert('user', $session->get_student_no())
                  ->insert('target_user', $user_no)
                  ->insert('action', "update status")
                  ->insert('data',  $user_key . "=" . $user_value)
                  ->insert('ip', $_SERVER['REMOTE_ADDR'])
                  ->go();

        if ($response) {
            return $response;
        }               
    }

    return array(
        "result" => "error",
        "message" => "데이터 로드에 실패하였습니다."
    );
}

function translate($data, $filter) {

    global $strings;

    if (isset($data)) {
        return $strings[$filter][$data];
    }
}

$response = process();

header("Content-Type:application/json");
echo(json_encode($response));
?>