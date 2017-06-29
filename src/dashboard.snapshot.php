<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 29
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
    

    // 관리자 권한 체크
    if ($session->get_level() < 1)  {
        return array(
            "result" => "error",
            "message" => "접근 권한이 없습니다." 
        );
    }

    $snapshot = "{\n";
    $snapshot .= take_snapshot("kscy_students") . ",\n";
    $snapshot .= take_snapshot("kscy_papers") . ",\n";
    $snapshot .= take_snapshot("kscy_plans") . ",\n";
    $snapshot .= take_snapshot("kscy_mentorings") . ",\n";
    $snapshot .= take_snapshot("kscy_camps") . ",\n";
    $snapshot .= take_snapshot("kscy_statistics") . ",\n";
    $snapshot .= take_snapshot("kscy_logs") . "\n";
    $snapshot .= "}";

    return array(
        "result" => "success",
        "data" => $snapshot
    );
}

function take_snapshot($table_name) {

    global $db;

    $db_data = $db->in($table_name)
                  ->select("*")
                  ->go_and_get_all();

    $snapshot = "\"".$table_name . "\": [";
    foreach ($db_data as $db_datum) {
        $snapshot .= json_encode($db_datum) . ",\n";
    }
    $snapshot .= "]";

    return $snapshot;
}

$response = process();

if ($response["result"] == "error") {
    exit($response["message"]);
}

else if ($response["result"] == "success") {

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=snapshot-' . time() . '.json');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    ob_clean();
    flush();
    echo($response["data"]);
    exit();
}
?>