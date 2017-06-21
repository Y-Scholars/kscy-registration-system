<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 17
 */

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

function process_paper($review_mode) {

    global $db;
    global $session;
    global $utils;

    // 변수로 POST 값들 읽어오기
    $user_paper_title = trim($_POST["paperTitle"]);
    $user_paper_research_field = trim($_POST["paperResearchField"]);
    $user_paper_desired_session = trim($_POST["paperDesiredSession"]);
    $user_paper_team_members  = trim($_POST["paperTeamMembers"]);
    $user_paper_team_leader  = explode(",", $user_paper_team_members)[0];
    $user_paper_file = $_FILES["paperFile"];

    // POST 값들의 유효성을 체크
    $is_try = !empty($user_paper_title);
    $is_valid = !(empty($user_paper_title) || 
                  empty($user_paper_file) || 
                  empty($user_paper_research_field) || 
                  empty($user_paper_desired_session) || 
                  empty($user_paper_team_members));

    // 리뷰 모드라면, 서버로부터 데이터 로드
    if ($review_mode) {

        // 팀장으로 있는 데이터들
        $paper_data = $db->in('kscy_papers')
                         ->select('no')
                         ->select('title')
                         ->select('file')
                         ->select('research_field')
                         ->select('desired_session')
                         ->select('team_members')
                         ->select('team_leader')
                         ->where('team_leader', '=', $session->get_student_no())
                         ->go_and_get();
        
        // 세부 팀 멤버 데이터 로드
        $team_members = explode(",", $paper_data["team_members"]);
        $team_member_data = $db->in('kscy_students')
                                ->select('no')
                                ->select('name')
                                ->select('school')
                                ->select('grade');
        foreach ($team_members as $team_member) {
            $team_member_data->where('no', '=', $team_member, "OR");              
        }
        $team_member_data = $team_member_data->go_and_get_all();
        $paper_data["team_member_data"] = $team_member_data;

        if (!$paper_data) {
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
            "data" => $paper_data
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
    if (!empty($user_paper_file["name"])) {
        $file_extension = pathinfo($user_paper_file["name"])['extension'];
        $file_path = "./papers/" . $user_paper_team_leader . "." . $file_extension;

        if ($user_paper_file['size'] > 1048576 * 50) {
            return array(
                "result" => "warning",
                "review" => $review_mode,
                "message" => "첨부파일의 용량이 너무 큽니다."
            );
        }

        if (!move_uploaded_file($user_paper_file['tmp_name'], $file_path)) {
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
        $response = $db->in('kscy_papers')
                       ->update('title', $utils->purify($user_paper_title))
                       ->update('file', $file_uploaded ? $file_path : $paper_data["file"])
                       ->update('research_field', $utils->purify($user_paper_research_field))
                       ->update('desired_session', $utils->purify($user_paper_desired_session))
                       ->update('team_leader', $utils->purify($user_paper_team_leader))
                       ->update('team_members', $utils->purify($user_paper_team_members))
                       ->where('team_leader', '=', $session->get_student_no())
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
        $response = $db->in('kscy_papers')
                       ->select('team_leader')
                       ->where('team_leader', '=', $utils->purify($user_paper_team_members))
                       ->go_and_get();

        if ($response) {
            return array(
                "result" => "warning",
                "review" => $review_mode,
                "message" => "이미 지원서를 제출하였습니다."
            );
        }

        $response = $db->in('kscy_papers')
                       ->insert('title', $utils->purify($user_paper_title))
                       ->insert('file', $file_path)
                       ->insert('research_field', $utils->purify($user_paper_research_field))
                       ->insert('desired_session', $utils->purify($user_paper_desired_session))
                       ->insert('team_leader', $utils->purify($user_paper_team_leader))
                       ->insert('team_members', $utils->purify($user_paper_team_members))
                       ->go();

        $statistics = $db->in('kscy_statistics')
                         ->update('value', '`value` + 1 ', true)
                         ->where('key', '=', 'total_papers')
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
                "data" => $paper_data
            );
}

function render_paper($response) {

    global $utils;
    global $strings;

    if ($response["result"] == "success") { ?>
        <script>window.location.href = "./message.php?type=application<?php echo($response["review"] ? "-review" : "");?>"; </script><?php
        return;
    } else if ($response["result"] == "error") { ?>
        <script>window.location.href = "./message.php?type=error";</script><?php
        return;
    }
    if ($response["result"] == "warning") { ?>
        <div class="ui warning message"><?php echo($response["message"]); ?></div><?php
    } ?>
    <form class="ui form" method="post" enctype="multipart/form-data" >
        <h4 class="ui dividing header" style="margin-top: 10px">논문 발표자 정보</h4>
        <div class="field required">
           <label>발표자 리스트 (Team Members)</label>
            <input type="hidden" id="paperTeamMembers" name="paperTeamMembers"  value="<?php $utils->display($response["data"]["team_members"]);?>">
            <div class="ui action input">
                <input type="text" id="paperStudentEmail" placeholder="학생 등록 시 입력한 이메일 주소">
                <a class="ui button" id="paperAddStudent">학생 추가</a>
            </div>
            <div class="ui pointing blue basic label">
                <p>발표자 추가를 위해서는 학생이 <u><a href="./student.php">학생 등록</a></u>되어 있어야 합니다. 리스트의 첫 번째에 위치한 학생이 팀장이 됩니다. (추후 수정 가능)</p>
            </div>
            <div class="ui middle aligned list" id="paperStudentsList">
           
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
        <h4 class="ui dividing header" style="margin-top: 40px">논문 정보</h4>
        <div class="field required">
            <label>논문 제목 (Paper Title)</label>
            <input type="text" name="paperTitle" placeholder="제목" value="<?php $utils->display($response["data"]["title"]);?>">
        </div>
        <div class="field required">
            <label>논문 파일 (Paper File)
                <?php if (!empty($response["data"]["file"])) {
                    $file_extension = pathinfo($response["data"]["file"])['extension'];?>
                <u><a href="<?php echo("download.php?type=papers&extension=".$file_extension);?>"> 다운로드</a></u>
                <?php } ?>
            </label>
            <div class="ui fluid file input action">
                <input type="text" readonly >
                <input type="file" name="paperFile" autocomplete="off">
                <div class="ui button">찾아보기...</div>
            </div>
            <div class="ui pointing blue basic label" >
                <p> 논문 파일은 <u><a href="./files/8th_KSCY_Paper.docx">KSCY 연구논문 양식</a></u>에 맞추어 작성해 주시기 바랍니다.</p>
            </div>
        </div>
        <div class="field required">
            <label>연구 분야 (Research Field)</label>
            <input type="text" name="paperResearchField" placeholder="연구 분야" value="<?php $utils->display($response["data"]["research_field"]);?>">
            <div class="ui pointing blue basic label">
                <p>희망 참가 세션과는 별개로 본인이 생각하는 연구의 분야를 입력해 주세요.</p>
            </div>
        </div>
        <div class="field required">
            <label>희망 참가 세션 (Desired Session)</label>
            <select class="ui fluid dropdown" name="paperDesiredSession" id="paperDesiredSession">
                <?php foreach($strings["session_names"] as $key => $value) { ?>
                <option value="<?php echo($key);?>"><?php echo($value);?></option>
                <?php } ?>
            </select>
        </div>
        <div class="required inline field" style="margin-top:25px">
            <div class="ui checkbox">
                <input type="checkbox" tabindex="0" name="paperAgreeTerms" class="hidden">
                <label>본인은 KSCY <a onclick="$('#terms').modal('show')">운영 및 심사 방침</a>에 동의합니다</label>
            </div>
        </div>
        <button class="ui button" type="submit" style="margin-top: 15px">지원서 <?php echo($response["review"] ? "수정" : "제출")?></button>
        <?php if ($response["review"]) {
            echo('<a class="ui button" href="./application.delete.php?type=paper">지원서 삭제</a>');
        } ?>
        <a class="ui button" href="./" >취소</a>
    </form>

    <script>

    var paperTeamMembers = [ <?php if ($response["review"]) { echo($response["data"]["team_members"]); } ?>];
    var paperTeamLeader <?php if ($response["review"]) { echo("= ".$response["data"]["team_leader"]); } ?>;

    $("#paperDesiredSession option[value='<?php echo($response["data"]["desired_session"]); ?>']").prop("selected", true);
    $('.ui.checkbox').checkbox();
    $('.ui.form').form({
        fields: {
            paperTitle: 'minLength[3]',
            paperFile: 'empty',
            paperResearchField: 'minLength[2]',
            paperTeamMembers: 'minLength[1]',
            paperAgreeTerms: 'checked'
        }
    });

    $('#paperAddStudent').on('click', function() {
        $('#paperAddStudent').addClass("disabled");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './student-searcher.php',
            data: {studentEmail:$('#paperStudentEmail').val()},
            success: function (data) {

                $('#paperAddStudent').removeClass("disabled");

                if (paperTeamMembers.indexOf(Number(data.no)) >= 0) {
                    alert("이미 추가된 학생입니다.");
                    return;
                }

                paperTeamMembers.push(Number(data.no));
                var innerHTML = '<div class="item">'
                innerHTML += '<div class="left floated content">'
                innerHTML += '<div class="ui icon basic button" no="'+Number(data.no)+'">'
                innerHTML += '<i class="remove icon"></i>'
                innerHTML += '</div>'
                innerHTML += '</div>'
                innerHTML += '<div class="content">'
                innerHTML += '<div class="header">'+data.name+'&nbsp;</div>'
                innerHTML += data.school + " " + data.grade + "학년"
                innerHTML += '</div>'
                innerHTML += '</div>'
                $('#paperTeamMembers').val(paperTeamMembers.join(","));
                $('#paperStudentsList').append(innerHTML);
                $('#paperStudentEmail').val("");

                if (paperTeamLeader != paperTeamMembers[0]) {
                    paperTeamLeader = paperTeamMembers[0];
                    var labelElement = '<div class="ui tiny horizontal label">팀장</div>';
                    var firstElement = $(".ui.icon.basic.button[no='"+paperTeamMembers[0]+"']").parent().parent().find(".content").find(".header");
                    firstElement.append(labelElement);

                }
            },
            error: function (request, status, error) {
                $('#paperAddStudent').removeClass("disabled");
                alert("학생을 찾을 수 없습니다. 등록된 학생인지 확인해 주세요.");
            }
        });
    });

    $('#paperStudentsList').on('click', '.ui.icon.basic.button', function() {

        if (paperTeamMembers.length < 2) {
            alert("팀원은 최소 한 명 이상이어야 합니다.");
            return;
        }
        paperTeamMembers.splice(paperTeamMembers.indexOf(Number($(this).attr('no'))), 1);
        $('#paperTeamMembers').val(paperTeamMembers.join(","));
        $(this).parent('div').parent('div').remove();

        if (paperTeamLeader != paperTeamMembers[0]) {
            paperTeamLeader = paperTeamMembers[0];
            var labelElement = '<div class="ui tiny horizontal label">팀장</div>';
            var firstElement = $(".ui.icon.basic.button[no='"+paperTeamMembers[0]+"']").parent().parent().find(".content").find(".header");
            firstElement.append(labelElement);
        }
    })


    $('.ui.file.input').find('input:text, .ui.button').on('click', function(e) {
        $(e.target).parent().find('input:file').click();
    })
    ;

    $('input:file', '.ui.file.input').on('change', function(e) {
        var file = $(e.target);
        var name = '';
        for (var i=0; i<e.target.files.length; i++) {
        name += e.target.files[i].name + ', ';
        }
        name = name.replace(/,\s*$/, '');
        $('input:text', file.parent()).val(name);
    });

    </script>
<?php } ?>


