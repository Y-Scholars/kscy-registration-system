<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 15
 */

$header_meta_description = "KSCY 참가접수 시스템";
$header_title = "KSCY " . $title_korean;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="description" content="<?php echo($header_meta_description);?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title><?php echo($header_title);?></title>
    
    <link rel="stylesheet" type="text/css" href="./css/semantic.min.css">
    <link rel="stylesheet" type="text/css" href="./css/default.css">

    <script src="./js/jquery.min.js"></script>
    <script src="./js/semantic.min.js"></script>
</head>
<body>
<div class="kscy-header-background">
    <div class="ui container">
        <a href="/"><img class="kscy-header-logo" src="./images/site-top-logo.png"/></a>
        <h1 class="kscy-header-subtitle"><?php echo($title_korean);?></h1>
        <h1 class="kscy-header-subtitle"><?php echo($title_english); if ($session->get_level() >= 1)  { ?><a class="ui right floated inverted basic button" href="./authentication.php?logout=true">로그아웃</a><?php } ?></h1>
        
    </div>
</div>

