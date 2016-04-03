<?php 
define("SUMMETA","<! --this is the first view page created at ".date("Y-m-d H:i:s")." by JackieAtHome index.php -->");
 $kv = new SaeKV();
 $kv->init();
if($_GET['s']){
	$url = $_SERVER['SCRIPT_URI'].'?s='.$_GET['s'];
	
	sae_debug("redirect to search page, url=".$url);
	echo fetchUrl($url);
	exit;
}
$sitemap = $kv->get($_SERVER['SCRIPT_URI'].'index.html');
if ($sitemap) {
	header('Content-type:text/html; charset=utf-8');
	echo $sitemap;
	sae_debug("using static page for main page, SCRIPT_URI=".$_SERVER['SCRIPT_URI']."index.html");
}else{
	echo fetchUrl($_SERVER['SCRIPT_URI']).SUMMETA;
	sae_debug("create static page for main view, SCRIPT_URI=".$_SERVER['SCRIPT_URI']."index.html");
}

function fetchUrl($url){
	$ch=curl_init();
	curl_setopt($ch, CURLOPT_AUTOREFERER,0);
	curl_setopt($ch, CURLOPT_REFERER, 'staticindex');
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
