<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 18
 */

error_reporting(0);

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");

function process() {

    global $db;
    global $session;
    global $utils;

    // 변수에 POST 값 읽어오기
    $user_redirect = base64_decode(isset($_GET["redirect"]) ? $_GET["redirect"] : "./");
    $user_student_email = $_POST["studentEmail"];
    $user_student_password = $_POST["studentPassword"];

    // 로그아웃
    $user_logout = false;
    if (!empty($_GET["logout"])) {

        $session->delete_student_no();
        $user_logout = true;

        return array(
            "result" => "success",
            "redirect" => "./"
        );
    }

    // POST 값 유효성 검사
    $is_try = !empty($user_student_email);
    $is_valid = !(empty($user_student_email) ||  empty($user_student_password));

    if ($user_logout) {
        $session->delete_student_no();
    }

    if (!$is_try) {
        return array(
            "result" => "pending"
        );
    }

    // 유효하지 않은 값이 있다면
    if ($is_try && !$is_valid) {
        return array(
            "result" => "error",
            "message" => "올바른 값이 전달되지 않았습니다."
        );
    }

    // 로그인 시도
    $response = $db->in('kscy_students')
                   ->select('no')
                   ->select('level')
                   ->where('email', '=', $utils->purify($user_student_email))
                   ->where('password', '=', hash("sha256", $utils->purify($user_student_password)))
                   ->go_and_get();

    if (!$response) {
        return array(
            "result" => "warning",
            "message" => "이메일이나 비밀번호가 올바르지 않습니다."
        );
    }

    $session->set_level(intval($response["level"]));
    $session->set_student_no($response["no"]);

    return array(
        "result" => "success",
        "redirect" => $user_redirect
    );
}

$response = process();

// 리다이렉트
if ($response["result"] == "success") {
    header("Location: " . $response["redirect"]);
    exit();
}
else if ($response["result"] == "error") {
    header("Location: ./message.php?type=error");
    exit();
}

$title_korean = "학생 로그인";
$title_english = "Student Authentication";
    
include_once("./header.php");
?>

<div class="kscy-body">
<div class="ui container">
   
    <div class="ui icon message">
        <i class="warning circle icon"></i>
        <div class="content">
            <div class="header">유의사항</div>
            <p>학생 등록 시 입력했던 이메일과 비밀번호로 로그인이 가능합니다.</p>
        </div>
    </div>

    <?php if ($response["result"] == "warning") { ?>
    <div class="ui warning message">
        <p><?php echo($response["message"]);?></p>
    </div>
    <?php } ?>
    
    <form class="ui form" method="post" id="loginForm" style="">
        <div class="two fields" style="margin-top: 40px">
            <div class="field">
                <label>이메일 주소 (Email)</label>
                <input placeholder="이메일 주소 입력" name="studentEmail" type="text">
            </div>
            <div class="field">
                <label>비밀번호 (Password)</label>
                <input placeholder="비밀번호 입력" name="studentPassword" type="password">
            </div>
        </div>
        <button type="submit" class="ui submit button">로그인</button>
        <a class="ui button" href="./" >취소</a>
    </form>

</div>
</div>
<script>
$('.ui.form').form({
    fields: {
        studentEmail: 'empty',
        studentPassword: 'empty',
    }
});
</script>
<?php
include_once("./footer.php");
?>