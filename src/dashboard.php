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
require_once("./dashboard.tab.paper.php");
require_once("./dashboard.tab.plan.php");
require_once("./dashboard.tab.mentoring.php");
require_once("./dashboard.tab.camp.php");
require_once("./dashboard.tab.session.php");
require_once("./dashboard.tab.student.php");

error_reporting(-1);
function process() {

    global $db;
    global $session;
    global $utils;
    global $strings;

    $tab = "paper";
    if (!empty($_GET["tab"])) {
        $tab = trim($_GET["tab"]);
    }

    $no = "1";
    if (!empty($_GET["no"])) {
        $no = trim($_GET["no"]);
    }

    // 관리자 권한이 없다면
    if ($session->get_level() < 1)  {
        if (empty($session->get_student_no())) {
            header("Location: ./message.php?type=dashboard-error");
        } else {
            header("Location: ./authentication.php?redirect=".base64_encode("dashboard.php"));
        }
        exit();
    }

    // 각 행의 수 불러오기
    $statistics = $db->in('kscy_statistics')
                     ->select('key')
                     ->select('value')
                     ->go_and_get_all();

    if (!$statistics) {
        return array(
            "result" => "error",
            "message" => "통계 정보를 불러오는 데 실패하였습니다."
        );
    }

    $total_students = 0;
    $total_papers = 0;
    $total_plans = 0;
    $total_mentorings = 0;
    $total_camps = 0;

    foreach ($statistics as $row) {
        switch ($row["key"]) {
            case "total_students":
                $total_students = intval($row["value"]);
                break;
            case "total_papers":
                $total_papers = intval($row["value"]);
                break;
            case "total_plans":
                $total_plans = intval($row["value"]);
                break;
            case "total_mentorings":
                $total_mentorings = intval($row["value"]);
                break;
            case "total_camps":
                $total_camps = intval($row["value"]);
                break;
        }
    }

    $counter = array(
        "total_students" => $total_students,
        "total_papers" => $total_papers,
        "total_plans" => $total_plans,
        "total_mentorings" => $total_mentorings,
        "total_camps" => $total_camps
    );

    switch ($tab) {
        case "paper":
            $response = process_paper();
            break;
        case "plan":
            $response = process_plan();
            break;
        case "mentoring":
            $response = process_mentoring();
            break;
        case "camp":
            $response = process_camp();
            break;
        case "student":
            $response = process_student();
            break;
        case "session":
            $response = process_session();
            break;
    }

    $response["tab"] = $tab;
    $response["no"] = $no;
    $response["counter"] = $counter;

    return $response;
}

$response = process();

if ($response["result"] == "error") {
    header("Location: ./message.php?type=dashboard-error");
    exit();
}

$title_korean = "대시보드";
$title_english = "Dashboard";

include_once("./header.php");
?>
<div class="ui modal student">
    <div class="header">학생 세부 정보</div>
    <div class="content">
        <table class="ui selectable definition celled sortable table">
            <tbody>
                <tr>
                    <td>번호</td>
                    <td id="studentNo">없음</td>
                </tr>
                <tr>
                    <td>이름</td>
                    <td id="studentName">없음</td>
                </tr>
                <tr>
                    <td>성별</td>
                    <td id="studentGender">없음</td>
                </tr>
                <tr>
                    <td>학교</td>
                    <td id="studentSchool">없음</td>
                </tr>
                <tr>
                    <td>학년</td>
                    <td id="studentGrade">없음</td>
                </tr>
                <tr>
                    <td>이메일 주소</td>
                    <td id="studentEmail">없음</td>
                </tr>
                <tr>
                    <td>전화번호</td>
                    <td id="studentPhone">없음</td>
                </tr>
                <tr>
                    <td>보호자 이름</td>
                    <td id="studentGuardianName">없음</td>
                </tr>
                <tr>
                    <td>보호자 연락처</td>
                    <td id="studentGuardianPhone">없음</td>
                </tr>
                <tr>
                    <td>참가 경로</td>
                    <td id="studentSurvey">없음</td>
                </tr>
                <tr>
                    <td>자동 참가전환 여부</td>
                    <td id="studentSwitch">없음</td>
                </tr>
                <tr>
                    <td>등록 일시</td>
                    <td id="studentTimestamp">없음</td>
                </tr>
            </tbody>
        </table>
        <button class="ui basic button"><i class="icon write"></i>학생 정보 수정</button>
        <button class="ui basic button"><i class="icon trash"></i>학생 삭제</button>
    </div>
    <div class="actions">
        <div class="ui cancel button">닫기</div>
    </div>
</div>
<div class="kscy-body">
<div class="ui container">
    <div class="ui grid">
        <div class="four wide column">
            <div class="ui vertical accordion menu fluid">
                <a class="item<?php echo($response["tab"] == "student" ? " active" : "");?>" href="./dashboard.php?tab=student">
                    학생 탐색기
                    <div class="ui blue left pointing label"><?php echo($response["counter"]["total_students"]);?></div>
                </a>
                <a class="item<?php echo($response["tab"] == "paper" ? " active" : "");?>" href="./dashboard.php?tab=paper">
                    논문 발표
                    <div class="ui blue left pointing label"><?php echo($response["counter"]["total_papers"]);?></div>
                </a>
                <a class="item<?php echo($response["tab"] == "plan" ? " active" : "");?>" href="./dashboard.php?tab=plan">
                    연구계획 발표
                    <div class="ui blue left pointing label"><?php echo($response["counter"]["total_plans"]);?></div>
                </a>
                <a class="item<?php echo($response["tab"] == "mentoring" ? " active" : "");?>" href="./dashboard.php?tab=mentoring">
                    멘토링 참가
                    <div class="ui blue left pointing label"><?php echo($response["counter"]["total_mentorings"]);?></div>
                </a>
                <a class="item<?php echo($response["tab"] == "camp" ? " active" : "");?>" href="./dashboard.php?tab=camp">
                    캠프 참가
                    <div class="ui blue left pointing label"><?php echo($response["counter"]["total_camps"]);?></div>
                </a>
                <div class="item<?php echo($response["tab"] == "session" ? " active" : "");?>">
                    <a class="active title">세션별 지원서 보기<i class="dropdown icon"></i></a>
                    <div class="content active">
                        <div class="ui ordered link list">
                        <?php foreach ($strings["session_names"] as $key => $value) {
                            echo ('<a class="item'.(($response["no"] == $key && $response["tab"] == "session" )? " active" : "").'" href="./dashboard.php?tab=session&no='.$key.'">'.$value.'</a>');
                        } ?>
                        </div>
                    </div>
                </div>
                <a class="item">통계</a>
                <a class="item">방 배치</a>
            </div>
        </div>
        <div class="twelve wide stretched column">
            <div class="ui segment">
            <?php switch ($response["tab"]) {
                case "paper":
                    echo(render_paper($response));
                    break;
                case "plan":
                    echo(render_plan($response));
                    break;
                case "mentoring":
                    echo(render_mentoring($response));
                    break;
                case "camp":
                    echo(render_camp($response));
                    break;
                case "student":
                    echo(render_student($response));
                    break;
                case "session":
                    echo(render_session($response));
                    break;
            } ?>
            </div>
        </div>
    </div>
</div>
</div>
<script>
$('.ui.accordion').accordion();
</script>
<?php
include_once("./footer.php");
?>