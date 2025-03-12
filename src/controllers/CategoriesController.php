<?php
namespace App\Controllers;

use phpoffice\phpspreadsheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

include __DIR__.'/../expansions/simple_html_dom.php';

class CategoriesController 
{
	public function buildExcel($request)
	{	
		$url = $request->target;
		
		
		if (! filter_var($url, FILTER_VALIDATE_URL)) {
			return ['vars' => ['result' => false, 'msg' => '올바른 URL 형식이 아닙니다.']];
		}
	
		$indexPage = $this->getHtmlByCurl($url);

		$products = [];
		$regxp 	= '/\/category\/([^\/?]+)/';
		$regxp2 = '/ctg_(sub|third)_code=/';
		$attr = 'data-url_path';
       

		//1뎁스에 대해서 저장함
		foreach ($indexPage->find('.usNavLeft .usNavLeftButton') as $div) {
			$products[$div->{$attr}]['name'] = $div->plaintext;
			$products[$div->{$attr}]['slides'] = [];
			$products[$div->{$attr}]['statics'] = [];
			$products[$div->{$attr}]['children'] = [];
		}

		
			
	
		foreach ($indexPage->find('.usItemCategoryDropdownList a') as $idx => $a) {
			
			$href = $a->href;

			preg_match($regxp, $href, $matches);

			$rootDepth = $matches[1];

			if (isset($products[$rootDepth])) {

				$relation = explode('?', $href)[1];

				$relation = preg_replace($regxp2, '', $relation);
	
				$depths = explode('&', $relation);


				if ($a->class === 'usItemCategoryDropdownListItemMore') {
					
					$sideBarHtml = $this->getHtmlByCurl($url.$href);
					
					foreach ($sideBarHtml->find('.usthirdCategoryActive a') as $sideIdx => $sideA) {
						
						$compareRelation = explode('?', $sideA->href)[1];
						$compareRelation = preg_replace($regxp2, '', $compareRelation);

						$compareDepths = explode('&', $compareRelation);

						if ($this->checkNodeInArray($products[$rootDepth], $compareDepths))
							continue;
						
						$child = [
							'name' => trim($sideA->plaintext),
							'href' => $url . $sideA->href,
							'slides' => [],
							'statics' => [],
						];
		
						$this->buildTreeArray($child, $compareDepths, $products[$rootDepth]);

					}
					
	
				} else {
					
	
					$child = [
						'name' => trim($a->plaintext),
						'href' => $url . $a->href,
						'slides' => [],
						'statics' => [],
					];
	
					$this->buildTreeArray($child, $depths, $products[$rootDepth]);

				}

			
	
			}

		}


		$maxDepth = PHP_FLOAT_MIN;
	

		$attrByClass = ['.usCategoryBandBanner .usListBannerImage' => 'data-href', '.swiper-wrapper a' => 'href'];


		foreach ($products as $rootDepth => &$rootInfo) {

			$numByRootDepth = 1;

			$this->calculateTreeDepth($rootInfo, $numByRootDepth);

			$maxDepth = max($maxDepth, $numByRootDepth);

			foreach ($rootInfo['children'] as &$child) {
				
				$this->scrapingLandingPage($url, $child, $attrByClass);
			}
			unset($child);
		}

		unset($rootInfo);

		
	
		$data = [];

		foreach ($products as $rootDepth => $rootInfo) 
			$data = $this->buildSheetArray($maxDepth, $rootInfo, $data);
		

		$header = [];
		for ($i = 1; $i <= $maxDepth; $i++) 
			$header[] = '카테고리'.$i;

		$header = array_merge($header, ['슬라이드 랜딩 내역', '이미지 랜딩 내역']);
		
		$fileNm = date('Y-m-d H:i:s') . '_유니브 랜딩 내역.xlsx';
		
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header("Content-Disposition: attachment; filename={$fileNm}");
		header('Cache-Control: max-age=0');


		$spreadSheet = new Spreadsheet();
		$sheet = $spreadSheet->getActiveSheet();
		$sheet->fromArray($header, null, 'B2');
		$sheet->fromArray($data, null, 'B3');
		
		$sheet->getStyle('B2:F2')->getFont()->setBold(true);
		$sheet->getStyle('B2:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

		$xlsx = new Xlsx($spreadSheet);
		$xlsx->save('php://output');

		exit();
	
		return ['vars' => [ 'products' => $products]];
	}


	private function getHtmlByCurl($url) 
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 타임아웃 설정
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36') );

		$html = curl_exec($ch);
		curl_close($ch);

		return str_get_html($html);
	}

	private function buildTreeArray($child, $iterArray, &$retval)
	{
		if (empty($iterArray)) 
			return;

		$key = array_shift($iterArray);

		if (! isset($retval['children'][$key])) {

			$retval['children'][$key] = [
				'name' => $child['name'],
				'href' => $child['href'],
				'slides' 	=> [],
				'statics'	=> [],
				'children'  => [],
			];
		}

		$this->buildTreeArray($child, $iterArray, $retval['children'][$key]);

	}

	/**
	 * 스크래핑 랜딩에 필요한 정보를 긁어옴
	 * @param array $data
	 * @param array $classByNodeAttr
	 * @param string $imgLinkAttr
	 * @return void
	 */
	private function scrapingLandingPage($url, array &$data, array $attrByClass, string $imgLinkAttr = 'data-desktop-src')
	{
		if (isset($data['href'])) {
			$html = $this->getHtmlByCurl($data['href']);


			foreach ($attrByClass as $class => $nodeAttr) {
				
				if (empty($html->find($class)))
					continue;

				foreach ($html->find($class) as $img) {
				
					switch ($class) {
						case '.usCategoryBandBanner .usListBannerImage':
							$data['statics'][] = $img->{$imgLinkAttr}."\r\n".$url . $img->{$nodeAttr}."\r\n"; 
							break;
						case '.swiper-wrapper a':
							$data['slides'][] = $img->{$imgLinkAttr}."\r\n".$url . $img->{$nodeAttr}."\r\n"; ;							
							break;
					} 
				}
			}

		}

		if (!empty($data['children'])) {
			foreach ($data['children'] as &$child)
				$this->scrapingLandingPage($url, $child, $attrByClass);
		}
	}

	private function buildSheetArray($maxDepth, $data, &$result, $path = [], $isTwoDepthOrigin = false) 
	{	

		
		if (isset($data['name'])) {			
			$path[] = $data['name'];
		}

		$curDepth = count($path);

		//자식노드가 없는 경우
		if (empty($data['children'])) {

			$tmp = $path;
			while ($curDepth < $maxDepth) {
				$tmp[] = '';
				$curDepth++;
			}
		
			$tmp[] = ( ! isset($data['slides']) || empty($data['slides'])) ? '' :  rtrim(implode("", $data['slides']));
			$tmp[] =  (! isset($data['statics']) || empty($data['statics'])) ? '' : rtrim(implode("", $data['slides']));
			$result[] = $tmp;

		} else {
			
			foreach ($data['children'] as  $idx => $child) {

				if ( $curDepth === 2 && array_key_first($data['children']) === $idx) {
					$isTwoDepthOrigin = true;
				}

				if ($isTwoDepthOrigin) {

					$tmp = $path;

					while ($curDepth < $maxDepth) {
						$tmp[] = '';
						$curDepth++;
					}
					
					$tmp[] = ( ! isset($data['slides']) || empty($data['slides'])) ? '' : rtrim(implode("", $data['slides']));
					$tmp[] =  (! isset($data['statics']) || empty($data['statics'])) ? '' :  rtrim(implode("", $data['statics']));
					$result[] = $tmp;

					$isTwoDepthOrigin = false;
				}

				$this->buildSheetArray($maxDepth, $child, $result, $path, $isTwoDepthOrigin);
			}
		}

		return $result;
	}

	private function calculateTreeDepth($data, &$depth = 0)
	{
		if (empty($data['children'])) {
			$depth++;
			return $depth;
		}

		$depth++;
		$this->calculateTreeDepth($data['children'], $depth);
	}

	private function checkNodeInArray($data, $nodes)
	{
		if (empty($nodes))
			return true;

		$currentDepth = array_shift($nodes);

		if (! isset($data['children'][$currentDepth]))
			return false;

		return $this->checkNodeInArray($data['children'][$currentDepth], $nodes);
	}
}	

