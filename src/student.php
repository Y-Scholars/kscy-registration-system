<?php

error_reporting(0);

require_once("db.php");
require_once("utils.php");
require_once("session.php");

function process($review_mode) {

    global $db;
    global $session;
    global $utils;

    // 변수로 POST 값들 읽어오기
    $user_student_name = $_POST["studentName"];
    $user_student_school_name = $_POST["studentSchoolName"];
    $user_student_school_grade = $_POST["studentSchoolGrade"];
    $user_student_phone_number = $_POST["studentPhoneNumber"];
    $user_student_email = $_POST["studentEmail"];
    $user_student_password = $_POST["studentPassword"];
    $user_student_gender = $_POST["studentGender"];
    $user_student_guardian_name = $_POST["studentGuardianName"];
    $user_student_guardian_phone_number = $_POST["studentGuardianPhoneNumber"];
    $user_student_survey = $_POST["studentSurvey"];
    $user_student_switch_option = $_POST["studentSwitchOption"] == "yes" ? "1" : "0";
    
    // POST 값들의 유효성을 체크
    $is_try = !empty($user_student_name);
    $is_valid = !(empty($user_student_name) || 
                  empty($user_student_school_name) || 
                  empty($user_student_school_grade) || 
                  empty($user_student_phone_number) || 
                  empty($user_student_email) || 
                  empty($user_student_password) || 
                  empty($user_student_survey));

    // 리뷰 모드라면, 서버로부터 데이터 로드
    if ($review_mode) {

        // 로그인된 정보로부터 불러오기
        $student_data = $db->in('kscy_students')
                           ->select('no')
                           ->select('name')
                           ->select('school')
                           ->select('grade')
                           ->select('phone_number')
                           ->select('email')
                           ->select('password')
                           ->select('gender')
                           ->select('guardian_name')
                           ->select('guardian_phone_number')
                           ->select('survey')
                           ->select('auto_switch')
                           ->where('no', '=', $session->get_student_no())
                           ->goAndGet();

        if (!$student_data) {
            return array(
                "result" => "error",
                "message" => "해당하는 학생 정보가 존재하지 않습니다."
            );
        }
    }

    if (!$is_try) {
        return array(
            "result" => "pending",
            "data" => $student_data
        );
    }

    // 유효하지 않은 값이 있다면
    if ($is_try && !$is_valid) {
        return array(
            "result" => "warning",
            "message" => "올바른 값이 전달되지 않았습니다."
        );
    }

    // 데이터베이스 업데이트
    if ($review_mode) {
        $response = $db->in('kscy_students')
                       ->update('name', $utils->purify($user_student_name))
                       ->update('school', $utils->purify($user_student_school_name))
                       ->update('grade', $utils->purify($user_student_school_grade))
                       ->update('phone_number', $utils->purify($user_student_phone_number))
                       ->update('password', hash("sha256", $utils->purify($user_student_password)))
                       ->update('gender', $utils->purify($user_student_gender))
                       ->update('guardian_name', $utils->purify($user_student_guardian_name))
                       ->update('guardian_phone_number', $utils->purify($user_student_guardian_phone_number))
                       ->update('survey', $utils->purify($user_student_survey))
                       ->update('auto_switch', $utils->purify($user_student_switch_option))
                       ->where('no', '=', $session->get_student_no())
                       ->go();

        if (!$response) {
            return array(
                "result" => "error",
                "message" => "업데이트 도중 에러가 발생하였습니다."
            );
        }
    } 


    // 데이터베이스 신규 등록
    else {

        // 중복 학생 등록 검사
        $response = $db->in('kscy_students')
                       ->select('email')
                       ->where('email', '=', $utils->purify($user_student_email))
                       ->goAndGet();

        if ($response) {
            return array(
                "result" => "warning",
                "message" => "이미 등록된 이메일 주소입니다."
            );
        }

        $response = $db->in('kscy_students')
                       ->insert('name', $utils->purify($user_student_name))
                       ->insert('school', $utils->purify($user_student_school_name))
                       ->insert('grade', $utils->purify($user_student_school_grade))
                       ->insert('phone_number', $utils->purify($user_student_phone_number))
                       ->insert('email', $utils->purify($user_student_email))
                       ->insert('password', hash("sha256", $utils->purify($user_student_password)))
                       ->insert('gender', $utils->purify($user_student_gender))
                       ->insert('guardian_name', $utils->purify($user_student_guardian_name))
                       ->insert('guardian_phone_number', $utils->purify($user_student_guardian_phone_number))
                       ->insert('survey', $utils->purify($user_student_survey))
                       ->insert('auto_switch', $utils->purify($user_student_switch_option))
                       ->go();

        $statistics = $db->in('kscy_statistics')
                         ->update('value', '`value` + 1 ', true)
                         ->where('key', '=', 'total_students')
                         ->go();

        if (!$response) {
            return array(
                "result" => "error",
                "message" => "학생 등록 도중 에러가 발생하였습니다."
            );
        }
    }

    return array(
                "result" => "success",
                "data" => $student_data
            );
}

$review_mode = false;
if (!empty($_GET["review"]) && $_GET["review"] == true && isset($_SESSION['student_no'])) {
    $review_mode = true;
}

$response = page_student($review_mode);

$title_korean = $review_mode ? "학생 조회" : "학생 등록";
$title_english = $review_mode ? "Student Review" : "Student Registration";

include "header.php";

?>

    <div class="ui one column centered doubling stackable grid container">
        <div class="column">


<?php
if ($response["result"] == "success") {
?>

<div class="ui success message" style="margin-top: 40px">
  <div class="header">
    학생 등록이 완료되었습니다.
  </div>
  <p>지원서를 작성하여 참가 접수 절차를 마무리해 주시길 바랍니다.</p>
  <a class="positive basic ui button" href="./">처음으로 돌아가기</a>
  <a class="positive basic ui button" href="./application.php">지원서 작성하기</a>

</div>


<?php
} else if ($response["result"] == "error") {
?>

<div class="ui negative message" style="margin-top: 40px">
  <div class="header">
    학생 등록 실패
  </div>
  <p><?php echo($response["message"]); ?></p>
</div>

<?php
}  else if ($response["result"] == "pending" || $response["result"] == "warning") {
?>




            <div class="ui icon message" style="margin-top: 40px">
                <i class="warning circle icon"></i>
                <div class="content">
                    <div class="header">
                        유의사항
                    </div>
                    <p>참가 학생 등록만으로는 KSCY에 참가 신청된 것이 아닙니다. 참가 학생 등록 후 반드시 지원서를 작성하여 신청 절차를 완료해 주세요.</p>
                </div>
            </div>

            <?php
if ($response["result"] == "warning") {
?>
<div class="ui warning message">
  <p><?php echo($response["message"]); ?></p>
</div>
<?php
}
?>



<div class="ui modal" id="priv">
  <i class="close icon"></i>
  <div class="header">
    개인정보처리방침
  </div>
  <div class=" content">
  <p> 1. 수집하는 개인정보 항목
한국청소년학술대회는 (이하 '단체')은(는) 원할한 회의 참가를 위해 개인정보를 수집하고 있습니다.
<br/>* 수집항목 : 위 지원서 항목
<br/>* 개인정보 수집방법 : 홈페이지 </p>
 <p>
2. 개인정보의 수집 및 이용목적
단체는 수집한 개인정보를 다음의 목적을 위해 활용합니다.
서비스 이용에 따른 본인확인, 개인 식별, 불량회원의 부정 이용 방지와 비인가 사용 방지, 분쟁 조정을 위한 기록보존, 불만처리 등 민원처리, 고지사항 전달 및 다양한 정보 제공, 고객만족도 조사, 설문조사, 본인 의사 확인, 회원관리, 신규 서비스 안내 등
 </p><p>
3. 개인정보의 보유 및 이용기간
개인정보의 보유 및 이용 기간은 해지시까지입니다.
 <p>
4. 동의를 거부할 권리가 있다는 사실과 동의 거부에 따른 불이익 내용
이용자는 수집하는 개인정보에 대해 동의를 거부할 권리가 있으며 동의 거부 시에는 서비스가 제한됩니다.
  </p></div>
  <div class="actions">
    <div class="ui button" onclick="$('.ui.modal').modal('hide')">확인</div>
  </div>
</div>


<div class="ui modal" id="chan">
  <i class="close icon"></i>
  <div class="header">
    자동 참가유형 전환 제도
  </div>
  <div class=" content">


<p>KSCY참가전환제도는 <u>연구논문발표트랙</u>,  <u>연구계획발표트랙</u> 지원자를 대상으로 KSCY 참가 선택권을 넓히기 위해 기획된 제도입니다. <p> 
<p>본 참가전환제도에 동의할 경우 지원한 세션의 발표자로 선발이 되지 못한 상황에서 자동으로 동일 세션 <u>연구멘토링트랙</u> 또는 <u>캠프트랙</u>으로 변경됩니다. <p> 

<p><i>1. 연구논문발표 > 동일세션 연구멘토링 트랙(옵저버) 전환</i><p> 
<p><i>2. 연구계획발표 > 동일세션 연구논문작성캠프 트랙 또는 연구멘토링 트랙(옵저버) 전환</i><p> 

<p>참가트랙이 전환된 지원자는 해당 트랙의 참가비를 정해진 기한안에 입금하며 등록절차를 마치면 KSCY 참가가 확정됩니다. <p> 
<p><i>(지원 세션에 공석이 생겨 추가발표자 선발이 가능해질 경우 추가합격을 진행합니다)</i><p> 


</div>
  <div class="actions">
    <div class="ui button" onclick="$('.ui.modal').modal('hide')">확인</div>
  </div>
</div>



            <form class="ui form" method="post">
                <h4 class="ui dividing header" style="margin-top: 40px">기본 정보</h4>
                <div class="field required">
                    <label>성명 (Name)</label>
                    <div class="field">
                        <input type="text" name="studentName" placeholder="이름" value="<?php $utils->display($response["data"]["name"]);?>">
                    </div>
                </div>
                <div class="field required">
                    <label>소속 (Affiliation)</label>
                    <div class="fields">
                        <div class="twelve wide field">
                            <input type="text" name="studentSchoolName" placeholder="학교 이름" value="<?php $utils->display($response["data"]["school"]);?>">
                        </div>
                        <div class="four wide field">
                            <select class="ui fluid dropdown" name="studentSchoolGrade" id="studentSchoolGrade">
                                <option value="1">중학교 1학년</option>
                                <option value="2">중학교 2학년</option>
                                <option value="3">중학교 3학년</option>
                                <option value="4">고등학교 1학년</option>
                                <option value="5">고등학교 2학년</option>
                                <option value="6">고등학교 3학년</option>
                                <option value="7">기타 (검정고시 등)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="field required">

                    <label>연락처 (Contact)</label>


                    <div class="fields">

                        <div class="six wide field">
                            <input type="text" name="studentPhoneNumber" placeholder="전화번호" value="<?php $utils->display($response["data"]["phone_number"]);?>">
                        </div>
                        <div class="ten wide field<?php if ($review_mode) { echo(" disabled");}?>">
                            <input type="email" name="studentEmail" placeholder="이메일 주소" value="<?php $utils->display($response["data"]["email"]);?>">
                        </div>
                    </div>
                </div>

                <div class="field required">

                    <label>비밀번호 (Password)</label>


                    <div class="fields">

                        <div class="eight wide field">
                            <input type="password" name="studentPassword" placeholder="비밀번호">
                        </div>
                        <div class="eight wide field">
                            <input type="password" name="studentPasswordRepeat" placeholder="비밀번호 재입력">
                        </div>
                    </div>
                </div>





                <h4 class="ui dividing header" style="margin-top: 40px">추가 정보 <small><em>(캠프 참여 예정인 학생만 작성)</em></small></h4>


                <?php
                    $gender_option = true;
                    if (isset($response["data"]["gender"])) {
                        $gender_option = ($response["data"]["gender"] == "male");
                    }
                ?>


                <div class="field">
                    <label>성별 (Gender)</label>
                    <div class="inline fields">
                        <div class="field">
                            <div class="ui radio checkbox">
                                <input type="radio" value="male" name="studentGender" <?php echo($gender_option ? "checked=\"true\"" : "");?>  tabindex="0" class="hidden">
                                <label>남자</label>
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui radio checkbox">
                                <input type="radio" value="female" name="studentGender" <?php echo(!$gender_option ? "checked=\"true\"" : "");?>  tabindex="1" class="hidden">
                                <label>여자</label>
                            </div>
                        </div>
                    </div>
                </div>


                <div class="field">

                    <label>보호자 (Guardian)</label>


                    <div class="fields">

                        <div class="six wide field">
                            <input type="text" name="studentGuardianName" placeholder="보호자 성명" value="<?php $utils->display($response["data"]["guardian_name"]);?>">
                        </div>
                        <div class="ten wide field">
                            <input type="text" name="studentGuardianPhoneNumber" placeholder="보호자 연락처" value="<?php $utils->display($response["data"]["guardian_phone_number"]);?>">
                        </div>
                    </div>
                </div>


                <h4 class="ui dividing header" style="margin-top: 40px">기타</h4>

                <div class="field required">
                    <label>KSCY를 알게 된 경로 (Survey)</label>
                       
                        <div class="field">
                            <select class="ui fluid dropdown" name="studentSurvey" id="studentSurvey">
                                <option value="1">SNS (페이스북)</option>
                                <option value="2">학교에 게시된 포스터</option>
                                <option value="3">선생님의 권유</option>
                                <option value="4">부모님의 권유</option>
                                <option value="5">주변 친구 및 선배의 권유</option>
                                <option value="6">기타</option>
                            </select>
                    </div>
                </div>

                <?php
                    $switch_option = true;
                    if (isset($response["data"]["auto_switch"])) {
                        $switch_option = ($response["data"]["auto_switch"] == "1");
                    }
                ?>

                <div class="field">
                    <a onclick="$('#chan').modal('show')">참가유형 전환제도</a>에 따라 참가유형전환을 신청하겠습니다.
                    <div class="inline fields">
                        <div class="field">
                            <div class="ui radio checkbox">
                                <input type="radio" value="yes" name="studentSwitchOption" <?php echo($switch_option ? "checked=\"true\"" : "");?> tabindex="0" class="hidden">
                                <label>예</label>
                            </div>
                        </div>
                        <div class="field">
                            <div class="ui radio checkbox">
                                <input type="radio" value="no" name="studentSwitchOption" <?php echo(!$switch_option ? "checked=\"true\"" : "");?> tabindex="1" class="hidden">
                                <label>아니오</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="required inline field">
                    <div class="ui checkbox">
                        <input type="checkbox" tabindex="1" name="studentTerms" class="hidden">
                        <label>본인은 KSCY <a onclick="$('#priv').modal('show')">개인정보처리방침</a>에 동의합니다</label>
                    </div>
                </div>

                <button class="ui button" type="submit" style="margin-top: 40px"><?php echo($review_mode ? "학생 정보 수정" : "학생 등록");?></button>
                <a class="ui button" href="./" >취소</a>

            </form>

<script>

$("#studentSchoolGrade option[value='<?php echo($response["data"]["grade"]); ?>']").prop("selected", true);
$("#studentSurvey option[value='<?php echo($response["data"]["survey"]); ?>']").prop("selected", true);

$('.ui.modal')
  .modal()
;

$('.ui.checkbox')
  .checkbox();

$('.ui.radio.checkbox')
  .checkbox();

$('.ui.form')
  .form({
    fields: {
      studentName     : 'empty',
      studentSchoolName   : 'empty',
      studentPhoneNumber : 'empty',
      studentPassword: ['match[studentPasswordRepeat]', 'empty'],
      studentEmail : 'email',
      studentTerms    : 'checked'
    }
  });
  


</script>
<?php

}?>

</div></div>


<?php
include "footer.php";
?>
