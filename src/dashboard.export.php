<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 23
 */

error_reporting(0);

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");
require_once('./vendor/phpoffice/phpexcel/Classes/PHPExcel.php');

function process() {

    global $db;
    global $session;
    global $utils;

    // 파라미터 체크
    if (!isset($_GET["type"])) {
        return array(
            "result" => "error",
            "message" => "파라미터가 충분하지 않습니다." 
        );
    }
    $user_type = trim($_GET["type"]);

    // 접근 권한 검사
    if ($session->get_level() < 1)  {
        return array(
            "result" => "error",
            "message" => "접근 권한이 없습니다."
        );
    }

    $excel_data = new PHPExcel();
    $excel_data->getProperties()
               ->setCreator("KSCY Application System")
			   ->setTitle("KSCY Application");

    switch ($user_type) {
        case "paper":
            processor_paper_and_plan($excel_data, true);
            break;
        case "plan":
            processor_paper_and_plan($excel_data, false);
            break;
        case "mentoring":
            processor_mentoring($excel_data);
            break;
        case "camp":
            processor_camp($excel_data);
            break;
        case "session":
            if (!isset($_GET["no"])) {
                return array(
                    "result" => "error",
                    "message" => "파라미터가 충분하지 않습니다." 
                );
            }
            $user_session_no = trim($_GET["no"]);
            processor_session($excel_data, $user_session_no);
            break;
        case "student":
            processor_student($excel_data);
            break;
        case "log":
            processor_log($excel_data);
            break;
    }
    return array(
        "result" => "success",
        "data" => $excel_data,
    );
}

function processor_paper_and_plan($excel_data, $is_paper) {

    global $db;
    global $strings;

    $applications = $db->in('kscy_'. ($is_paper ? "papers" : "plans"))
                   ->select("*")    
                   ->go_and_get_all();

    if (!$applications) {
        exit();
    }

    $excel_data->setActiveSheetIndex(0)
               ->setCellValue("A1", "번호")
               ->setCellValue("B1", "제목")
               ->setCellValue("C1", "분야")
               ->setCellValue("D1", "희망 세션")
               ->setCellValue("E1", "이름")
               ->setCellValue("F1", "성별")
               ->setCellValue("G1", "학교")
               ->setCellValue("H1", "학년")
               ->setCellValue("I1", "이메일")
               ->setCellValue("J1", "전화번호")
               ->setCellValue("K1", "입금")
               ->setCellValue("L1", "참가 상태")
               ->setCellValue("M1", "제출 시간");

    $excel_data->getActiveSheet()->setTitle(($is_paper ? '논문' : '연구계획') . ' 발표 지원서');
    $i = 2;
    $count = 1;
    foreach ($applications as $application) {

        // 팀원 정보 가져오기
        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        
        foreach ($team_members_data as $team_member_data) {
            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, $count)
                       ->setCellValue("B".$i, $application["title"])
                       ->setCellValue("C".$i, $application["research_field"])
                       ->setCellValue("D".$i, $strings["session_names"][$application["desired_session"]])
                       ->setCellValue("E".$i, $team_member_data["name"] . ($team_member_data["no"] == $application["team_leader"] ? "*" : ""))
                       ->setCellValue("F".$i, $team_member_data["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("G".$i, $team_member_data["school"])
                       ->setCellValue("H".$i, $team_member_data["grade"])
                       ->setCellValue("I".$i, $team_member_data["email"])
                       ->setCellValue("J".$i, $team_member_data["phone_number"])
                       ->setCellValue("K".$i, $strings["deposit_names"][$team_member_data["deposit_status"]])
                       ->setCellValue("L".$i, $strings["approved_names"][$application["approved"]])
                       ->setCellValue("M".$i, $application["timestamp"]);
            $i++;
        }
        $count++;
    }
}

function processor_mentoring($excel_data) {

    global $db;
    global $strings;

    $applications = $db->in('kscy_mentorings')
                   ->select("*")    
                   ->go_and_get_all();

    if (!$applications) {
        exit();
    }

    $excel_data->setActiveSheetIndex(0)
               ->setCellValue("A1", "번호")
               ->setCellValue("B1", "자기소개")
               ->setCellValue("C1", "참가 동기")
               ->setCellValue("D1", "희망 세션")
               ->setCellValue("E1", "이름")
               ->setCellValue("F1", "성별")
               ->setCellValue("G1", "학교")
               ->setCellValue("H1", "학년")
               ->setCellValue("I1", "이메일")
               ->setCellValue("J1", "전화번호")
               ->setCellValue("K1", "입금")
               ->setCellValue("L1", "참가 상태")
               ->setCellValue("M1", "제출 시간");

    $excel_data->getActiveSheet()->setTitle('멘토링 참가 지원서');
    $i = 2;
    $count = 1;
    foreach ($applications as $application) {

        // 팀원 정보 가져오기
        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        
        foreach ($team_members_data as $team_member_data) {
            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, $count)
                       ->setCellValue("B".$i, $application["bio"])
                       ->setCellValue("C".$i, $application["motivation"])
                       ->setCellValue("D".$i, $strings["session_names"][$application["desired_session"]])
                       ->setCellValue("E".$i, $team_member_data["name"] . ($team_member_data["no"] == $application["team_leader"] ? "*" : ""))
                       ->setCellValue("F".$i, $team_member_data["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("G".$i, $team_member_data["school"])
                       ->setCellValue("H".$i, $team_member_data["grade"])
                       ->setCellValue("I".$i, $team_member_data["email"])
                       ->setCellValue("J".$i, $team_member_data["phone_number"])
                       ->setCellValue("K".$i, $strings["deposit_names"][$team_member_data["deposit_status"]])
                       ->setCellValue("L".$i, $strings["approved_names"][$application["approved"]])
                       ->setCellValue("M".$i, $application["timestamp"]);
            $i++;
        }
        $count++;
    }
}

function processor_camp($excel_data) {

    global $db;
    global $strings;

    $applications = $db->in('kscy_camps')
                       ->select("*")    
                       ->go_and_get_all();

    if (!$applications) {
        exit();
    }

    $excel_data->setActiveSheetIndex(0)
               ->setCellValue("A1", "번호")
               ->setCellValue("B1", "자기소개")
               ->setCellValue("C1", "참가 동기")
               ->setCellValue("D1", "희망 세션")
               ->setCellValue("E1", "이름")
               ->setCellValue("F1", "성별")
               ->setCellValue("G1", "학교")
               ->setCellValue("H1", "학년")
               ->setCellValue("I1", "이메일")
               ->setCellValue("J1", "전화번호")
               ->setCellValue("K1", "보호자 이름")
               ->setCellValue("L1", "보호자 연락처")
               ->setCellValue("M1", "입금")
               ->setCellValue("N1", "참가 상태")
               ->setCellValue("O1", "제출 시간");

    $excel_data->getActiveSheet()->setTitle('멘토링 참가 지원서');
    $i = 2;
    $count = 1;
    foreach ($applications as $application) {

        // 팀원 정보 가져오기
        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        
        foreach ($team_members_data as $team_member_data) {
            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, $count)
                       ->setCellValue("B".$i, $application["bio"])
                       ->setCellValue("C".$i, $application["motivation"])
                       ->setCellValue("D".$i, $strings["session_names"][$application["desired_session"]])
                       ->setCellValue("E".$i, $team_member_data["name"] . ($team_member_data["no"] == $application["team_leader"] ? "*" : ""))
                       ->setCellValue("F".$i, $team_member_data["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("G".$i, $team_member_data["school"])
                       ->setCellValue("H".$i, $team_member_data["grade"])
                       ->setCellValue("I".$i, $team_member_data["email"])
                       ->setCellValue("J".$i, $team_member_data["phone_number"])
                       ->setCellValue("K".$i, $team_member_data["guardian_name"])
                       ->setCellValue("L".$i, $team_member_data["guardian_phone_number"])
                       ->setCellValue("M".$i, $strings["deposit_names"][$team_member_data["deposit_status"]])
                       ->setCellValue("N".$i, $strings["approved_names"][$application["approved"]])
                       ->setCellValue("O".$i, $application["timestamp"]);
            $i++;
        }
        $count++;
    }
}


function processor_session($excel_data, $session_no) {

    global $db;
    global $strings;

    $excel_data->setActiveSheetIndex(0)
               ->setCellValue("A1", "트랙")
               ->setCellValue("B1", "번호")
               ->setCellValue("C1", "제목")
               ->setCellValue("D1", "분야")
               ->setCellValue("E1", "이름")
               ->setCellValue("F1", "성별")
               ->setCellValue("G1", "학교")
               ->setCellValue("H1", "학년")
               ->setCellValue("I1", "이메일")
               ->setCellValue("J1", "전화번호")
               ->setCellValue("K1", "입금")
               ->setCellValue("L1", "참가 상태")
               ->setCellValue("M1", "제출 시간");

    $excel_data->getActiveSheet()->setTitle(str_replace(":", " -", $strings["session_names"][$session_no]));

    $applications = $db->in('kscy_papers')
                   ->select("*")   
                   ->where("desired_session", "=", $session_no) 
                   ->go_and_get_all();

    $i = 2;

    $count = 1;
    foreach ($applications as $application) {

        // 팀원 정보 가져오기
        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        
        foreach ($team_members_data as $team_member_data) {
            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, "논문 발표")
                       ->setCellValue("B".$i, $count)
                       ->setCellValue("C".$i, $application["title"])
                       ->setCellValue("D".$i, $application["research_field"])
                       ->setCellValue("E".$i, $team_member_data["name"] . ($team_member_data["no"] == $application["team_leader"] ? "*" : ""))
                       ->setCellValue("F".$i, $team_member_data["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("G".$i, $team_member_data["school"])
                       ->setCellValue("H".$i, $team_member_data["grade"])
                       ->setCellValue("I".$i, $team_member_data["email"])
                       ->setCellValue("J".$i, $team_member_data["phone_number"])
                       ->setCellValue("K".$i, $strings["deposit_names"][$team_member_data["deposit_status"]])
                       ->setCellValue("L".$i, $strings["approved_names"][$application["approved"]])
                       ->setCellValue("M".$i, $application["timestamp"]);
            $i++;
        }
        $count++;
    }


    $applications = $db->in('kscy_plans')
                   ->select("*")
                   ->where("desired_session", "=", $session_no)  
                   ->go_and_get_all();

    foreach ($applications as $application) {

        // 팀원 정보 가져오기
        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        
        foreach ($team_members_data as $team_member_data) {
            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, "연구계획 발표")
                       ->setCellValue("B".$i, $count)
                       ->setCellValue("C".$i, $application["title"])
                       ->setCellValue("D".$i, $application["research_field"])
                       ->setCellValue("E".$i, $team_member_data["name"] . ($team_member_data["no"] == $application["team_leader"] ? "*" : ""))
                       ->setCellValue("F".$i, $team_member_data["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("G".$i, $team_member_data["school"])
                       ->setCellValue("H".$i, $team_member_data["grade"])
                       ->setCellValue("I".$i, $team_member_data["email"])
                       ->setCellValue("J".$i, $team_member_data["phone_number"])
                       ->setCellValue("K".$i, $strings["deposit_names"][$team_member_data["deposit_status"]])
                       ->setCellValue("L".$i, $strings["approved_names"][$application["approved"]])
                       ->setCellValue("M".$i, $application["timestamp"]);
            $i++;
        }
        $count ++;
    }

    $applications = $db->in('kscy_mentorings')
                   ->select("*")
                   ->where("desired_session", "=", $session_no)  
                   ->go_and_get_all();

    foreach ($applications as $application) {

        // 팀원 정보 가져오기
        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        
        foreach ($team_members_data as $team_member_data) {
            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, "멘토링 참가")
                       ->setCellValue("B".$i, $count)
                       ->setCellValue("C".$i, $application["bio"])
                       ->setCellValue("D".$i, $application["motivation"])
                       ->setCellValue("E".$i, $team_member_data["name"] . ($team_member_data["no"] == $application["team_leader"] ? "*" : ""))
                       ->setCellValue("F".$i, $team_member_data["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("G".$i, $team_member_data["school"])
                       ->setCellValue("H".$i, $team_member_data["grade"])
                       ->setCellValue("I".$i, $team_member_data["email"])
                       ->setCellValue("J".$i, $team_member_data["phone_number"])
                       ->setCellValue("K".$i, $strings["deposit_names"][$team_member_data["deposit_status"]])
                       ->setCellValue("L".$i, $strings["approved_names"][$application["approved"]])
                       ->setCellValue("M".$i, $application["timestamp"]);
            $i++;
        }
        $count++;
    }
    
}



function processor_student($excel_data) {

    global $db;
    global $strings;

    $excel_data->setActiveSheetIndex(0)
               ->setCellValue("A1", "번호")
               ->setCellValue("B1", "이름")
               ->setCellValue("C1", "성별")
               ->setCellValue("D1", "학교")
               ->setCellValue("E1", "학년")
               ->setCellValue("F1", "이메일")
               ->setCellValue("G1", "전화번호")
               ->setCellValue("H1", "보호자 이름")
               ->setCellValue("I1", "보호자 전화번호")
               ->setCellValue("J1", "참가 경로")
               ->setCellValue("K1", "자동 참가전환")
               ->setCellValue("L1", "참가 트랙")
               ->setCellValue("M1", "입금")
               ->setCellValue("N1", "제출 시간");

    $excel_data->getActiveSheet()->setTitle("전체 학생 데이터");

    $students = $db->in('kscy_students')
                   ->select("*")   
                   ->go_and_get_all();

    $i = 2;
    foreach ($students as $student) {
        
        $query = "SELECT `no` ,  'kscy_mentorings' AS table_name FROM  `kscy_mentorings` ";
        $query .= "WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_mentorings`.`team_members` ) > 0 ";
        $query .="UNION ";
        $query .="SELECT `no` ,  'kscy_camps' AS table_name FROM  `kscy_camps` ";
        $query .="WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_camps`.`team_members` ) > 0 ";
        $query .="UNION ";
        $query .="SELECT `no` ,  'kscy_papers' AS table_name FROM  `kscy_papers` ";
        $query .="WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_papers`.`team_members` ) > 0 ";
        $query .="UNION ";
        $query .="SELECT `no` ,  'kscy_plans' AS table_name FROM  `kscy_plans` ";
        $query .="WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_plans`.`team_members` ) > 0 ";

        $applied = $db->custom($query);
        $together = array();
        if ($applied) {
            foreach($applied as $row) {
                switch ($row["table_name"]) {
                    case "kscy_mentorings":
                        array_push($together, "멘토링");
                        break;
                    case "kscy_camps":
                        array_push($together, "캠프");
                        break;
                    case "kscy_papers":
                        array_push($together, "논문 발표");
                        break;
                    case "kscy_plans":
                        array_push($together, "연구계획 발표");
                        break;
                }
            }
        }

            $excel_data->setActiveSheetIndex(0)
                       ->setCellValue("A".$i, $student["no"])
                       ->setCellValue("B".$i, $student["name"])
                       ->setCellValue("C".$i, $student["gender"] == "male" ? "남자" : "여자")
                       ->setCellValue("D".$i, $student["school"])
                       ->setCellValue("E".$i, $student["grade"])
                       ->setCellValue("F".$i, $student["email"])
                       ->setCellValue("G".$i, $student["phone_number"])
                       ->setCellValue("H".$i, $student["guardian_name"])
                       ->setCellValue("I".$i, $student["guardian_phone_number"])
                       ->setCellValue("J".$i, $strings["survey_names"][$student["survey"]])
                       ->setCellValue("K".$i, $student["auto_switch"] == "1" ? "예" : "아니오")   
                       ->setCellValue("L".$i, implode(", ", $together))
                       ->setCellValue("M".$i, $strings["deposit_names"][$student["deposit_status"]])
                       ->setCellValue("N".$i, $student["timestamp"]);
            $i++;
        
    }
}

function processor_log($excel_data) {

    global $db;
    global $strings;

    $logs = $db->in('kscy_logs')
                       ->select("*")    
                       ->go_and_get_all();
    if (!$logs) {
        exit();
    }

    $excel_data->setActiveSheetIndex(0)
               ->setCellValue("A1", "번호")
               ->setCellValue("B1", "작업자")
               ->setCellValue("C1", "대상")
               ->setCellValue("D1", "행동")
               ->setCellValue("E1", "데이터")
               ->setCellValue("F1", "IP")
               ->setCellValue("G1", "작업 시간");

    $excel_data->getActiveSheet()->setTitle('작업 로그');
    $i = 2;
    foreach ($logs as $log) {
        $excel_data->setActiveSheetIndex(0)
                   ->setCellValue("A".$i, $log["no"])
                   ->setCellValue("B".$i, $log["user"])
                   ->setCellValue("C".$i, $log["target_user"])
                   ->setCellValue("D".$i, $log["action"])
                   ->setCellValue("E".$i, $log["data"])
                   ->setCellValue("F".$i, $log["ip"])
                   ->setCellValue("G".$i, $log["timestamp"]);
        $i++;
    }    
}

$response = process();

if ($response["result"] == "success") {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="export.xlsx"');
    header('Cache-Control: max-age=0');
    header('Cache-Control: max-age=1');
    header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
    header ('Cache-Control: cache, must-revalidate');
    header ('Pragma: public');

    $excel_writer = PHPExcel_IOFactory::createWriter($response["data"], 'Excel2007');
    $excel_writer->save('php://output');
}

exit();
?>