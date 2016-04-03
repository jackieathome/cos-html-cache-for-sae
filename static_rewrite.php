<?php 
define("SUMMETA","<! --this is the first view page created at ".date("Y-m-d H:i:s")." by JackieAtHome static_rewrite.php  -->");
// - rewrite: if ( %{REQUEST_URI} ~ "archives/\d+" ) goto "wp-content/plugins/cos-html-cache/static_rewrite.php?%{QUERY_STRING}"
 $kv = new SaeKV();
 $kv->init();

 $requestUri = $_SERVER['SCRIPT_URI'].".html";
 $sitemap = $kv->get($requestUri);
 if ($sitemap) {
 	header('Content-type:text/html; charset=utf-8');
  	echo $sitemap;
  	sae_debug("using static page for singe view, SCRIPT_URI=".$requestUri);
 }else{
 	echo fetchUrl($requestUri).SUMMETA;
 	sae_debug("create static page for singe view, SCRIPT_URI=".$requestUri);
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