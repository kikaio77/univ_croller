<?php

namespace App\Controllers;

class CategoriesController 
{
	public function buildExcel($request)
	{	
		$fileNm = 'univ_tmp.xlsx';

		$xlsxPath = __DIR__.'/../../storage/'.$fileNm;

		if (file_exists($xlsxPath)) {

			header('Content-Description: File Transfer');
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="' . basename($xlsxPath) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($xlsxPath));

			readfile($xlsxPath);

		}
		
		exit();
	
	}

}	

