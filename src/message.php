<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 15
 */

error_reporting(0);

function process() {

    $type = $_GET["type"];

    if (!isset($_GET["type"])) {
        header("Location: ./");
        exit();
    }

    $message_title = "";
    $message_type = "";
    $message_text = "";

    switch ($type) {
        case "error":
            $message_title = "에러가 발생하였습니다";
            $message_type = "negative";
            $message_text = "잘못된 요청이거나 접근입니다. 다시 시도해 주시기 바랍니다.";
            break;
        case "student":
            $message_title = "학생 등록이 완료되었습니다";
            $message_type = "success";
            $message_text = "지원서를 작성하여 참가 접수 절차를 마무리하여 주시기 바랍니다.";
            break;
        case "student-review":
            $message_title = "학생 정보 수정이 완료되었습니다";
            $message_type = "success";
            $message_text = "마감일 전까지 학생 정보는 자유롭게 수정하실 수 있습니다.";
            break;
        case "student-error":
            $message_title = "학생 정보 등록에 실패하였습니다.";
            $message_type = "negative";
            $message_text = "다시 시도해 주시기 바랍니다.";
            break;    
        case "application":
            $message_title = "지원서 제출이 완료되었습니다";
            $message_type = "success";
            $message_text = "마감일 전까지 지원서는 자유롭게 수정하실 수 있습니다.";
            break;
        case "application-earlybird":
            $message_title = "지원서 제출이 완료되었습니다";
            $message_type = "success";
            $message_text = "캠프/멘토링 트랙의 경우 조기접수 마감일인 6월 21일 (수) 까지 입금을 마치면 희망 세션에 대한 참가가 보장됩니다.";
            break; 
        case "application-review":
            $message_title = "지원서 수정이 완료되었습니다";
            $message_type = "success";
            $message_text = "마감일 전까지 지원서는 자유롭게 수정하실 수 있습니다.";
            break;
        case "application-not-exists":
            $message_title = "본 학생이 팀장인 지원서가 없습니다";
            $message_type = "negative";
            $message_text = "지원서를 작성하거나, 팀장 정보로 다시 시도해 주세요.";
            break;
        case "application-delete":
            $message_title = "지원서 삭제가 완료되었습니다";
            $message_type = "success";
            $message_text = "마감일 전까지 지원서는 자유롭게 제출하실 수 있습니다.";
            break;
        case "dashboard-error":
            $message_title = "대시보드 엑세스에 실패하였습니다";
            $message_type = "negative";
            $message_text = "관리자 권한이 있는지 다시 확인해 주세요.";
            break;
    }

    return array(
        "title" => $message_title,
        "type" => $message_type,
        "text" => $message_text
    );
}

$response = process($review_mode);

$title_korean = "참가접수 시스템";
$title_english = "Registration System";

include_once("header.php");
?>
<div class="kscy-body">
<div class="ui container">
    <div class="ui <?php echo($response["type"]);?> message">
        <div class="header">
            <?php echo($response["title"]);?>
        </div>
        <p><?php echo($response["text"]);?></p>
        <a class="ui button" href="./">처음으로 돌아가기</a>
    </div>
</div>
</div>
<?php
include "footer.php";
?>