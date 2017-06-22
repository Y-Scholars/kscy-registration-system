<?php
/*
 * KSCY Registration System 2.0
 * 
 * Written By HyunJun Kim
 * 2017. 06. 22
 */

error_reporting(0);

require_once("./session.php");

if (empty($session->get_student_no())) {
    exit();
}

$file = "./files/".$_GET["type"]."s/".$session->get_student_no().".".$_GET["extension"];
$filename = $_GET["type"].".".$_GET["extension"];

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename=' . $filename);
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($file));
ob_clean();
flush();
readfile($file);
exit();
?>