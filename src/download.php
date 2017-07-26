<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 25
 */

error_reporting(0);
set_time_limit (3600);

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

    $user_type = $_GET["type"];

    if ($user_type == "papers-all" || $user_type == "plans-all" || $user_type == "session") {

        // 관리자 권한 체크
        if ($session->get_level() < 1)  {
            return array(
                "result" => "error",
                "message" => "접근 권한이 없습니다." 
            );
        }

        // 세션 넘버 체크
        if ($user_type == "session") {
            if (!isset($_GET["no"])) {
                return array(
                    "result" => "error",
                    "message" => "파라미터가 충분하지 않습니다." 
                );
            }
            $session_no = $_GET["no"];
        }
        
        $zip_path = "data.zip";
        $zip_data = new ZipArchive;
        $zip_data->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // 지원서 정보 불러오기
        for ($i = 0; $i < 2; $i++) {

            if ($user_type == "papers-all" && $i == 1) {
                continue;
            }

            if ($user_type == "plans-all" && $i == 0) {
                continue;
            }

            $db->in($i == 0 ? "kscy_papers" : "kscy_plans")->select('*');

            if ($user_type == "session") {
                $db->where("desired_session", "=", $session_no);
            }
            $applications = $db->go_and_get_all();

            foreach ($applications as $application) {

                $application_file = load_application_file($application, $i == 0 ? "논문" : "연구계획");

                if (empty($application_file)) {
                    continue;
                }
                $zip_data->addFile($application_file["file_path"], $application_file["file_name"] . "." . $application_file["file_extension"]);
            }
        }

        $zip_data->close();

        return array(
            "result" => "success",
            "file_path" => $zip_path,
            "file_name" => "data-" . time(),
            "file_extension" => "zip"
        );
    }

    else if ($user_type == "paper" || $user_type == "plan") {

        // 세션 넘버 체크
        if (isset($_GET["no"])) {
            $application_no = $_GET["no"];
        }
        
        if (!empty($application_no)) {
            // 관리자 권한 체크
            if ($session->get_level() < 1)  {
                return array(
                    "result" => "error",
                    "message" => "접근 권한이 없습니다." 
                );
            }
        } else {
            // 로그인 체크
            if (empty($session->get_student_no())) {
                return array(
                    "result" => "error",
                    "message" => "접근 권한이 없습니다." 
                );
            }
        }

        // 지원서 정보 불러오기
        $db->in('kscy_' .$user_type. 's')->select('*');

        if (empty($application_no)) {
            $db->where("team_leader", "=", $session->get_student_no());
        } else {
            $db->where("no", "=", $application_no);
        }

        $application = $db->go_and_get();

        $application_file = load_application_file($application, $user_type == "paper" ? "논문" : "연구계획");

        if (empty($application_file)) {
            return array(
                "result" => "error",
                "message" => "파일이 존재하지 않습니다."
            );
        }

        return array(
            "result" => "success",
            "file_path" => $application_file["file_path"],
            "file_name" => $application_file["file_name"],
            "file_extension" => $application_file["file_extension"],
        );
    }

    return array(
        "result" => "error",
        "message" => "파라미터가 유효하지 않습니다." 
    );
}

function load_application_file($application, $application_type) {

    global $db;
    global $strings;

    // 팀장 정보 불러오기
    $team_leader = $application["team_leader"];
    $team_leader_data = $db->in('kscy_students')
                            ->select("*")
                            ->where("no", "=", $team_leader)
                            ->go_and_get();

    $file_path = $application["file"];

    if (!file_exists($file_path)) {
        return null;
    }

    $file_extension = pathinfo($file_path)['extension'];
    $file_name = str_replace(":", "_", str_replace(" ", "", $strings["session_names"][$application["desired_session"]] . 
                "_" . $application_type . 
                "_" . $team_leader_data["name"] .
                "_" . mb_strimwidth($application["title"], 0, 15, '...')));
    
    return array(
        "file_path" => $file_path,
        "file_name" => $file_name,
        "file_extension" => $file_extension
    );
}

$response = process();

if ($response["result"] == "error") {
    exit($response["message"]);
}

else if ($response["result"] == "success") {

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $response["file_name"] . "." . $response["file_extension"]);
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . filesize($response["file_path"]));
    ob_clean();
    flush();
    readfile($response["file_path"]);
    exit();
   
}
?>