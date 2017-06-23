<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 17
 */

error_reporting(0);

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

function process() {

    global $db;
    global $session;
    global $utils;

    $review_mode = false;
    if (!empty($_GET["review"]) && $_GET["review"] == true) {
        $review_mode = true;
    }

    $student_no = -1;
    if (!empty($_GET["no"])) {
        $student_no = intval($_GET["no"]);
    }

    if ($review_mode) {

        // 관리자 권한으로 편집
        if ($student_no >= 0) {
            $is_admin = true;
            if ($session->get_level() < 1)  {
                header("Location: ./authentication.php?redirect=".base64_encode("student.php?review=true"));
                exit();
            }
        } 

        // 스스로 편집
        else {
            if (empty($session->get_student_no())) {
                header("Location: ./authentication.php?redirect=".base64_encode("student.php?review=true"));
                exit();
            }
        }
    }

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
    $user_student_tag = $_POST["studentTag"];
    $user_student_memo = $_POST["studentMemo"];
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
                           ->select('*')
                           ->where('no', '=', $is_admin ? $student_no : $session->get_student_no())
                           ->go_and_get();

        if (!$student_data) {
            return array(
                "result" => "error",
                "review" => $review_mode,
                "message" => "해당하는 학생 정보가 존재하지 않습니다."
            );
        }
    }

    if (!$is_try) {
        return array(
            "result" => "pending",
            "admin" => $is_admin,
            "review" => $review_mode,
            "data" => $student_data
        );
    }

    // 유효하지 않은 값이 있다면
    if ($is_try && !$is_valid) {
        return array(
            "result" => "warning",
            "admin" => $is_admin,
            "review" => $review_mode,
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
                       ->update('gender', $utils->purify($user_student_gender))
                       ->update('guardian_name', $utils->purify($user_student_guardian_name))
                       ->update('guardian_phone_number', $utils->purify($user_student_guardian_phone_number))
                       ->update('survey', $utils->purify($user_student_survey))
                       ->update('auto_switch', $utils->purify($user_student_switch_option))
                       ->where('no', '=', $is_admin >= 0 ? $student_no : $session->get_student_no());
        
        // 관리자 권한으로 수정했을 경우
        if ($is_admin) {
            $response->update('memo', $utils->purify($user_student_memo));
            $response->update('tag', $utils->purify($user_student_tag));
            // 입력 비밀번호가 바뀌지 않았다면 비밀번호를 변경하지 않음
            if ($user_student_password != "admin") {
                $response->update('password', hash("sha256", $utils->purify($user_student_password)));
            }
        } else {
            $response->update('password', hash("sha256", $utils->purify($user_student_password)));
        }
        $response = $response->go();

        if ($is_admin) {
            $log = $db->in('kscy_logs')
                      ->insert('user', $session->get_student_no())
                      ->insert('target_user', $student_no)
                      ->insert('action', "modify info")
                      ->insert('data',  "")
                      ->insert('ip', $_SERVER['REMOTE_ADDR'])
                      ->go();
        }

        if (!$response) {
            return array(
                "result" => "error",
                "admin" => $is_admin,
                "review" => $review_mode,
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
                       ->go_and_get();

        if ($response) {
            return array(
                "result" => "warning",
                "admin" => $is_admin,
                "review" => $review_mode,
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
                "admin" => $is_admin,
                "review" => $review_mode,
                "message" => "학생 등록 도중 에러가 발생하였습니다."
            );
        }
    }

    // 관리자 모드라면 대시보드로 이동
    if ($is_admin) {
        header("Location: ./dashboard.php");
        exit();
    }

    $session->delete_student_no();

    return array(
                "result" => "success",
                "admin" => $is_admin,
                "review" => $review_mode,
                "data" => $student_data
            );
}

$response = process();

if ($response["result"] == "success") {
    header("Location: ./message.php?type=student" . ($response["review"] ? "-review" : ""));
    exit();
} else if ($response["result"] == "error") {
    header("Location: ./message.php?type=student-error");
    exit();
}

$title_korean = $response["review"] ? "학생 정보 수정" : "학생 등록";
$title_english = $response["review"] ? "Student Review" : "Student Registration";

include_once("./header.php");
?>

<div class="ui modal" id="term1">
    <i class="close icon"></i>
    <div class="header">개인정보처리방침</div>
    <div class="content">
        <p>1. 수집하는 개인정보 항목
        <br/>한국청소년학술대회는 (이하 '단체')은(는) 원할한 회의 참가를 위해 개인정보를 수집하고 있습니다.
        <br/>* 수집항목 : 위 지원서 항목
        <br/>* 개인정보 수집방법 : 홈페이지 </p>
        <p>2. 개인정보의 수집 및 이용목적
        <br/>단체는 수집한 개인정보를 다음의 목적을 위해 활용합니다. 서비스 이용에 따른 본인확인, 개인 식별, 불량회원의 부정 이용 방지와 비인가 사용 방지, 분쟁 조정을 위한 기록보존, 불만처리 등 민원처리, 고지사항 전달 및 다양한 정보 제공, 고객만족도 조사, 설문조사, 본인 의사 확인, 회원관리, 신규 서비스 안내 등</p>
        <p>3. 개인정보의 보유 및 이용기간
        <br/>개인정보의 보유 및 이용 기간은 해지시까지입니다.
        <p>4. 동의를 거부할 권리가 있다는 사실과 동의 거부에 따른 불이익 내용
        <br/>이용자는 수집하는 개인정보에 대해 동의를 거부할 권리가 있으며 동의 거부 시에는 서비스가 제한됩니다.</p>
    </div>
    <div class="actions">
        <div class="ui button" onclick="$('.ui.modal').modal('hide')">확인</div>
    </div>
</div>

<div class="ui modal" id="term2">
    <i class="close icon"></i>
    <div class="header">자동 참가유형 전환 제도</div>
    <div class="content">
        <p>KSCY참가전환제도는 <u>연구논문발표트랙</u>,  <u>연구계획발표트랙</u> 지원자를 대상으로 KSCY 참가 선택권을 넓히기 위해 기획된 제도입니다. </p> 
        <p>본 참가전환제도에 동의할 경우 지원한 세션의 발표자로 선발이 되지 못한 상황에서 자동으로 동일 세션 <u>연구멘토링트랙</u> 또는 <u>캠프트랙</u>으로 변경됩니다. </p> 
        <p><i>1. 연구논문발표 > 동일세션 연구멘토링 트랙(옵저버) 전환</i></p> 
        <p><i>2. 연구계획발표 > 동일세션 연구논문작성캠프 트랙 또는 연구멘토링 트랙(옵저버) 전환</i></p> 
        <p>참가트랙이 전환된 지원자는 해당 트랙의 참가비를 정해진 기한안에 입금하며 등록절차를 마치면 KSCY 참가가 확정됩니다. </p> 
        <p><i>(지원 세션에 공석이 생겨 추가발표자 선발이 가능해질 경우 추가합격을 진행합니다)</i></p> 
    </div>
    <div class="actions">
        <div class="ui button" onclick="$('.ui.modal').modal('hide')">확인</div>
    </div>
</div>

<div class="kscy-body">
<div class="ui container">

    <div class="ui icon message">
        <i class="warning circle icon"></i>
        <div class="content">
            <div class="header">유의사항</div>
            <p>참가 학생 등록 후 반드시 지원서를 작성하여 신청 절차를 완료해 주시기 바랍니다.</p>
        </div>
    </div>

    <?php if ($response["result"] == "warning") { ?>
    <div class="ui warning message">
        <p><?php echo($response["message"]); ?></p>
    </div>
    <?php } ?>

    <form class="ui form" method="post">
        <h4 class="ui dividing header" style="margin-top: 40px">기본 정보</h4>
        <div class="field required">
            <label>이름 (Name)</label>
            <input type="text" name="studentName" placeholder="이름 입력" value="<?php $utils->display($response["data"]["name"]);?>">
        </div>
        <div class="two fields">
            <div class="ten wide field required">
                <label>학교 이름 (Affiliation)</label>
                <input type="text" name="studentSchoolName" placeholder="학교 이름 입력" value="<?php $utils->display($response["data"]["school"]);?>">
            </div>
            <div class="six wide field required">
                <label>학년 (Grade)</label>
                <select class="ui fluid dropdown" name="studentSchoolGrade" id="studentSchoolGrade">
                    <?php foreach($strings["grade_names"] as $key => $value) { ?>
                    <option value="<?php echo($key);?>"><?php echo($value);?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="two fields">
            <div class="eight wide field required">
                <label>전화번호 (Mobile)</label>
                <input type="text" name="studentPhoneNumber" placeholder="전화번호 입력" value="<?php $utils->display($response["data"]["phone_number"]);?>">
            </div>
            <div class="eight wide field required <?php if ($response["review"]) { echo(" disabled");}?>">
                <label>이메일 주소 (Email)</label>
                <input type="email" name="studentEmail" placeholder="이메일 주소 입력" value="<?php $utils->display($response["data"]["email"]);?>">
            </div>
        </div>
        <div class="two fields">
            <div class="eight wide field required">
                <label>비밀번호 (Password)</label>
                <input type="password" name="studentPassword" placeholder="비밀번호 입력" value="<?php echo($response["admin"] ? "admin" : "");?>">
            </div>
            <div class="eight wide field">
                <label>비밀번호 재입력 (Repeat Password)</label>
                <input type="password" name="studentPasswordRepeat" placeholder="비밀번호 재입력" value="<?php echo($response["admin"] ? "admin" : "");?>">
            </div>
        </div>
        <?php if ($response["admin"]) { ?>
        <h4 class="ui dividing header" style="margin-top: 40px">관리자 기입 사항</h4>
        <div class="field">
            <label>태그 (Tag)</label>
            <select class="ui fluid dropdown" name="studentTag" id="studentTag">
                <?php foreach($strings["tag_names"] as $key => $value) { ?>
                <option value="<?php echo($key);?>"><?php echo($value);?></option>
                <?php } ?>
            </select>
        </div>
        <div class="field">
            <label>메모 (Memo)</label>
            <textarea name="studentMemo"><?php $utils->display($response["data"]["memo"]);?></textarea>
        </div>
        <?php } ?>
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
        <div class="two fields">
            <div class="eight wide field">
                <label>보호자 이름 (Guardian)</label>
                <input type="text" name="studentGuardianName" placeholder="보호자 이름 입력" value="<?php $utils->display($response["data"]["guardian_name"]);?>">
            </div>
            <div class="eight wide field">
                <label>보호자 전화번호 (Guardian Mobile)</label>
                <input type="text" name="studentGuardianPhoneNumber" placeholder="보호자 전화번호 입력" value="<?php $utils->display($response["data"]["guardian_phone_number"]);?>">
            </div>
        </div>
        <h4 class="ui dividing header" style="margin-top: 40px">기타</h4>
        <div class="field required">
            <label>KSCY를 알게 된 경로 (Survey)</label>
            <div class="field">
                <select class="ui fluid dropdown" name="studentSurvey" id="studentSurvey">
                    <?php foreach($strings["survey_names"] as $key => $value) { ?>
                    <option value="<?php echo($key);?>"><?php echo($value);?></option>
                    <?php } ?>
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
            <a onclick="$('#term2').modal('show')">참가유형 전환제도</a>에 따라 참가유형전환을 신청하겠습니다.
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
                <input type="checkbox" tabindex="1" name="studentTerms" class="hidden"<?php echo($response["review"] ? " checked" : "");?>>
                <label>본인은 KSCY <a onclick="$('#term1').modal('show')">개인정보처리방침</a>에 동의합니다</label>
            </div>
        </div>
        <button class="ui button" type="submit" style="margin-top: 40px"><?php echo($response["review"] ? "학생 정보 수정" : "학생 등록");?></button>
        <a class="ui button" href="./" >취소</a>
    </form>
</div>
</div>

<script>

$("#studentSchoolGrade option[value='<?php echo($response["data"]["grade"]); ?>']").prop("selected", true);
$("#studentSurvey option[value='<?php echo($response["data"]["survey"]); ?>']").prop("selected", true);
$("#studentTag option[value='<?php echo($response["data"]["tag"]); ?>']").prop("selected", true);

$('.ui.modal').modal();
$('.ui.checkbox').checkbox();
$('.ui.radio.checkbox').checkbox();

$('.ui.form')
    .form({
        fields: {
            studentName: 'empty',
            studentSchoolName: 'empty',
            studentPhoneNumber: 'empty',
            studentPassword: ['match[studentPasswordRepeat]', 'empty'],
            studentEmail: 'email',
            studentTerms: 'checked'
        }
    }
);
</script>
<?php
include_once("./footer.php");
?>
