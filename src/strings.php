<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 22
 */

$session_names = array(
    "1"=>"인문과학분야: 인문학세션",
    "17"=>"경영기업가정신분야: 경영학세션",
    "2"=>"사회과학분야: 경제학세션",
    "3"=>"사회과학분야: 사회학세션",
    "4"=>"사회과학분야: 정치외교학세션",
    "5"=>"사회과학분야: 교육학세션",
    "6"=>"사회과학분야: 심리학세션",
    "7"=>"자연과학분야: 수학세션",
    "8"=>"자연과학분야: 물리학세션",
    "9"=>"자연과학분야: 화학세션",
    "10"=>"생명과학분야: 생물학세션",
    "11"=>"생명과학분야: 의학세션",
    "12"=>"공학분야: 컴퓨터공학세션",
    "13"=>"공학분야: 전기전자/기계공학세션",
    "14"=>"공학분야: 환경공학세션 ",
    "15"=>"글로벌분야: 인문사회계열세션",
    "16"=>"글로벌분야: 이공계열세션",
);

$grade_names = array(
    "1" => "중학교 1학년",
    "2" => "중학교 2학년",
    "3" => "중학교 3학년",
    "4" => "고등학교 1학년",
    "5" => "고등학교 2학년",
    "6" => "고등학교 3학년",
    "7" => "기타 (검정고시 등)"
);

$survey_names = array(
    "" => "경로 선택",
    "1" => "SNS (페이스북)",
    "2" => "학교에 게시된 포스터",
    "3" => "선생님의 권유",
    "4" => "부모님의 권유",
    "5" => "주변 친구 및 선배의 권유",
    "6" => "기타"
);

$tag_names = array(
    "" => "없음",
    "positive" => "긍정",
    "negative" => "부정",
    "warning" => "중립"
);

$level_names = array(
    "0" => "권한 없음", 
    "1" => "퍼실리테이터",
    "2" => "코디네이터",
    "3" => "시스템 관리자",
    "4" => "시스템 개발자",
);

$deposit_names = array(
    "0" => "미납",
    "1" => "조기",
    "2" => "1차",
    "3" => "2차",
    "4" => "3차",
    "5" => "4차",
    "6" => "기타",
);

$approved_names = array(
    "0" => "심사 중",
    "1" => "합격",
    "2" => "불합격",
    "3" => "추가합격",
    "4" => "참가취소",
);

$strings = array(
    "session_names" => $session_names,
    "grade_names" => $grade_names,
    "survey_names" => $survey_names,
    "deposit_names" => $deposit_names,
    "approved_names" => $approved_names,
    "tag_names" => $tag_names,
    "level_names" => $level_names
);
?>