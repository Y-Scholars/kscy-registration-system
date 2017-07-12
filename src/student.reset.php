<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 07. 10
 */

error_reporting(0);

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./mail.php");

function process() {

    global $db;
    global $session;
    global $utils;
    global $mail;

    // 변수에 POST 값 읽어오기
    $user_student_email = $_POST["studentEmail"];

    // POST 값 유효성 검사
    $is_try = !empty($user_student_email);

    if (!$is_try) {
        return array(
            "result" => "pending"
        );
    }

    // 로그인 시도
    $student = $db->in('kscy_students')
                   ->select('*')
                   ->where('email', '=', $utils->purify($user_student_email))
                   ->go_and_get();

    if (!$student) {
        return array(
            "result" => "warning",
            "message" => "등록된 학생이 없습니다."
        );
    }

    // 새로운 비밀번호를 생성한다.
    $generated_password = bin2hex(openssl_random_pseudo_bytes(6));

    $password_update = $db->in('kscy_students')
                          ->update("password", hash("sha256", $generated_password))
                          ->where("no", "=", $student["no"])
                          ->go();

    if (!$password_update) {
        return array(
            "result" => "warning",
            "message" => "비밀번호 업데이트에 실패하였습니다."
        );
    }

    $email_title = "KSCY 계정 비밀번호를 알려드립니다";
    $email_content = "<b>" . $student["name"] . "</b>님의 새 비밀번호는 <b>" . $generated_password . "</b>입니다. 로그인 후 비밀번호를 변경해 주시기 바랍니다.<br/><a href=\"www.kscy.or.kr/register\">www.kscy.or.kr/register</a>";
    $email_sent = $mail->send($student["email"], $email_title, $email_content);

    if (!$email_sent) {
        return array(
            "result" => "warning",
            "message" => "메일 발송에 실패하였습니다."
        );
    }

    return array(
        "result" => "success"
    );
}


$response = process();

// 리다이렉트
if ($response["result"] == "success") {
    header("Location: ./message.php?type=reset-success");
    exit();
}

$title_korean = "비밀번호 찾기";
$title_english = "Password Reset";
    
include_once("./header.php");
?>

<div class="kscy-body">
<div class="ui container">
   
    <div class="ui icon message">
        <i class="warning circle icon"></i>
        <div class="content">
            <div class="header">유의사항</div>
            <p>학생 이메일 주소를 입력하면 해당 이메일 주소로 임시 비밀번호를 발송해 드립니다.</p>
        </div>
    </div>

    <?php if ($response["result"] == "warning") { ?>
    <div class="ui warning message">
        <p><?php echo($response["message"]);?></p>
    </div>
    <?php } ?>
    
    <form class="ui form" method="post" id="loginForm" style="">
        <div class="field" style="margin-top: 40px">
            <label>이메일 주소 (Email)</label>
            <input placeholder="이메일 주소 입력" name="studentEmail" type="text">
        </div>
        <button type="submit" class="ui submit button">임시 비밀번호 발송</button>
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