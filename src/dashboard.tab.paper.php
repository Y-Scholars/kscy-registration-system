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

function process_paper() {

    global $db;
    global $session;
    global $utils;

    // 접근 권한 검사
    if ($session->get_level() < 1)  {
        return array(
            "result" => "error",
            "message" => "접근 권한이 없습니다."
        );
    }

    // 데이터 불러오기
    $applications = $db->in('kscy_papers')
                        ->select("*")    
                        ->go_and_get_all();
    
    // 팀원 정보도 함께 불러와서 할당
    foreach ($applications as &$application) {

        $team_members_no = explode(",", $application["team_members"]);
        $team_members_data = $db->in("kscy_students")->select("*");

        foreach ($team_members_no as $team_member_no) {
            $team_members_data->where("no", "=", $team_member_no, "OR");
        }
        $team_members_data = $team_members_data->go_and_get_all();
        $application["team_members_data"] = $team_members_data;
    }
    unset($application);

    return array(
        "result" => "success",
        "data" => $applications
    );
}

function render_paper($response) {

    global $utils;
    global $strings;

    ?>
    <div class="ui modal application">
        <div class="header">논문 발표 지원서</div>
        <div class="content">
            <table class="ui selectable definition celled sortable table">
                <tbody>
                    <tr>
                        <td>번호</td>
                        <td id="paperNo">없음</td>
                    </tr>
                    <tr>
                        <td>제목</td>
                        <td id="paperTitle">없음</td>
                    </tr>
                    <tr>
                        <td>파일</td>
                        <td><a id="paperDownload" href="#">다운로드</a></td>
                    </tr>
                    <tr>
                        <td>연구분야</td>
                        <td id="paperResearchField">없음</td>
                    </tr>
                    <tr>
                        <td>희망 세션</td>
                        <td id="paperDesiredSession">없음</td>
                    </tr>
                    <tr>
                        <td>등록 일시</td>
                        <td id="paperTimestamp">없음</td>
                    </tr>
                </tbody>
            </table>
            <a id="paperModify" class="ui basic button"><i class="icon write"></i>지원서 수정</a>
            <a id="paperDelete" class="ui basic button"><i class="icon trash"></i>지원서 삭제</a>
        </div>
        <div class="actions">
            <div class="ui cancel button">닫기</div>
        </div>
    </div>
    <h2 class="ui header">논문 발표 지원서</h2>
    <a class="ui basic button" href="./dashboard.export.php?type=paper"><i class="icon download"></i>엑셀로 내보내기...</a>
    <a class="ui basic button" href="./download.php?type=papers-all"><i class="icon download"></i>전체 지원서 내려받기...</a>
    <table class="ui structured celled table">
        <thead>
            <tr>
                <th>#</th>
                <th class="three wide">제목</th>
                <th class="two wide">분야</th>
                <th class="two wide">희망 세션</th>
                <th class="two wide">이름</th>
                <th>학교</th>
                <th class="two wide">참가비</th>
                <th>지원서 열람</th>
                <th class="two wide">합격 여부</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 1;
        foreach ($response["data"] as $application) {

            $first = true;
            $team_members_no = count($application["team_members_data"]);

            foreach ($application["team_members_data"] as $team_member_data) {?>
                <tr class="<?php echo($team_member_data["tag"]);?>">
                <?php if ($first) { ?>
                    <td rowspan="<?php echo($team_members_no);?>"><?php echo($count);?></td>
                    <td rowspan="<?php echo($team_members_no);?>"><?php echo(mb_strimwidth($application["title"], 0, 35, '...'));?></td>
                    <td rowspan="<?php echo($team_members_no);?>"><?php echo(mb_strimwidth($application["research_field"], 0, 35, '...'));?></td>
                    <td rowspan="<?php echo($team_members_no);?>"><?php echo($strings["session_names"][$application["desired_session"]]);?></td>
                <?php } ?>
                <?php if ($team_member_data["no"] == $application["team_leader"]) { ?>
                    <td><a class="name student" data-no="<?php echo($team_member_data["no"]);?>"><?php echo($team_member_data["name"]);?></a> <div class="ui tiny horizontal label">팀장</div></td>
                <?php } else { ?>
                    <td><a class="name student" data-no="<?php echo($team_member_data["no"]);?>"><?php echo($team_member_data["name"]);?></a></td>
                <?php } ?>
                    <td><?php echo(mb_strimwidth($team_member_data["school"], 0, 25, '...'));?></td>                
                    <td>
                        <select class="ui dropdown deposit fluid" data-no="<?php echo($team_member_data["no"]);?>">
                            <?php foreach($strings["deposit_names"] as $key => $value) { ?>
                            <option value="<?php echo($key);?>"<?php echo($team_member_data["deposit_status"] == $key ? " selected" : "");?>><?php echo($value);?></option>
                            <?php } ?>
                        </select>
                    </td>
                <?php if ($first) { ?>
                    <td rowspan="<?php echo($team_members_no);?>">
                        <button class="ui icon button fluid" data-no="<?php echo($application["no"]);?>"><i class="icon unhide"></i></button>
                    </td>
                    <td rowspan="<?php echo($team_members_no);?>">
                    <select class="ui dropdown application fluid" data-no="<?php echo($application["no"]);?>">
                        <?php foreach($strings["approved_names"] as $key => $value) { ?>
                        <option value="<?php echo($key);?>"<?php echo($application["approved"] == $key ? " selected" : "");?>><?php echo($value);?></option>
                        <?php } ?>
                    </select>
                    </td>
                <?php } ?>
                </tr>
                <?php $first = false;
            }
            $count++;
        } ?>
        </tbody>
    </table>
    <script>
    $('.ui.modal').modal();
    $('.ui.checkbox').checkbox();

    $('.ui.dropdown.deposit').on("change", function() {
        var self = this;
        $(self).addClass("disabled");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './dashboard.ajax.php',
            data: { action: "save", type: "student", no: $(self).data("no"), key: "deposit_status", value: $(self).val() },
            success: function (data) {
                $(self).removeClass("disabled");
            },
            error: function (request, status, error) {
                $(self).removeClass("disabled");
                alert("상태 업데이트에 실패했습니다.");
            }
        });
    });

    $('.ui.dropdown.application').on("change", function() {
        var self = this;
        $(self).addClass("disabled");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './dashboard.ajax.php',
            data: { action: "save", type: "paper", no: $(self).data("no"), key: "approved", value: $(self).val() },
            success: function (data) {
                $(self).removeClass("disabled");
            },
            error: function (request, status, error) {
                $(self).removeClass("disabled");
                alert("상태 업데이트에 실패했습니다.");
            }
        });
    });

    $('.ui.icon.button').on("click", function() {
        var self = this;
        $(self).addClass("loading");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './dashboard.ajax.php',
            data: { action: "load", type: "paper", no: $(self).data("no")},
            success: function (data) {
                $(self).removeClass("loading");
                $("#paperNo").html(data.no);
                $("#paperTitle").html(data.title);
                $("#paperDownload").attr("href", "./download.php?type=paper&no=" + data.no);
                $("#paperResearchField").html(data.research_field);
                $("#paperDesiredSession").html(data.desired_session);
                $("#paperTimestamp").html(data.timestamp);
                $("#paperModify").attr("href", "./application.php?review=true&no=" + data.team_leader);
                $('.ui.modal.application').modal('show');
            },
            error: function (request, status, error) {
                $(self).removeClass("loading");
            }
        });
    });
    </script>
<?php
}
?>