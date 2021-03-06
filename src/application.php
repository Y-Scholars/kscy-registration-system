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
require_once("./settings.php");
require_once("./application.tab.paper.php");
require_once("./application.tab.plan.php");
require_once("./application.tab.mentoring.php");
require_once("./application.tab.camp.php");

function process() {

    global $db;
    global $session;
    global $utils;
    global $settings;

    $review_mode = false;
    if (!empty($_GET["review"]) && $_GET["review"] == true) {
        $review_mode = true;
    }

    $delete_mode = false;
    if (!empty($_GET["delete"]) && $_GET["delete"] == true) {
        $delete_mode = true;
    }

    $admin_mode = false;
    $team_leader_no = -1;
    if (!empty($_GET["no"])) {
        $team_leader_no = intval($_GET["no"]);
    }

    // 권한이 없을 경우 로그인 페이지로 이동
    if (($review_mode || $delete_mode)) {

        // 관리자 권한으로 편집
        if ($team_leader_no >= 0) {
            $admin_mode = true;
            if ($session->get_level() < 2)  {
                header("Location: ./message.php?type=dashboard-error");
                exit();
            }
        }  

        else {
            if (empty($session->get_student_no())) {
                header("Location: ./authentication.php?redirect=".base64_encode("application.php?review=true"));
                exit();
            }
        }
    }

    if ($settings->is_closed() && $session->get_level() < 2) {
        header("Location: ./message.php?type=closed");
        exit();
    }

    // 탭 정보 읽어오기
    $tab = "paper";
    if (!empty($_GET["tab"])) {
        $tab = $_GET["tab"];
    }

    $student_no = $admin_mode ? $team_leader_no : $session->get_student_no();

    $available_tabs = array(
        "paper" => true,
        "plan" => true,
        "mentoring" => true,
        "camp" => true
    );

    // 사용 가능한 탭 읽어오기
    if ($review_mode) {

        $available_tabs = array(
            "paper" => is_available_tab("kscy_papers", $student_no),
            "plan" => is_available_tab("kscy_plans", $student_no),
            "mentoring" => is_available_tab("kscy_mentorings", $student_no),
            "camp" => is_available_tab("kscy_camps", $student_no),
        );

        $no_tab = true;
        foreach ($available_tabs as $tab_enabled) {
            $no_tab = $no_tab && !$tab_enabled;
        }

        if ($no_tab) {
            return array(
                "result" => "not-exists"
            );
        }
    }

    // 선택된 탭이 활성화되지 않았다면 첫 번째 탭을 선택
    if (empty($available_tabs[$tab]) || !$available_tabs[$tab]) {
        foreach ($available_tabs as $key => $value) {
            if ($value == true) {
                $tab = $key;
            }
        }
    }

    // 각 페이지 프로세싱
    switch ($tab) {
        case "paper":
            $response = process_paper($review_mode, $delete_mode, $student_no);
            break;
        case "plan":
            $response = process_plan($review_mode, $delete_mode, $student_no);
            break;
        case "mentoring":
            $response = process_mentoring($review_mode, $delete_mode, $student_no);
            break;
        case "camp":
            $response = process_camp($review_mode, $delete_mode, $student_no);
            break;
    }

    if ($admin_mode && $response["result"] == "success") {
       
        $log = $db->in('kscy_logs')
                    ->insert('user', $session->get_student_no())
                    ->insert('target_user', $student_no)
                    ->insert('action', "modify ". $tab . " application")
                    ->insert('data',  "")
                    ->insert('ip', $_SERVER['REMOTE_ADDR'])
                    ->go();
    }

    $response["available_tabs"] = $available_tabs;
    $response["admin"] = $admin_mode;
    $response["student_no"] = $student_no;
    $response["tab"] = $tab;
    return $response;
}

function is_available_tab($tab_name, $student_no) {

    global $db;
    
    $tab_exists = $db->in($tab_name)
                      ->select('no')
                      ->where('team_leader', '=', $student_no)
                      ->go_and_get();

    return $tab_exists ? true : false;
}

$response = process();

if ($response["result"] == "success") {

    // 관리자 모드라면 대시보드로 이동
    if ($response["admin"]) {
        header("Location: ./dashboard.php");
        exit();
    }
    header("Location: ./message.php?type=application" . ($response["review"] ? "-review" : ""));
    exit();
} else if ($response["result"] == "error") {
    header("Location: ./message.php?type=application-error");
    exit();
} else if ($response["result"] == "not-exists") {
    header("Location: ./message.php?type=application-not-exists");
    exit();
} else if ($response["result"] == "delete") {
    header("Location: ./message.php?type=application-delete");
    exit();
}

$title_korean = "지원서 " . ($response["review"] ? "조회" : "작성");
$title_english = "Application ". ($response["review"] ? "Review" : "Submission");

include_once("./header.php");
?>

<div class="ui modal" id="terms">
    <i class="close icon"></i>
    <div class="header">KSCY 운영 및 심사 방침</div>
    <div class="content">
        <h4 class="ui header">운영</h4>
        <p>한국청소년학술대회 KSCY(이하 KSCY) <a href="http://kscy.or.kr/register">지원시스템</a>에서의 일련의 등록과정은 2017년 7월 28일부터 29일까지 열리는 제8회 한국청소년학술대회 KSCY에 참가하기 위한 지원 절차입니다.
        본 ‘운영 및 심사 방침’을 포함한 ’학생등록’과 ‘지원서 작성’ 과정에서의 동의사항과 선택사항은 참가자 복지와 원활한 컨퍼런스 운영을 위해 사용됩니다. 반드시 읽고 체크하여 주시기 바랍니다.</p>
        <h4 class="ui header">심사</h4>
        <p>KSCY는 청소년 학술 대중화와 청소년 학자 정신(Young Scholar-ship)의 확산을 위해 운영되며 위시한 Social Mission의 달성을 위해 다음과 같은 컨퍼런스 운영 및 심사 규정을 가지고 있습니다.</p>
        <p>하나. 단순 연구의 완성도와 우열을 나누는 평가가 아닌, 청소년 학자 정신(Young Scholar-ship)의 실현과 가능성을 중요시 하는 심사 과정을 구축한다.</p>
        <p>하나. 청소년 시기 학술적 경험에서 경쟁을 절대적으로 지양하며 학술교류, 건설적 비판, 시너지효과를 지향한다.</p>
        <p>하나. KSCY 조직위원회는 가능한 많은 청소년을 대상으로 최고의 학술적 경험을 제공하기 위해 노력한다.</p>
        <h4 class="ui header">저작권 및 초상권</h4>
        <p>1. KSCY 지원 과정부터 컨퍼런스 현장에서 참가자에 의해 발표 및 연구되는 연구물들의 저작권은 청소년 참가자(저작자)에게 있습니다.</p>
        <p>2. KSCY 준비 과정부터 컨퍼런스 현장에서 KSCY에 의해 촬영 및 수집되는 사진, 영상, 데이터의 소유권은 KSCY에 있습니다.</p>
        <p>3. KSCY 준비 과정부터 컨퍼런스 현장에서 KSCY에 의해 촬영 및 수집되는 사진, 영상 등은 이후 별도의 허가 없이 촬영 및 공표(KSCY 아카이빙, 홍보, 브랜딩 등 활용)될 수 있습니다.</p>
        <p>4. <a href="http://db.kscy.org/">한국청소년학술데이터베이스</a>와 KSCY 개최 과정에서 가공 및 제작되는 연구 가공 데이터(DB, 논문집)의 운영 및 저작권은 KSCY에 있습니다.</p>
        <p>5. 참가자는 본인의 연구가 KSCY DB 및 논문집으로의 활용 및 발행되는것을 거부할 수 있으며 이는 대회 최소 1주 전 사무국측에 서면(이메일 또는 전화)으로 거부의사를 밝혀야 하며 거부의사에 대한 사무국측의 확인을 받아야 합니다.</p>
        <h4 class="ui header">환불</h4>
        <p>‘환불’이란 참가자가 더이상 참가를 원하지 않는 경우 요청할 수 있습니다. 다만 한국청소년학술대회 사무국에서는 원할한 컨퍼런스 준비를 위해 기간에 따른 환불비율을 적용하고 있습니다. 환불을 원하는 경우 <a href="mailto:office@kscy.kr">office@kscy.kr</a>로 메일로 요청 하실 수 있습니다. 기간에 따른 환불 비율은 아래와 같습니다.</p>
        <p>- 7월 14일 23시 59분까지 환불 요청 시 100% 환불</p>
        <p>- 7월 14일 23시 59분 이후 ~ 7월 21일 23시 59분 이전까지 환불 요청 시 50% 환불</p>
        <p>- 7월 21일 23시 59분 이후 환불 요청시 *환불 불가능*</p>
        <p><i>(환불 요청 메일 내용: 이름, 학교, 지원트랙 및 세션, 연락처, 환급계좌, 취소사유)</i></p>
    </div>
    <div class="actions">
        <div class="ui button" onclick="$('.ui.modal').modal('hide')">확인</div>
    </div>
</div>

<div class="kscy-body">
<div class="ui container">

    <?php if (!$response["review"]) { ?>
    <div class="ui icon message" style="margin-top: 40px">
        <i class="warning circle icon"></i>
        <div class="content">
            <div class="header">유의사항</div>
            <p>지원서는 논문 한 편당 한 번만 작성하면 되며, 지원서 제출 이후에도 팀장 인적사항으로 로그인한 후 수정이 가능합니다.</p>
        </div>
    </div>
    <?php } ?>

    <div class="ui top attached tabular menu" style="margin-top: 40px">
        <?php 
        if ($response["available_tabs"]["paper"]) { ?>
            <a class="<?php echo($response["tab"] == "paper" ? "active " : "")?>item" href="./application.php?tab=paper<?php echo($response["review"] ? "&review=true" : "");?><?php echo($response["admin"] ? "&no=".$response["student_no"] : "");?>">논문 발표</a><?php
        }
        if ($response["available_tabs"]["plan"]) { ?>
            <a class="<?php echo($response["tab"] == "plan" ? "active " : "")?>item" href="./application.php?tab=plan<?php echo($response["review"] ? "&review=true" : "");?><?php echo($response["admin"] ? "&no=".$response["student_no"] : "");?>">연구계획 발표</a><?php
        }
        if ($response["available_tabs"]["mentoring"]) { ?>
            <a class="<?php echo($response["tab"] == "mentoring" ? "active " : "")?>item" href="./application.php?tab=mentoring<?php echo($response["review"] ? "&review=true" : "");?><?php echo($response["admin"] ? "&no=".$response["student_no"] : "");?>">멘토링</a><?php
        }
        if ($response["available_tabs"]["camp"]) { ?>
            <a class="<?php echo($response["tab"] == "camp" ? "active " : "")?>item" href="./application.php?tab=camp<?php echo($response["review"] ? "&review=true" : "");?><?php echo($response["admin"] ? "&no=".$response["student_no"] : "");?>">캠프</a><?php
        } ?>
    </div>

    <div class="ui bottom attached segment">
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
        } ?>
    </div>
</div>
</div>
<?php
include_once("./footer.php");
?>