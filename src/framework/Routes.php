<?php
namespace Framework;

class Routes 
{
	public $routes;
	
	const CTRL_PATH = 'App\\Controllers\\';

	public function __construct()
	{
		
	}
	
	public function get($pattern, $callback)
	{
		$this->routes['GET'][] = ['pattern' => $pattern, 'callback' => $callback];
	}
	public function post($pattern, $callback)
	{
		$this->routes['POST'][] = ['pattern' => $pattern, 'callback' => $callback];
	}
	public function delete($pattern, $callback)
	{
		$this->routes['DELETE'][] = ['pattern' => $pattern, 'callback' => $callback];
	}
	public function patch($pattern, $callback)
	{
		$this->routes['PATCH'][] = ['pattern' => $pattern, 'callback' => $callback];
	}

	public function dispatch($method, $url, $request)
	{	
		foreach ($this->routes[$method] as $idx => $route) {
			
			preg_match_all('/{([^\/]+)}/', $route['pattern'], $bindingColumns);
			
			array_shift($bindingColumns);
			
			$bindingColumns = $bindingColumns[0];
			
			$pattern = "@^".preg_replace('/{([^\/]+)}/', '([^/]+)', $route['pattern'])."$@";
			
			if (preg_match($pattern, $url, $matches)) {
				
				array_shift($matches);

				[ $controller, $action ] = $route['callback'];

				$controller = self::CTRL_PATH . $controller;
				
				if (
					class_exists($controller) 
					&& method_exists($controller, $action)
				) {
					
					$ctrlObj = new $controller();

					if (! empty($bindingColumns)) {
						$loopCnt = sizeof($bindingColumns);
						$args = [];
						
						for ($i = 0; $i < $loopCnt; $i++) {
							$args[$bindingColumns[$i]] = $matches[$i];	
						}
						
						return call_user_func_array([$ctrlObj, $action], $args);
					}
					return call_user_func_array([$ctrlObj, $action], [$request]);
				}
			} 

			http_response_code(400);
			die();

		}
	}



}