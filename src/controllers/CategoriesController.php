<?php

namespace App\Controllers;

class CategoriesController 
{
	public function buildExcel()
	{	
		$fileNm = 'univ_tmp.xlsx';

		$xlsxPath = __DIR__.'/../../storage/'.$fileNm;

		if (file_exists($xlsxPath)) {

			error_log('파일있음');

			header('Content-Description: File Transfer');
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="' . basename($xlsxPath) . '"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			header('Content-Length: ' . filesize($xlsxPath));

			readfile($xlsxPath);
			exit();
		} 

		$output = [];
		$retval = 0;

		exec('/usr/bin/php /mnt/share/univCroller_loc/bin/genExcel.php 2>&1', $output, $retval);

		if ($output != 0) {
			http_response_code(500);
			echo '엑셀 다운로드에 실패했습니다. 다시 시도해주세요.';
			exit;
		}

		$this->buildExcel();

	}

}	

