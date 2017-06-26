<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 22
 */

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

function process_camp($review_mode, $delete_mode, $student_no) {

    global $db;
    global $session;
    global $utils;

    $max_file_size = 1048576 * 50;

    // 변수로 POST 값들 읽어오기
    $user_camp_bio = trim($_POST["campBio"]);
    $user_camp_motivation = trim($_POST["campMotivation"]);
    $user_camp_desired_session = trim($_POST["campDesiredSession"]);
    $user_camp_team_members  = trim($_POST["campTeamMembers"]);
    $user_camp_team_leader  = explode(",", $user_camp_team_members)[0];

    // POST 값들의 유효성을 체크
    $is_try = !empty($user_camp_bio);
    $is_valid = !(empty($user_camp_bio) || 
                  empty($user_camp_motivation) || 
                  empty($user_camp_desired_session) || 
                  empty($user_camp_team_members));

    // 삭제 모드라면
    if ($delete_mode) {

        $response = $db->in('kscy_camps')
                        ->delete()
                        ->where("team_leader", "=", $student_no)
                        ->go();
        
        if ($response) {
            $statistics = $db->in('kscy_statistics')
                            ->update('value', '`value` - 1 ', true)
                            ->where('key', '=', 'total_camps')
                            ->go();

            return array(
                "result" => "delete"
            );
        }
    }

    // 리뷰 모드라면, 서버로부터 데이터 로드
    if ($review_mode) {

        // 팀장으로 있는 데이터들
        $camp_data = $db->in('kscy_camps')
                         ->select('no')
                         ->select('bio')
                         ->select('motivation')
                         ->select('desired_session')
                         ->select('team_members')
                         ->select('team_leader')
                         ->where('team_leader', '=', $student_no)
                         ->go_and_get();
        
        // 세부 팀 멤버 데이터 로드
        $team_members = explode(",", $camp_data["team_members"]);
        $team_member_data = $db->in('kscy_students')
                                ->select('no')
                                ->select('name')
                                ->select('school')
                                ->select('grade');
        foreach ($team_members as $team_member) {
            $team_member_data->where('no', '=', $team_member, "OR");              
        }
        $team_member_data = $team_member_data->go_and_get_all();
        $camp_data["team_member_data"] = $team_member_data;

        if (!$camp_data) {
            return array(
                "result" => "error",
                "message" => "제출된 지원서가 없습니다."
            );
        }
    }

    if (!$is_try) {
        return array(
            "result" => "pending",
            "review" => $review_mode,
            "data" => $camp_data
        );
    }

    // 유효하지 않은 값이 있다면
    if ($is_try && !$is_valid) {
        return array(
            "result" => "warning",
            "review" => $review_mode,
            "message" => "올바른 값이 전달되지 않았습니다."
        );
    }

    // 데이터베이스 업데이트
    if ($review_mode) {
        $response = $db->in('kscy_camps')
                       ->update('bio', $utils->purify($user_camp_bio))
                       ->update('motivation', $utils->purify($user_camp_motivation))
                       ->update('desired_session', $utils->purify($user_camp_desired_session))
                       ->update('team_leader', $utils->purify($user_camp_team_leader))
                       ->update('team_members', $utils->purify($user_camp_team_members))
                       ->where('team_leader', '=', $student_no)
                       ->go();

        if (!$response) {
            return array(
                "result" => "error",
                "message" => "업데이트 도중 오류가 발생했습니다."
            );
        }
    } 

    // 데이터베이스 신규 등록
    else {

        // 중복 업로드 검사
        $response = $db->in('kscy_camps')
                       ->select('team_leader')
                       ->where('team_leader', '=', $utils->purify($user_camp_team_members))
                       ->go_and_get();

        if ($response) {
            return array(
                "result" => "warning",
                "review" => $review_mode,
                "message" => "이미 지원서를 제출하였습니다."
            );
        }

        $response = $db->in('kscy_camps')
                       ->insert('bio', $utils->purify($user_camp_bio))
                       ->insert('motivation', $utils->purify($user_camp_motivation))
                       ->insert('desired_session', $utils->purify($user_camp_desired_session))
                       ->insert('team_leader', $utils->purify($user_camp_team_leader))
                       ->insert('team_members', $utils->purify($user_camp_team_members))
                       ->go();

        $statistics = $db->in('kscy_statistics')
                         ->update('value', '`value` + 1 ', true)
                         ->where('key', '=', 'total_camps')
                         ->go();

        if (!$response) {
            return array(
                "result" => "error",
                "message" => "등록 도중 오류가 발생했습니다."
            );
        }
    }

    return array(
        "result" => "success",
        "review" => $review_mode,
        "data" => $camp_data
    );
}

function render_camp($response) {

    global $utils;
    global $strings;

    if ($response["result"] == "warning") { ?>
        <div class="ui warning message"><?php echo($response["message"]); ?></div><?php
    } ?>
    <form class="ui form" method="post" enctype="multipart/form-data" >
        <h4 class="ui dividing header" style="margin-top: 15px">캠프 참가 학생 정보</h4>
        <div class="field required">
           <label>캠프 참가 학생 (Student)</label>
            <input type="hidden" id="campTeamMembers" name="campTeamMembers"  value="<?php $utils->display($response["data"]["team_members"]);?>">
            <div class="ui action input">
                <input type="text" id="campStudentEmail" placeholder="학생 등록 시 입력한 이메일 주소 입력">
                <a class="ui button" id="campAddStudent">학생 등록</a>
            </div>
            <div class="ui pointing blue basic label">
                <p>학생 추가를 위해서는 학생이 <u><a href="./student.php">학생 등록</a></u>되어 있어야 합니다. (추후 수정 가능)</p>
            </div>
            <div class="ui middle aligned list" id="campStudentsList">
            <?php
            foreach ($response["data"]["team_member_data"] as $team_member_data) {?>
                <div class="item">
                    <div class="left floated content">
                        <div class="ui icon basic button" no="<?php echo($team_member_data["no"]);?>">
                            <i class="remove icon"></i>
                        </div>
                    </div>
                <div class="content">
                    <div class="header"><?php echo($team_member_data["name"]. " ".($team_member_data["no"] == $response["data"]["team_leader"] ? '<div class="ui tiny horizontal label">팀장</div>' :"")); ?></div>
                        <?php echo($team_member_data["school"] . " " . (((intval($team_member_data["grade"]) - 1) % 3) + 1). "학년"); ?>
                    </div>
                </div>
            <?php } ?>
            </div>
        </div>
        <h4 class="ui dividing header" style="margin-top: 40px">캠프 지원서</h4>
        <div class="field required">
            <label>자기소개 (Bio)</label>
            <textarea name="campBio"><?php $utils->display($response["data"]["bio"]);?></textarea>
        </div>
        <div class="field required">
            <label>캠프 참가 동기 (Motivation)</label>
            <textarea name="campMotivation"><?php $utils->display($response["data"]["motivation"]);?></textarea>
        </div>
        <div class="field required">
            <label>희망 참가 세션 (Desired Session)</label>
            <select class="ui fluid dropdown" name="campDesiredSession" id="campDesiredSession">
                <?php foreach($strings["session_names"] as $key => $value) { ?>
                <option value="<?php echo($key);?>"><?php echo($value);?></option>
                <?php } ?>
            </select>
        </div>
        <div class="required inline field" style="margin-top:25px">
            <div class="ui checkbox">
                <input type="checkbox" tabindex="0" name="campAgreeTerms" class="hidden"<?php echo($response["review"] ? " checked" : "");?>>
                <label>본인은 KSCY <a onclick="$('#terms').modal('show')">운영 및 심사 방침</a>에 동의합니다</label>
            </div>
        </div>
        <button class="ui button" type="submit" style="margin-top: 15px">지원서 <?php echo($response["review"] ? "수정" : "제출")?></button>
        <?php if ($response["review"]) {
            echo('<a class="ui button" id="campDelete">지원서 삭제</a>');
        } ?>
        <a class="ui button" href="./" >취소</a>
    </form>

    <script>

    var campTeamMembers = [ <?php if ($response["review"]) { echo($response["data"]["team_members"]); } ?>];
    var campTeamLeader<?php if ($response["review"]) { echo(" = ".$response["data"]["team_leader"]); } ?>;

    $("#campDesiredSession option[value='<?php echo($response["data"]["desired_session"]); ?>']").prop("selected", true);
    $('.ui.checkbox').checkbox();
    $('.ui.form').form({
        fields: {
            campBio: 'empty',
            campDesiredSession: 'empty',
            campMotivation: 'empty',
            campTeamMembers: 'minLength[1]',
            campAgreeTerms: 'checked'
        }
    });

    $('#campAddStudent').on('click', function() {
        $('#campAddStudent').addClass("disabled");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './student.ajax.php',
            data: { action: "get-by-email", email:$('#campStudentEmail').val() },
            success: function (data) {
                $('#campAddStudent').removeClass("disabled");

                if (campTeamMembers.length > 0) {
                    alert("캠프는 개인 지원만 가능합니다.");
                    return;
                }

                if (campTeamMembers.indexOf(Number(data.no)) >= 0) {
                    alert("이미 추가된 학생입니다.");
                    return;
                }
                if (data.result == "error") {
                    alert(data.message);
                    return;
                }
                campTeamMembers.push(Number(data.no));
                var innerHTML = '<div class="item">'
                innerHTML += '<div class="left floated content">'
                innerHTML += '<div class="ui icon basic button" no="'+Number(data.no)+'">'
                innerHTML += '<i class="remove icon"></i>'
                innerHTML += '</div>'
                innerHTML += '</div>'
                innerHTML += '<div class="content">'
                innerHTML += '<div class="header">' + data.name + '&nbsp;</div>'
                innerHTML += data.school + " " + data.grade + "학년"
                innerHTML += '</div>'
                innerHTML += '</div>'
                $('#campTeamMembers').val(campTeamMembers.join(","));
                $('#campStudentsList').append(innerHTML);
                $('#campStudentEmail').val("");

                if (campTeamLeader != campTeamMembers[0]) {
                    campTeamLeader = campTeamMembers[0];
                    var labelElement = '<div class="ui tiny horizontal label">팀장</div>';
                    var firstElement = $(".ui.icon.basic.button[no='"+campTeamMembers[0]+"']").parent().parent().find(".content").find(".header");
                    firstElement.append(labelElement);
                }
            },
            error: function (request, status, error) {
                $('#campAddStudent').removeClass("disabled");
                alert("학생을 찾을 수 없습니다. 등록된 학생인지 확인해 주세요.");
            }
        });
    });

    $('#campDelete').on('click', function() {
        if(confirm("정말 지원서를 삭제하시겠습니까?")) {
            window.location.href = "./application.php?delete=true";
        }
    });

    $('#campStudentsList').on('click', '.ui.icon.basic.button', function() {

        if (campTeamMembers.length < 2) {
            alert("팀원은 최소 한 명 이상이어야 합니다.");
            return;
        }
        campTeamMembers.splice(campTeamMembers.indexOf(Number($(this).attr('no'))), 1);
        $('#campTeamMembers').val(campTeamMembers.join(","));
        $(this).parent('div').parent('div').remove();

        if (campTeamLeader != campTeamMembers[0]) {
            campTeamLeader = campTeamMembers[0];
            var labelElement = '<div class="ui tiny horizontal label">팀장</div>';
            var firstElement = $(".ui.icon.basic.button[no='"+campTeamMembers[0]+"']").parent().parent().find(".content").find(".header");
            firstElement.append(labelElement);
        }
    });
    </script>
<?php } ?>