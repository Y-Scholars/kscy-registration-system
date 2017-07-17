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

    <h2 class="ui header">합격자 발표</h2>
    <a href="./files/etc/8thKSCY_FinalList_0717.xlsx" class="ui button primary">합격자 명단</a>
    
    <h2 class="ui header">합격자 유의사항</h2>
    <a href="./files/etc/8thKSCY_Paper_Guideline.pdf" class="ui button basic">논문 및 연구계획 발표자</a>
    <a href="./files/etc/8thKSCY_Mentoring_Guideline.pdf" class="ui button basic">멘토링 참가자</a>
    <a href="./files/etc/8thKSCY_Camp_Guideline.pdf" class="ui button basic">캠프 참가자</a>
    <a href="./files/etc/8thKSCY_Transfer_Guideline.pdf" class="ui button basic">참가유형 전환자</a>

    <h2 class="ui header">기타 서류</h2>
    <a href="./files/etc/8thKSCY_Camp_Agreement.pdf" class="ui button basic">캠프 참가자 서약서</a>
    <br/>
    <a class="ui button" href="./" style="margin-top: 30px">돌아가기</a>
</div>
</div>

<script>

</script>
<?php
include_once("./footer.php");
?>
