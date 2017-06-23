<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 23
 */

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

function process_student() {

    global $db;
    global $session;
    global $utils;

    // 접근 권한 검사
    if ($session->get_level() < 1)  {
        return array(
            "result" => "error",
            "message" => "접근 권한이 없습니다."
        );
    }

    // 데이터 불러오기
    $students = $db->in('kscy_students')
                        ->select("*")    
                        ->go_and_get_all();
    
    foreach ($students as &$student) {

        // 지원한 세션 검색
        $query = "SELECT `no` ,  'kscy_mentorings' AS table_name FROM  `kscy_mentorings` ";
        $query .= "WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_mentorings`.`team_members` ) > 0 UNION ";
        $query .="SELECT `no` ,  'kscy_camps' AS table_name FROM  `kscy_camps` ";
        $query .="WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_camps`.`team_members` ) > 0 UNION ";
        $query .="SELECT `no` ,  'kscy_papers' AS table_name FROM  `kscy_papers` ";
        $query .="WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_papers`.`team_members` ) > 0 UNION ";
        $query .="SELECT `no` ,  'kscy_plans' AS table_name FROM  `kscy_plans` ";
        $query .="WHERE FIND_IN_SET(  '".$student["no"]."',  `kscy_plans`.`team_members` ) > 0 ";

        //$applied_tracks_data = $db->custom($query);
        $applied_tracks_data = null;
        $applied_tracks = array("엑셀에서 확인 요망");

        if ($applied_tracks_data) {
            foreach($applied_tracks_data as $row) {
                switch ($row["table_name"]) {
                    case "kscy_mentorings":
                        array_push($applied_tracks, "멘토링");
                        break;
                    case "kscy_camps":
                        array_push($applied_tracks, "캠프");
                        break;
                    case "kscy_papers":
                        array_push($applied_tracks, "논문 발표");
                        break;
                    case "kscy_plans":
                        array_push($applied_tracks, "연구계획 발표");
                        break;
                }
            }
        }
        $student["applied_tracks"] = $applied_tracks;
    }
    unset($student);

    return array(
        "result" => "success",
        "data" => $students
    );
}

function render_student($response) {

    global $utils;
    global $strings;

    ?>

    <h2 class="ui header">학생 탐색기</h2>
    <a class="ui basic button" href="./dashboard.export.php?type=student"><i class="icon download"></i>엑셀로 내보내기...</a>
    <table class="ui structured celled table">
        <thead>
            <tr>
                <th>번호</th>
                <th>이름</th>
                <th class="one wide">성별</th>
                <th>학교</th>
                <th>전화번호</th>
                <th>이메일</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 1;
        foreach ($response["data"] as $student) {?>
            <tr>
                <td><?php echo($student["no"]);?></td>
                <td><a class="name student" data-no="<?php echo($student["no"]);?>"><?php echo($student["name"]);?></a></td>
                <td><?php echo($student["gender"] == "male" ? "남자" : "여자");?></td>
                <td><?php echo(mb_strimwidth($student["school"], 0, 35, '...'));?></td>
                <td><?php echo($student["phone_number"]);?></td>
                <td><?php echo($student["email"]);?></td>
        <?php
            $count++;
        } ?>
        </tbody>
    </table>
    <script>
    $('.ui.modal').modal();
    $('.ui.checkbox').checkbox();

    $('.ui.dropdown.deposit').on("change", function() {
        var self = this;
        $(self).addClass("disabled");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './dashboard.ajax.php',
            data: { action: "save", type: "student", no: $(self).data("no"), key: "deposit_status", value: $(self).val() },
            success: function (data) {
                $(self).removeClass("disabled");
            },
            error: function (request, status, error) {
                $(self).removeClass("disabled");
                alert("상태 업데이트에 실패했습니다.");
            }
        });
    });

    $('.student.name').on("click", function() {
        var self = this;
        $(self).addClass("loading");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './dashboard.ajax.php',
            data: { action: "load", type: "student", no: $(self).data("no")},
            success: function (data) {
                $(self).removeClass("loading");
                $("#studentNo").html(data.no);
                $("#studentName").html(data.name);
                $("#studentGender").html(data.gender == "male" ? "남자" : "여자");
                $("#studentSchool").html(data.school);
                $("#studentGrade").html(data.grade);
                $("#studentEmail").html(data.email);
                $("#studentPhone").html(data.phone_number);
                $("#studentGuardianName").html(data.guardian_name);
                $("#studentGuardianPhone").html(data.guardian_phone_number);
                $("#studentSurvey").html(data.survey);
                $("#studentSwitch").html(data.auto_switch == "1" ? "예" : "아니오");
                $("#studentTimestamp").html(data.timestamp);
                $('.ui.modal.student').modal('show');
            },
            error: function (request, status, error) {
                $(self).removeClass("loading");
                alert("데이터를 불러오지 못했습니다.");
            }
        });
    });

    </script>
<?php
}
?>