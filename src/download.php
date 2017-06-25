<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 25
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
    if (!isset($_GET["type"])) {
        return array(
            "result" => "error",
            "message" => "파라미터가 충분하지 않습니다." 
        );
    }

    $type = $_GET["type"];

    switch ($type) {
        case "papers-all":
            $type_name = "논문";
            break;
        case "plans-all":
            $type_name = "연구계획";
            break;
        case "papers-session":
            $type_name = "논문";
            break;
        case "plans-session":
            $type_name = "연구계획";
            break;
        case "paper":
            $type_name = "논문";
            break;
        case "plan":
            $type_name = "연구계획";
            break;
        default:
            return array(
                "result" => "error",
                "message" => "잘못된 파라미터가 전송되었습니다." 
            );
    }


    if ($type != "paper" && $type != "plan") {

        // 관리자 권한 체크
        if ($session->get_level() < 1)  {
            return array(
                "result" => "error",
                "message" => "접근 권한이 없습니다." 
            );
        }
        
        $zip_name = $type . "s";
        $zip_data = new ZipArchive;
        $zip_data->open($zip_name . ".zip", ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 지원서 정보 불러오기
        $applications = $db->in('kscy_' .$type. 's')
                        ->select('*')
                        ->go_and_get_all();

        foreach ($applications as $application) {
            
            // 팀장 정보 불러오기
            $team_leader = $application["team_leader"];
            $team_leader_data = $db->in('kscy_students')
                                ->select("*")
                                ->where("no", "=", $team_leader)
                                ->go_and_get();

            if (!$team_leader_data) {
                continue;
            }
            $file_path = $application["file"];
            $file_extension = pathinfo($file_path)['extension'];
            $file_name = $strings["session_names"][$application["desired_session"]] . 
                        "_" . $type_name . 
                        "_" . $team_leader_data["name"] .
                        "_" . mb_strimwidth($application["title"], 0, 15, '...');
            
            $zip_data->addFile($file_path, $file_name . $file_extension);
        }
        $zip_data->close();

        return array(
            "result" => "success",
            "type" => $type,
            "file" => $zip_data
        );
    }

    else {

        // 로그인 체크
        if (!empty($session->get_student_no())) {
            return array(
                "result" => "error",
                "message" => "접근 권한이 없습니다." 
            );
        }

        // 지원서 정보 불러오기
        $applications = $db->in('kscy_' .$type. 's')
                           ->select('*')
                           ->where("no", "=", $session->get_student_no())
                           ->go_and_get();

        // 팀장 정보 불러오기
        $team_leader = $application["team_leader"];
        $team_leader_data = $db->in('kscy_students')
                                ->select("*")
                                ->where("no", "=", $team_leader)
                                ->go_and_get();

        $file_path = $application["file"];
        $file_extension = pathinfo($file_path)['extension'];
        $file_name = $strings["session_names"][$application["desired_session"]] . 
                    "_" . $type_name . 
                    "_" . $team_leader_data["name"] .
                    "_" . mb_strimwidth($application["title"], 0, 15, '...');

        return array(
            "result" => "success",
            "type" => $type,
            "file" => $file_path,
            "file_name" => $file_name . $file_extension
        );
    }
}

$response = process();


header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $filename);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);
exit();
?>