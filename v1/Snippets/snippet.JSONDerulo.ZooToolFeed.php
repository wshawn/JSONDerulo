<?php
/*
* @author Phil Steer
* @package JSONDerulo
* @site GitHub source: https://github.com/pdincubus/JSONDerulo
* @site MODX Extra: http://modx.com/extras/package/jsonderulo
* Fetches ZooTool feed in JSON format and allows templating via chunk
*/

$feedUrl = 'http://zootool.com/api/users/items/?username={username}&apikey={apikey}&limit={limit}';

$ch = null;

$tpl = $modx->getOption('tpl', $scriptProperties, '');
$limit = $modx->getOption('limit', $scriptProperties, 2);
$excludeEmpty = explode(',', $modx->getOption('excludeEmpty', $scriptProperties, 'image'));
$feeds = explode(',', $modx->getOption('users', $scriptProperties, ''));
$apiKey = $modx->getOption('apiKey', $scriptProperties, '');
$cacheName = $modx->getOption('cacheName', $scriptProperties, '');
$cacheTime = $modx->getOption('cacheTime', $scriptProperties, 43200);

$rawFeedData = array();
$cacheName = str_replace(" ", "-", $cacheName);

foreach ($feeds as $username) {
	$cacheId = 'zootoolfeed-'.$cacheName.'-'.$username;

	if (($json = $modx->cacheManager->get($cacheId)) === null) {
		if ($ch === null) {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		}

		curl_setopt_array($ch, array(
		  CURLOPT_URL => str_replace(array('{apikey}', '{username}', '{limit}'), array($apiKey, $username, $limit), $feedUrl),
		));


	  	$json = curl_exec($ch);
		if (empty($json)) {
			continue;
		}

		$modx->cacheManager->set($cacheId, $json, $cacheTime);
	}

	$feed = json_decode($json);

	if ($feed === null) {
		continue;
	}

	foreach ($feed as $image) {
		foreach ($excludeEmpty as $k) {
			if ($image->$k == '') {
				continue 2;
			}
		}

		$rawFeedData[] = array(
			'date' => $image->added,
			'picture' => $image->image,
			'title' => $image->title,
			'username' => $username,
		  	'referrer' => $image->referer,
		  	'permalink' => $image->permalink,
		);

	}
}

if ($ch !== null) {
	curl_close($ch);
}

$output = '';
foreach ($rawFeedData as $image) {
	$output .= $modx->getChunk($tpl, $image);
}

return $output;
