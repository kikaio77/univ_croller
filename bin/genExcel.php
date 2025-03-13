<?php
namespace bin;

require __DIR__.'/../vendor/autoload.php';

use App\Func;
use Error;
use phpoffice\phpspreadsheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


define('TARGET_URL', 'https://www.univstore.com');


$indexPage = Func::getHtmlByCurl(TARGET_URL);

$products = [];
$regxp 	= '/\/category\/([^\/?]+)/';
$regxp2 = '/ctg_(sub|third)_code=/';
$attr = 'data-url_path';

//1뎁스에 대해서 저장함
foreach ($indexPage->find('.usNavLeft .usNavLeftButton') as $div) {
	$products[$div->{$attr}]['name'] = $div->plaintext;
	$products[$div->{$attr}]['slide_url'] = [];
	$products[$div->{$attr}]['slide_img_url'] = [];
	$products[$div->{$attr}]['banner_url'] = [];
	$products[$div->{$attr}]['banner_img_url'] = [];
	$products[$div->{$attr}]['children'] = [];
}

//세부 뎁스를 저장을 위함
foreach ($indexPage->find('.usItemCategoryDropdownList a') as $idx => $a) {
			
	$href = $a->href;

	preg_match($regxp, $href, $matches);

	$category = $matches[1];
	
	$child = [
		'slide_url' => [],
		'slide_img_url' => [],
		'banner_url' => [],
		'banner_img_url' => [],
	];

	if ($a->class === 'usItemCategoryDropdownListItemMore' ) {

		$subPage = Func::getHtmlByCurl(TARGET_URL . $a->href);

		foreach ($subPage->find('.usthirdCategoryActive a') as $menu) {
			$queryStr = explode('?', $menu->href)[1];
			$relation = preg_replace($regxp2, '', $queryStr);
			$depths = explode('&', $relation);

			if (Func::checkNodeInArray($products[$category], $depths))
				continue;

			$child['name'] = trim($menu->plaintext);
			$child['href'] = TARGET_URL . $menu->href;
	
			Func::buildTreeArray($child, $depths, $products[$category]);

		}


	} else {
		
		$queryStr = explode('?', $href)[1];
		$relation = preg_replace($regxp2, '', $queryStr);
		$depths = explode('&', $relation);
		
		$child['name'] = trim($a->plaintext);
		$child['href'] = TARGET_URL . $a->href;

		Func::buildTreeArray($child, $depths, $products[$category]);
	}
	
}


$maxDepth = PHP_FLOAT_MIN;

$attrByClass = ['.usCategoryBandBanner .usListBannerImage' => 'data-href', '.swiper-wrapper a' => 'href'];

$maxColSize = [
	'slide' => PHP_FLOAT_MIN,
	'banner' => PHP_FLOAT_MIN, 
];

foreach ($products as $category => &$info) {

	$maxDepth = max(Func::calculateTreeDepth($info), $maxDepth);

	foreach ($info['children'] as &$child)
		Func::scrapingLandingPage(TARGET_URL, $child, $attrByClass, $maxColSize);
	
	unset($child);
}


unset($info);

$data = [];

foreach ($products as $category => $info) 
	$data = Func::buildSheetArray($maxDepth, $info, $data, $maxColSize);

$header = [];


//헤더 만드는 부분
for ($i = 1; $i <= $maxDepth; $i++) 
	$header[] = '카테고리'.$i;

for ($i = 1; $i <= $maxColSize['slide']; $i++) {
	$header[] = implode('_', ['슬라이드', $i ,'URL']);
	$header[] = implode('_', ['슬라이드이미지', $i ,'URL']);
}

for ($i = 1; $i <= $maxColSize['banner']; $i++) {
	$header[] = implode('_', ['배너', $i ,'URL']);
	$header[] = implode('_', ['배너이미지', $i ,'URL']);
}

$fileNm = 'univ_tmp.xlsx';
$filePath = __DIR__.'/../storage/'.$fileNm;

$spreadSheet = new Spreadsheet();
$sheet = $spreadSheet->getActiveSheet();
$sheet->fromArray($header, null, 'B2');
$sheet->fromArray($data, null, 'B3');

$sheet->getStyle('B2:F2')->getFont()->setBold(true);
$sheet->getStyle('B2:F2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$xlsx = new Xlsx($spreadSheet);
$xlsx->save($filePath);

exit();
