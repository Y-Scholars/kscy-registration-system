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

function process_plan($review_mode, $delete_mode, $student_no) {

    global $db;
    global $session;
    global $utils;

    $max_file_size = 1048576 * 50;

    // 변수로 POST 값들 읽어오기
    $user_plan_title = trim($_POST["planTitle"]);
    $user_plan_research_field = trim($_POST["planResearchField"]);
    $user_plan_desired_session = trim($_POST["planDesiredSession"]);
    $user_plan_team_members  = trim($_POST["planTeamMembers"]);
    $user_plan_team_leader  = explode(",", $user_plan_team_members)[0];
    $user_plan_file = $_FILES["planFile"];

    // POST 값들의 유효성을 체크
    $is_try = !empty($user_plan_title);
    $is_valid = !(empty($user_plan_title) || 
                  empty($user_plan_research_field) || 
                  empty($user_plan_desired_session) || 
                  empty($user_plan_team_members));

    // 삭제 모드라면
    if ($delete_mode) {

        $response = $db->in('kscy_plans')
                        ->delete()
                        ->where("team_leader", "=", $student_no)
                        ->go();
        
        if ($response) {
            $statistics = $db->in('kscy_statistics')
                            ->update('value', '`value` - 1 ', true)
                            ->where('key', '=', 'total_plans')
                            ->go();

            return array(
                "result" => "delete"
            );
        }
    }

    // 리뷰 모드라면, 서버로부터 데이터 로드
    if ($review_mode) {

        // 팀장으로 있는 데이터들
        $plan_data = $db->in('kscy_plans')
                         ->select('no')
                         ->select('title')
                         ->select('file')
                         ->select('research_field')
                         ->select('desired_session')
                         ->select('team_members')
                         ->select('team_leader')
                         ->where('team_leader', '=', $student_no)
                         ->go_and_get();
        
        // 세부 팀 멤버 데이터 로드
        $team_members = explode(",", $plan_data["team_members"]);
        $team_member_data = $db->in('kscy_students')
                                ->select('no')
                                ->select('name')
                                ->select('school')
                                ->select('grade');
        foreach ($team_members as $team_member) {
            $team_member_data->where('no', '=', $team_member, "OR");              
        }
        $team_member_data = $team_member_data->go_and_get_all();
        $plan_data["team_member_data"] = $team_member_data;

        if (!$plan_data) {
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
            "data" => $plan_data
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

    // 첨부파일이 있다면 업로드
    $file_uploaded = false;
    if (!empty($user_plan_file["name"])) {
        $file_extension = pathinfo($user_plan_file["name"])['extension'];
        $file_path = "./files/plans/" . $user_plan_team_leader . "." . $file_extension;

        if ($user_plan_file['size'] > $max_file_size) {
            return array(
                "result" => "warning",
                "review" => $review_mode,
                "message" => "첨부파일의 용량이 너무 큽니다."
            );
        }

        if (!move_uploaded_file($user_plan_file['tmp_name'], $file_path)) {
            return array(
                "result" => "warning",
                "review" => $review_mode,
                "message" => "첨부파일을 업로드하지 못하였습니다."
            );
        }
        $file_uploaded = true;
    }

    // 데이터베이스 업데이트
    if ($review_mode) {
        $response = $db->in('kscy_plans')
                       ->update('title', $utils->purify($user_plan_title))
                       ->update('file', $file_uploaded ? $file_path : $plan_data["file"])
                       ->update('research_field', $utils->purify($user_plan_research_field))
                       ->update('desired_session', $utils->purify($user_plan_desired_session))
                       ->update('team_leader', $utils->purify($user_plan_team_leader))
                       ->update('team_members', $utils->purify($user_plan_team_members))
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
        $response = $db->in('kscy_plans')
                       ->select('team_leader')
                       ->where('team_leader', '=', $utils->purify($user_plan_team_members))
                       ->go_and_get();

        if ($response) {
            return array(
                "result" => "warning",
                "review" => $review_mode,
                "message" => "이미 지원서를 제출하였습니다."
            );
        }

        $response = $db->in('kscy_plans')
                       ->insert('title', $utils->purify($user_plan_title))
                       ->insert('file', $file_path)
                       ->insert('research_field', $utils->purify($user_plan_research_field))
                       ->insert('desired_session', $utils->purify($user_plan_desired_session))
                       ->insert('team_leader', $utils->purify($user_plan_team_leader))
                       ->insert('team_members', $utils->purify($user_plan_team_members))
                       ->go();

        $statistics = $db->in('kscy_statistics')
                         ->update('value', '`value` + 1 ', true)
                         ->where('key', '=', 'total_plans')
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
        "data" => $plan_data
    );
}

function render_plan($response) {

    global $utils;
    global $strings;

    if ($response["result"] == "warning") { ?>
        <div class="ui warning message"><?php echo($response["message"]); ?></div><?php
    } ?>
    <form class="ui form" method="post" enctype="multipart/form-data" >
        <h4 class="ui dividing header" style="margin-top: 15px">연구계획 발표자 정보</h4>
        <div class="field required">
           <label>발표자 리스트 (Team Members)</label>
            <input type="hidden" id="planTeamMembers" name="planTeamMembers"  value="<?php $utils->display($response["data"]["team_members"]);?>">
            <div class="ui action input">
                <input type="text" id="planStudentEmail" placeholder="학생 등록 시 입력한 이메일 주소 입력">
                <a class="ui button" id="planAddStudent">학생 추가</a>
            </div>
            <div class="ui pointing blue basic label">
                <p>발표자 추가를 위해서는 학생이 <u><a href="./student.php">학생 등록</a></u>되어 있어야 하며, 리스트의 첫 번째에 위치한 학생이 팀장이 됩니다. (추후 수정 가능)</p>
            </div>
            <div class="ui middle aligned list" id="planStudentsList">
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
        <h4 class="ui dividing header" style="margin-top: 40px">연구계획 정보</h4>
        <div class="field required">
            <label>연구계획 제목 (Research Plan Title)</label>
            <input type="text" name="planTitle" placeholder="연구계획 제목 입력" value="<?php $utils->display($response["data"]["title"]);?>">
        </div>
        <div class="field required">
            <label>연구계획 파일 (Research Plan File)
                <?php if (!empty($response["data"]["file"])) {
                    $file_extension = pathinfo($response["data"]["file"])['extension'];?>
                <u><a href="<?php echo("./download.php?type=plan");?>"> 다운로드</a></u>
                <?php } ?>
            </label>
            <div class="ui fluid file input action">
                <input type="text" readonly >
                <input type="file" name="planFile" autocomplete="off">
                <div class="ui button">찾아보기...</div>
            </div>
            <div class="ui pointing blue basic label" >
                <p> 연구계획 파일은 <u><a href="./files/8th_KSCY_Plan.docx">KSCY 연구계획 양식</a></u>에 맞추어 작성해 주시기 바랍니다.</p>
            </div>
        </div>
        <div class="field required">
            <label>연구 분야 (Research Field)</label>
            <input type="text" name="planResearchField" placeholder="연구 분야 입력" value="<?php $utils->display($response["data"]["research_field"]);?>">
            <div class="ui pointing blue basic label">
                <p>희망 참가 세션과는 별개로 본인이 생각하는 연구의 분야를 입력해 주세요.</p>
            </div>
        </div>
        <div class="field required">
            <label>희망 참가 세션 (Desired Session)</label>
            <select class="ui fluid dropdown" name="planDesiredSession" id="planDesiredSession">
                <?php foreach($strings["session_names"] as $key => $value) { ?>
                <option value="<?php echo($key);?>"><?php echo($value);?></option>
                <?php } ?>
            </select>
        </div>
        <div class="required inline field" style="margin-top:25px">
            <div class="ui checkbox">
                <input type="checkbox" tabindex="0" name="planAgreeTerms" class="hidden"<?php echo($response["review"] ? " checked" : "");?>>
                <label>본인은 KSCY <a onclick="$('#terms').modal('show')">운영 및 심사 방침</a>에 동의합니다</label>
            </div>
        </div>
        <button class="ui button" type="submit" style="margin-top: 15px">지원서 <?php echo($response["review"] ? "수정" : "제출")?></button>
        <?php if ($response["review"]) {
            echo('<a class="ui button" id="planDelete">지원서 삭제</a>');
        } ?>
        <a class="ui button" onclick="javascript:history.back(-1);" >취소</a>
    </form>

    <script>

    var planTeamMembers = [ <?php if ($response["review"]) { echo($response["data"]["team_members"]); } ?>];
    var planTeamLeader<?php if ($response["review"]) { echo(" = ".$response["data"]["team_leader"]); } ?>;

    $("#planDesiredSession option[value='<?php echo($response["data"]["desired_session"]); ?>']").prop("selected", true);
    $('.ui.checkbox').checkbox();
    $('.ui.form').form({
        fields: {
            planTitle: 'minLength[3]',
            planResearchField: 'minLength[2]',
            planTeamMembers: 'minLength[1]',
            planAgreeTerms: 'checked'
        }
    });

    $('#planAddStudent').on('click', function() {
        $('#planAddStudent').addClass("disabled");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './student.ajax.php',
            data: { action: "get-by-email", email:$('#planStudentEmail').val() },
            success: function (data) {
                $('#planAddStudent').removeClass("disabled");
                if (planTeamMembers.indexOf(Number(data.no)) >= 0) {
                    alert("이미 추가된 학생입니다.");
                    return;
                }
                if (data.result == "error") {
                    alert(data.message);
                    return;
                }
                planTeamMembers.push(Number(data.no));
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
                $('#planTeamMembers').val(planTeamMembers.join(","));
                $('#planStudentsList').append(innerHTML);
                $('#planStudentEmail').val("");

                if (planTeamLeader != planTeamMembers[0]) {
                    planTeamLeader = planTeamMembers[0];
                    var labelElement = '<div class="ui tiny horizontal label">팀장</div>';
                    var firstElement = $(".ui.icon.basic.button[no='"+planTeamMembers[0]+"']").parent().parent().find(".content").find(".header");
                    firstElement.append(labelElement);
                }
            },
            error: function (request, status, error) {
                $('#planAddStudent').removeClass("disabled");
                alert("학생을 찾을 수 없습니다. 등록된 학생인지 확인해 주세요.");
            }
        });
    });

    $('#planDelete').on('click', function() {
        if(confirm("정말 지원서를 삭제하시겠습니까?")) {
            window.location.href = "./application.php?delete=true";
        }
    });

    $('#planStudentsList').on('click', '.ui.icon.basic.button', function() {

        if (planTeamMembers.length < 2) {
            alert("팀원은 최소 한 명 이상이어야 합니다.");
            return;
        }
        planTeamMembers.splice(planTeamMembers.indexOf(Number($(this).attr('no'))), 1);
        $('#planTeamMembers').val(planTeamMembers.join(","));
        $(this).parent('div').parent('div').remove();

        if (planTeamLeader != planTeamMembers[0]) {
            planTeamLeader = planTeamMembers[0];
            var labelElement = '<div class="ui tiny horizontal label">팀장</div>';
            var firstElement = $(".ui.icon.basic.button[no='"+planTeamMembers[0]+"']").parent().parent().find(".content").find(".header");
            firstElement.append(labelElement);
        }
    });

    $('.ui.file.input').find('input:text, .ui.button').on('click', function(e) {
        $(e.target).parent().find('input:file').click();
    });

    $('input:file', '.ui.file.input').on('change', function(e) {
        var file = $(e.target);
        var name = '';
        for (var i = 0; i < e.target.files.length; i++) {
            name += e.target.files[i].name + ', ';
        }
        name = name.replace(/,\s*$/, '');
        $('input:text', file.parent()).val(name);
    });

    </script>
<?php } ?>