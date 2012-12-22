<?php
$url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
if (!empty($url)) {
	header('Content-type: application/json');
	echo file_get_contents('http://www.reddit.com/api/info.json?url=' . $url);
}
exit;