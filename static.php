<?php 
define("SUMMETA","<! --this is the first view page created at ".date("Y-m-d H:i:s")." by JackieAtHome static.php  -->");
$kv = new SaeKV();
$kv->init();

$requestUri = $_SERVER['SCRIPT_URI'];
$sitemap = $kv->get($requestUri);
if ($sitemap) {
	header('Content-type:text/html; charset=utf-8');
	echo $sitemap;
	sae_debug("using static page for singe view, static.php, SCRIPT_URI=".$requestUri);
} else {
	echo fetchUrl($requestUri).SUMMETA;
	sae_debug("create static page for singe view, static.php, SCRIPT_URI=".$requestUri);
}
function fetchUrl($url){
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_AUTOREFERER,0);
	curl_setopt($ch, CURLOPT_REFERER, 'static');
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$ret=curl_exec($ch);
	curl_close($ch);
	if ($ret) {
		return $ret;
	}else{
		return false;
	}
}
?>
