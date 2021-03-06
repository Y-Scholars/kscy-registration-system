<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 15
 */

error_reporting(0);

require_once("./session.php");
require_once("./settings.php");

$title_korean = "참가접수 시스템";
$title_english ="Registration System";

include_once("./header.php");
?>
<div class="kscy-body">
<div class="ui container">
<h2 class="ui header">참가접수 절차</h2>
<div class="ui stackable fluid four steps">
    <div class="<?php echo($settings->is_closed() ? "disabled " : "");?>step">
        <div class="content">
            <div class="title">학생 등록</div>
            <div class="kscy-body-step-text">제8회 KSCY에 참가하는 모든 학생들은 지원서를 작성하기 전, 학생 정보를 등록해 주시기 바랍니다.</div>
            <div class="kscy-body-step-buttons">
                <a class="<?php echo($settings->is_closed() ? "disabled " : "");?>ui button" href="./student.php">학생 등록</a>
                <a class="<?php echo($settings->is_closed() ? "disabled " : "");?>ui button" href="./student.php?review=true" style="margin-top: 5px">정보 수정</a>
            </div>
            <div class="kscy-body-step-label">
                <div class="<?php echo($settings->is_closed() ? "disabled " : "");?>ui basic label">마감<div class="detail">7월 12일 (수)</div></div>
            </div>
        </div>
    </div>
    <div class="<?php echo($settings->is_closed() ? "disabled " : "active ");?>step">
        <div class="content">
            <div class="title">지원서 작성</div>
            <div class="kscy-body-step-text">소기의 양식에 따라 지원서를 작성해 주시기 바랍니다. 제출된 지원서는 마감일 전까지 수정이 가능합니다.</div>
            <div class="kscy-body-step-buttons">
                <a class="<?php echo($settings->is_closed() ? "disabled " : "");?>ui button" href="./application.php">지원서 작성</a>
                <a class="<?php echo($settings->is_closed() ? "disabled " : "");?>ui button" href="./application.php?review=true" style="margin-top: 5px">지원서 수정</a>
            </div>
            <div class="kscy-body-step-label">
                <div class="<?php echo($settings->is_closed() ? "disabled " : "");?>ui basic label">마감<div class="detail">7월 12일 (수)</div></div>
            </div>
        </div>
    </div>
    <div class="<?php echo($settings->is_closed() ? "active " : "");?>step">
        <div class="content">
            <div class="title">심사 결과</div>
            <div class="kscy-body-step-text">이 곳에서 합격 여부를 확인하실 수 있습니다. 합격 확인 후 입금이 완료되면 지원절차가 마무리됩니다.</div>
            <div class="kscy-body-step-buttons">
                <a class="ui button" href="./congrats.php">심사 결과 확인</a>
            </div>
            <div class="kscy-body-step-label">
                <div class="ui basic label">발표<div class="detail">7월 17일 (월)</div></div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php
include_once("./footer.php");
?>