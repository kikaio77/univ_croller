<?php
namespace App\Controllers;

class indexController 
{
	public function index()
	{	
		return ['title' => '김유진님 안녕하세요!', 'template' => 'index.html.php', 'vars' => []];
	}

	public function get($id)
	{

	}

	public function set($id, $pid)
	{

	}
}
