<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 23
 */

require_once("./db.php");
require_once("./utils.php");
require_once("./session.php");
require_once("./strings.php");

error_reporting(-1);

function process_log() {

    global $db;
    global $session;
    global $utils;

    // 접근 권한 검사
    if ($session->get_level() < 2)  {
        return array(
            "result" => "error",
            "message" => "접근 권한이 없습니다."
        );
    }

    // 데이터 불러오기
    $logs = $db->in('kscy_logs')
                ->select("*")    
                ->go_and_get_all();
    
    $admin_users = array();

    // 팀원 정보도 함께 불러와서 할당
    foreach ($logs as &$log) {
        if (array_key_exists($log["user"], $admin_users)) {
            $log["user_name"] = $admin_users[$log["user"]]["name"];
        } else {
            $admin_user = $db->in('kscy_students')
                             ->select("*")
                             ->where("no", "=", $log["user"])
                             ->go_and_get();
            $admin_users[$log["user"]] = $admin_user;
            $log["user_name"] = $admin_user["name"];
        }
    }
    unset($log);

    return array(
        "result" => "success",
        "data" => $logs
    );
}

function render_log($response) {

    global $utils;
    global $strings;

    ?>
    <h2 class="ui header">작업 로그</h2>
    <a class="ui basic button" href="./dashboard.export.php?type=log"><i class="icon download"></i>엑셀로 내보내기...</a>
    <table class="ui structured celled table">
        <thead>
            <tr>
                <th>#</th>
                <th>작업자</th>
                <th>대상</th>
                <th>행동</th>
                <th>데이터</th>
                <th>IP</th>
                <th>작업 시간</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 1;
        foreach ($response["data"] as $log) { ?>
            <tr>
                <td><?php echo($log["no"]);?></td>
                <td><a class="name student" data-no="<?php echo($log["user"]);?>">작업자 (<?php echo($log["user"]);?>)</td>
                <td><a class="name student" data-no="<?php echo($log["target_user"]);?>">학생 (<?php echo($log["target_user"]);?>)</td>
                <td><?php echo($log["action"]);?></td>
                <td><?php echo($log["data"]);?></td>
                <td><?php echo($log["ip"]);?></td>
                <td><?php echo($log["timestamp"]);?></td>
            </tr>
        <?php
            $count++;
        } ?>
        </tbody>
    </table>
    <script>
    $('.ui.modal').modal();
    $('.ui.checkbox').checkbox();

    $('.ui.icon.button').on("click", function() {
        var self = this;
        $(self).addClass("loading");
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: './dashboard.ajax.php',
            data: { action: "load", type: "mentoring", no: $(self).data("no")},
            success: function (data) {

                $(self).removeClass("loading");
                $("#studentBio").html(data.bio);
                $("#studentMotivation").html(data.motivation);
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