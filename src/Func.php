<?php

namespace App;

include __DIR__.'/../src/expansions/simple_html_dom.php';

class Func 
{
	public static function getHtmlByCurl($url) 
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

	public static function buildTreeArray($child, $iterArray, &$retval)
	{
		if (empty($iterArray)) 
			return;

		$key = array_shift($iterArray);

		if (! isset($retval['children'][$key])) {

			$retval['children'][$key] = [
				'name' => $child['name'],
				'href' => $child['href'],
				'slide_url' 	=> [],
				'slide_img_url' 	=> [],
				'banner_url'	=> [],
				'banner_img_url'	=> [],
				'children'  => [],
			];
		}

		self::buildTreeArray($child, $iterArray, $retval['children'][$key]);

	}

	/**
	 * Undocumented function
	 *
	 * @param string $url
	 * @param array $data
	 * @param array $attrByClass
	 * @param array $maxColSize
	 * @param string $imgLinkAttr
	 * @return void
	 */
	public static function scrapingLandingPage(string $url, array &$data, array $attrByClass, array &$maxColSize = ['slide' => PHP_FLOAT_MIN, 'banner' => PHP_FLOAT_MIN], string $imgLinkAttr = 'data-desktop-src')
	{
		$html = self::getHtmlByCurl($data['href']);

		foreach ($attrByClass as $class => $nodeAttr) {
			
			if (empty($html->find($class)))
				continue;

			foreach ($html->find($class) as $img) {
			
				switch ($class) {
					case '.usCategoryBandBanner .usListBannerImage':
						$data['banner_url'][] = $url . $img->{$nodeAttr};
						$data['banner_img_url'][] = $img->{$imgLinkAttr};
						break;
					case '.swiper-wrapper a':
						$data['slide_url'][] = $url . $img->{$nodeAttr};
						$data['slide_img_url'][] = $img->{$imgLinkAttr};					
						break;
				} 
			}
		}

		if (sizeof($data['slide_url']) > 12) {
			error_log($data['name'].' '.print_r($data['slide_url'], true));
		}

		$maxColSize['slide'] = max($maxColSize['slide'], sizeof($data['slide_url']));
		$maxColSize['banner'] = max($maxColSize['banner'], sizeof($data['banner_url']));
	
		if (!empty($data['children'])) {
			foreach ($data['children'] as &$child)
				self::scrapingLandingPage($url, $child, $attrByClass, $maxColSize);
		}
		
	}

	public static function buildSheetArray($maxDepth, $data, &$result, array $maxColSize, $path = []) 
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
			
			for ($i = 0; $i < $maxColSize['slide']; $i++) {
				$tmp[] = isset($data['slide_url'][$i]) ? $data['slide_url'][$i] : '';
				$tmp[] = isset($data['slide_img_url'][$i]) ? $data['slide_img_url'][$i] : '';
			}

			for ($i = 0; $i < $maxColSize['banner']; $i++) {
				$tmp[] = isset($data['banner_url'][$i]) ? $data['banner_url'][$i] : '';
				$tmp[] = isset($data['banner_img_url'][$i]) ? $data['banner_img_url'][$i] : '';
			}

			$result[] = $tmp;

		} else {
			if (
				!empty($data['href']) 
				&& $curDepth < $maxDepth
			) {
				$tmp = $path;

				while ($curDepth < $maxDepth) {
					$curDepth++;
					$tmp[] = '';
				}

				for ($i = 0; $i <$maxColSize['slide']; $i++) {
					$tmp[] = isset($data['slide_url'][$i]) ? $data['slide_url'][$i] : '';
					$tmp[] = isset($data['slide_img_url'][$i]) ? $data['slide_img_url'][$i] : '';
				}

				for ($i = 0; $i <$maxColSize['banner']; $i++) {
					$tmp[] = isset($data['banner_url'][$i]) ? $data['banner_url'][$i] : '';
					$tmp[] = isset($data['banner_img_url'][$i]) ? $data['banner_img_url'][$i] : '';
				}
				$result[] = $tmp;
			}
			
			foreach ($data['children'] as $idx => $child) {
				self::buildSheetArray($maxDepth, $child, $result, $maxColSize, $path);
			}	

		}
		

		return $result;
	}

	public static function calculateTreeDepth($data, $depth = 0)
	{	
		++$depth;

		if (empty($data['children'])) {
			++$depth;
			return $depth;
		}

		return self::calculateTreeDepth($data['children'], $depth);
	}

	public static function checkNodeInArray($data, $nodes)
	{
		if (empty($nodes))
			return true;

		$currentDepth = array_shift($nodes);

		if (! isset($data['children'][$currentDepth]))
			return false;

		return self::checkNodeInArray($data['children'][$currentDepth], $nodes);
	}
}