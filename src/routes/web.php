<?php
namespace Routes;

use Framework\Routes;

class web extends Routes 
{
	
	public function __construct()
	{
		$this->loader();
	}

	/**
	 * 여기에 라우터들을 추가하면 된다.
	 * @return void
	*/
	public function loader()
	{
		parent::get('/', ['IndexController', 'index']);
		// parent::get('/authors/{id}', ['IndexController', 'get']);
		// parent::get('/authors/{id}/post/{pid}', ['IndexController', 'set']);
		parent::post('/categories/buildexcel', ['CategoriesController', 'buildExcel']);
		parent::get('/test', ['CategoriesController', 'test']);

	}

}