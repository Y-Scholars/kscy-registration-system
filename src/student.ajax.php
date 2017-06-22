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

function process() {

    global $db;
    global $utils;

    // 파라미터 체크
    if (!isset($_POST["action"])) {
        return array(
            "result" => "error",
            "message" => "파라미터가 충분하지 않습니다."
        );
    }

    $user_action = trim($_POST["action"]);
    if ($user_action == "get-by-email") {
        if (!isset($_POST["email"])) {
            return array(
                "result" => "error",
                "message" => "파라미터가 충분하지 않습니다."
            );
        }
        $user_email = trim($_POST["email"]);
        $response = $db->in('kscy_students')
                        ->select('no')
                        ->select('name')
                        ->select('school')
                        ->select('grade')
                        ->where('email', '=', $utils->purify($user_email))
                        ->go_and_get();
        if (!$response) {
            return array(
                "result" => "error",
                "message" => "학생을 찾을 수 없습니다. 등록된 학생인지 확인해 주세요."
            );
        }
        return array(
            "result" => "success",
            "data" => $response
        );
    }

    return array(
        "result" => "error",
        "message" => "데이터 로드에 실패하였습니다."
    );
}

$response = process();

header("Content-Type:application/json");

if ($response["result"] == "error") {
    echo(json_encode($response));
}

else if ($response["result"] == "success") {
    $response["data"]["grade"] = ((intval($response["data"]["grade"]) - 1) % 3) + 1;
    $response["data"]["result"] = "success";
    echo(json_encode($response["data"]));
}
?>