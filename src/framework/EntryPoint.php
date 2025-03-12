<?php
namespace Framework;

use Framework\Routes;
use Routes\web;

class EntryPoint 
{
	const CTRL_PATH = 'App\\Controllers\\';
	
	private $routes;
	private $method;
	private $url;
	private $request;

	public function __construct(Routes $routes, string $method, $url, Object $request)
	{
		$this->routes = $routes;
		$this->method = strtoupper($method);
		$this->url = $url;
		$this->request = $request;
		$this->checkUrl();
	}

	private function checkUrl()
	{
		$lowerUrl = strtolower($this->url);

		if ($lowerUrl !== $this->url) {
			http_response_code(400);
			die();
		}
	}

	public function run()
	{
		$pureUrl= strtok($this->url, '?');
		
		$page = $this->routes->dispatch($this->method, $pureUrl, $this->request);

		//ajax로 들어온 경우는 이렇게 처리함

		// error_log('ajax 여부 : '.$_SERVER['HTTP_X_REQUESTED_WITH']);

		if ( !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
			header('Content-Type: application/json; charset=UTF-8');
			http_response_code(200);
			echo json_encode($page['vars'], JSON_UNESCAPED_UNICODE);
			exit();
		}

		$output =  $this->loadTemplate($page['template'], $page['vars']);

		echo $this->loadTemplate('layout/master.html.php', ['output' => $output, 'title' => $page['title']]);
	}

	public function loadTemplate(string $path, Array $returnVar = [])
	{
		extract($returnVar);
		ob_start();
		include __DIR__.'/../../resources/templates/'.$path;
		return ob_get_clean();
	}


}