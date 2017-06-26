<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 26
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

    
    // 권한 체크
    if (empty($session->get_student_no())) {
        header("Location: ./authentication.php?redirect=".base64_encode("status.php"));
        exit();
    }

    $student_no = $session->get_student_no();

    // 학생 정보 불러오기
    $student_data = $db->in("kscy_students")
                  ->select("*")
                  ->where("no", "=", $student_no)
                  ->go_and_get();

    // 지원한 모든 서류 불러오기
    $query = "SELECT `no` ,  'kscy_mentorings' AS table_name FROM  `kscy_mentorings` ";
    $query .= "WHERE FIND_IN_SET(  '".$student_no."',  `kscy_mentorings`.`team_members` ) > 0 ";
    $query .="UNION ";
    $query .="SELECT `no` ,  'kscy_camps' AS table_name FROM  `kscy_camps` ";
    $query .="WHERE FIND_IN_SET(  '".$student_no."',  `kscy_camps`.`team_members` ) > 0 ";
    $query .="UNION ";
    $query .="SELECT `no` ,  'kscy_papers' AS table_name FROM  `kscy_papers` ";
    $query .="WHERE FIND_IN_SET(  '".$student_no."',  `kscy_papers`.`team_members` ) > 0 ";
    $query .="UNION ";
    $query .="SELECT `no` ,  'kscy_plans' AS table_name FROM  `kscy_plans` ";
    $query .="WHERE FIND_IN_SET(  '".$student_no."',  `kscy_plans`.`team_members` ) > 0 ";

    $entries = $db->custom($query);
    $applications = array();

    foreach ($entries as $entry) {
        $application = $db->in($entry["table_name"])
                               ->select("*")
                               ->where("no", "=", $entry["no"])
                               ->go_and_get();

        switch ($entry["table_name"]) {
            case "kscy_papers":
                $application["type"] = "논문 발표 (" . mb_strimwidth($application["title"], 0, 25, '...') . ")";
                break;
            case "kscy_plans":
                $application["type"] = "연구계획 발표 (" . mb_strimwidth($application["title"], 0, 25, '...') . ")";
                break;
            case "kscy_mentorings":
                $application["type"] = "연구 멘토링 참가";
                break;
            case "kscy_camps":
                $application["type"] = "캠프 참가";
                break;
        }

        array_push($applications, $application);
    }

    if (count($applications) < 1) {
        return array(
            "result" => "error",
            "message" => "제출된 지원서가 없습니다."
        );
    }

    return array(
        "result" => "success",
        "data" => $applications,
        "student" => $student_data
    );
}

$response = process();

if ($response["result"] == "error") {
    header("Location: ./message.php?type=status-error");
    exit();
}

$title_korean = "심사 결과";
$title_english = "Application Status";

include_once("./header.php");
?>

<div class="kscy-body">
<div class="ui container">

    <div class="ui icon message">
        <i class="warning circle icon"></i>
        <div class="content">
            <div class="header">유의사항</div>
            <p>합격 확인 후 입금이 완료되어야 참가가 확정됩니다.</p>
        </div>
    </div>

    <table class="ui celled table fluid" style="margin-top: 40px">
        <thead>
            <tr>
                <th>이름</th>
                <th>학교</th>
                <th>학년</th>
                <th>신청 트랙</th>
                <th>심사 결과</th>
                <th>입금</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($response["data"] as $application) { ?>
            <tr>
            <td><?php echo($response["student"]["name"]);?></td>
            <td><?php echo($response["student"]["school"]);?></td>
            <td><?php echo($strings["grade_names"][$response["student"]["grade"]]);?></td>
            <td><?php echo($application["type"]);?></td>
            <td><?php echo($strings["approved_names"][$application["approved"]]);?></td>
            <td><?php echo($strings["deposit_names"][$response["student"]["deposit_status"]]);?></td>
            </tr>
            <?php } ?>

        </tbody>
    </table>
    <a class="ui button" href="./">돌아가기</a>
</div>
</div>

<script>

</script>
<?php
include_once("./footer.php");
?>
