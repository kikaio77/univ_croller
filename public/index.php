<?php

use Framework\EntryPoint;
use Routes\web;

try {
	require __DIR__.'/../vendor/autoload.php';

	$entryPoint = new EntryPoint(new Web(), $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], (Object)$_REQUEST);
	
	$entryPoint->run();

} catch (Exception $e) {
	$output = '에러가 발생했습니다. 관리자에게 연락주세요!';
	$title = '에러 발생';
	echo $entryPoint->loadTemplate('error/error.html.php', ['message' => $output]);
}