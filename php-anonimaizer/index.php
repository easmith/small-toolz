<?php
require_once 'ParserRequest.php';

// Формируем URL
$url = "http://www.bilet-on-line.ru" . $_SERVER['REQUEST_URI'];

$pr = new ParserRequest('bol', md5($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']));
$pr->setUserAgent($_SERVER['HTTP_USER_AGENT']);

$page = $_SERVER['REQUEST_METHOD'] == 'POST' ?  $pr->post($url, $_POST) : $pr->get($url);

echo $page['result'];
