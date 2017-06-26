<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 25
 */

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

function process_settings() {

    global $db;
    global $session;
    global $utils;

    // 접근 권한 검사
    if ($session->get_level() < 3)  {
        return array(
            "result" => "error",
            "message" => "접근 권한이 없습니다."
        );
    }

    return array(
        "result" => "success"
    );
}

function render_settings($response) {

    global $utils;
    global $strings;

    ?>
    <h2 class="ui header">대시보드 설정</h2>
    <div class="ui warning message">
        <div class="header">주의하세요</div>
        대시보드 설정은 매우 민감하므로 설정에 유의하시기 바랍니다.
    </div>
    <form class="ui form">
        <div class="field">
            <label>참가접수 관리</label>
            <div class="ui checkbox">
                <input type="checkbox" tabindex="0" class="hidden">
                <label>접수 마감하기</label>
            </div>
        </div>
        <div class="field">
            <label>참가자 데이터 관리</label>
            <a class="ui basic button">전체 내보내기</a>
            <a class="ui basic button">전체 불러오기</a>
            <a class="ui basic negative button">초기화</a>
        </div>
        <div class="field">
            <label>파일 관리</label>
            <a class="ui basic button">전체 내려받기</a>
            <a class="ui basic negative button">초기화</a>
        </div>
    </form>
    <script>
    $('.ui.checkbox').checkbox();
    </script>
<?php
}
?>