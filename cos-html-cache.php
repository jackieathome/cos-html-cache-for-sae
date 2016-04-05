<?php
/*
Plugin Name: cos-html-cache
Plugin URI: http://www.storyday.com/tag/cos-html-cache
Description: cos-html-cache is an extremely efficient WordPress page caching plugin designed to make your WordPress site much faster and more responsive. Based on URL rewriting, the plugin will automatically generate real html files for posts when they are loaded for the first time, and automatically renew the html files if their associated posts are modified.
cos-html-cache. Current version, cos-html-cache2.6, is a huge improvement over previous versions of cos-html-cache.
Version: 2.7.4
Author: jiangdong
date:2007-07-19
Author URI:http://www.storyday.com
*/
/*
Change log:
2007-06-02:  added custom cookie to fix Chinese charactor problems
2007-06-03:  added page cache function
2007-06-24:  fixed js bugs of chinese display
2007-07-25:	 changedd the cache merchanism
2007-08-14:	 changed the comment js
2008-02-21:  fixed database crush error
2008-04-06:  Compatible for wordpress2.5
2008-07-18:  Compatible for wordpress2.6  solved the cookie problems
2008-12-20:  fixed admin cookie httponly problems
2009-03-04:  fixed cookie '+' problems
2009-03-15:	 remove cache for password protected posts & fixed some js problems
2009-03-24:	 remove comment user cache data
2012-09-19:  cache remove bug fixed
				
*/
sae_debug("cos-html-cache.php, init");
/* config */
define('IS_INDEX',true);// false = do not create home page cache 
sae_debug("cos-html-cache.php, IS_INDEX=".IS_INDEX);
/*end of config*/

define('COSVERSION','2.7.4');
sae_debug("cos-html-cache.php, COSVERSION=".COSVERSION);
require_once(ABSPATH . 'wp-admin/includes/file.php');
/* end of config */
$sm_locale = get_locale();

$sm_mofile = dirname(__FILE__) . "/cosbeta-$sm_locale.mo";
load_textdomain('cosbeta', $sm_mofile);
$cossithome = get_option('home');
sae_debug("cos-html-cache.php, cossithome=".$cossithome);

$script_uri = rtrim( "http://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]  ,"/");
sae_debug("cos-html-cache.php, script_uri=".$script_uri);

$home_path = get_home_path();
sae_debug("cos-html-cache.php, home_path=".$home_path);

// date_default_timezone_set('PRC');
define('SCRIPT_URI',$script_uri);
sae_debug("cos-html-cache.php, SCRIPT_URI=".SCRIPT_URI);

define('CosSiteHome',$cossithome);
sae_debug("cos-html-cache.php, CosSiteHome=".$cossithome);

define('CosSiteHomeMain',$cossithome."/index.html");
sae_debug("cos-html-cache.php, CosSiteHomeMain=".CosSiteHomeMain);

define('CosBlogPath', $home_path);
sae_debug("cos-html-cache.php, CosBlogPath=".CosBlogPath);

define("COSMETA","<!--this is a real static html file created at ".date("Y-m-d H:i:s")." by cos-html-cache ".COSVERSION." -->");
sae_debug("cos-html-cache.php, succeed to define constants");
function CreateHtmlFile($FilePath,$Content){
	if ( !strstr( strtolower($Content), '</html>' ) ) {
		return;
	}
	
	$kv=new SaeKV();
	$kv->init();	
	$kv->set(SCRIPT_URI, $Content);
	sae_debug("CreateHtmlFile, succeed to create an static page, SCRIPT_URI=".SCRIPT_URI);
}
sae_debug("cos-html-cache.php, succeed to define CreateHtmlFile");
/* read the content from output buffer */
$is_buffer = false;
if( substr_count($_SERVER['REQUEST_URI'], '.htm') || ( SCRIPT_URI == CosSiteHome) ){
	if( strlen( $_COOKIE['wordpress_logged_in_'.COOKIEHASH] ) < 4 ){
		$is_buffer = true;
	}
	if(  substr_count($_SERVER['REQUEST_URI'], '?'))  $is_buffer = false;
	if(  substr_count($_SERVER['REQUEST_URI'], '../'))  $is_buffer = false;
}

if( $is_buffer ){
	ob_start('cos_cache_ob_callback');
	register_shutdown_function('cos_cache_shutdown_callback');
	sae_debug("cos-html-cache.php, succeed to define ob_start");
}

function cos_cache_ob_callback($buffer){

	$buffer = preg_replace('/(<\s*input[^>]+?(name=["\']author[\'"])[^>]+?value=(["\']))([^"\']+?)\3/i', '\1\3', $buffer);

	$buffer = preg_replace('/(<\s*input[^>]+?value=)([\'"])[^\'"]+\2([^>]+?name=[\'"]author[\'"])/i', '\1""\3', $buffer);
	
	$buffer = preg_replace('/(<\s*input[^>]+?(name=["\']url[\'"])[^>]+?value=(["\']))([^"\']+?)\3/i', '\1\3', $buffer);

	$buffer = preg_replace('/(<\s*input[^>]+?value=)([\'"])[^\'"]+\2([^>]+?name=[\'"]url[\'"])/i', '\1""\3', $buffer);
	
	$buffer = preg_replace('/(<\s*input[^>]+?(name=["\']email[\'"])[^>]+?value=(["\']))([^"\']+?)\3/i', '\1\3', $buffer);

	$buffer = preg_replace('/(<\s*input[^>]+?value=)([\'"])[^\'"]+\2([^>]+?name=[\'"]email[\'"])/i', '\1""\3', $buffer);

	if( !substr_count($buffer, '<!--cos-html-cache-safe-tag-->') ) return  $buffer;
	if( substr_count($buffer, 'post_password') > 0 ) return  $buffer;//to check if post password protected 
	$wppasscookie = "wp-postpass_".COOKIEHASH;
	if( strlen( $_COOKIE[$wppasscookie] ) > 0 ) return  $buffer;//to check if post password protected 
	/*
	$comment_author_url='';
$comment_author_email='';
$comment_author='';*/
	
	
	elseif( SCRIPT_URI == CosSiteHome) {// creat homepage
		$kv=new SaeKV();
		$kv->init();
		if (IS_INDEX) {
			$kv->set(CosSiteHomeMain, $buffer.COSMETA);
			sae_debug("cos_cache_ob_callback, succed to create static index page, key=".CosSiteHomeMain);
		}
	}
	else {
		CreateHtmlFile($_SERVER['REQUEST_URI'],$buffer.COSMETA );
	}
		
	return $buffer;
}
sae_debug("cos-html-cache.php, succeed to define cos_cache_ob_callback");
function cos_cache_shutdown_callback(){
	ob_end_flush();
	flush();
}
sae_debug("cos-html-cache.php, succeed to define cos_cache_shutdown_callback");
if( !function_exists('DelCacheByUrl') ){
	function DelCacheByUrl($url) {
		$kv=new SaeKV();
		$kv->init();
		if ($kv->get($url)) {
			$kv->delete($url);
			sae_debug("DelCacheByUrl, succed to delete cache, key=".$url);
		}
		else {
			sae_debug("DelCacheByUrl, no cache to delete, key=".$url);
		}
	}
	
	sae_debug("cos-html-cache.php, succeed to define DelCacheByUrl");
}

if( !function_exists('htmlCacheDel') ){
	// create single html
	function htmlCacheDel($post_ID) {
		if( $post_ID == "" ) {
			return true;
		}
		$uri = get_permalink($post_ID);
		DelCacheByUrl($uri );
	}
	sae_debug("cos-html-cache.php, succeed to define htmlCacheDel");
}

if( !function_exists('htmlCacheDelNb') ){
	// delete nabour posts
	function htmlCacheDelNb($post_ID) {
		if( $post_ID == "" ) {
			return true;
		}

		$uri = get_permalink($post_ID);
		DelCacheByUrl($uri );
		global $wpdb;
		$postRes=$wpdb->get_results("SELECT `ID`  FROM `" . $wpdb->posts . "` WHERE post_status = 'publish'   AND   post_type='post'   AND  ID < ".$post_ID." ORDER BY ID DESC LIMIT 0,1;");
		$uri1 = get_permalink($postRes[0]->ID);
		DelCacheByUrl($uri1 );
		$postRes=$wpdb->get_results("SELECT `ID`  FROM `" . $wpdb->posts . "` WHERE post_status = 'publish'  AND   post_type='post'    AND ID > ".$post_ID."  ORDER BY ID ASC  LIMIT 0,1;");
		if( $postRes[0]->ID != '' ){
			  $uri2  = get_permalink($postRes[0]->ID);
			  DelCacheByUrl($uri2 );
		}
	}
	
	sae_debug("cos-html-cache.php, succeed to define htmlCacheDelNb");
}

//create index.html
if( !function_exists('createIndexHTML') ){
	function createIndexHTML($post_ID){
		if( $post_ID == "" ) {
			return true;
		}
		$kv=new SaeKV();
		$kv->init();
        if ($kv->get(CosSiteHomeMain)) {
	        $kv->delete(CosSiteHomeMain);
	        sae_debug("createIndexHTML, succeed to delete cache, key=".CosSiteHomeMain);
        }
        else {
        	sae_debug("createIndexHTML, no cache to delete, key=".CosSiteHomeMain);
        }
	}
	sae_debug("cos-html-cache.php, succeed to define createIndexHTML");
}

if(!function_exists("htmlCacheDel_reg_admin")) {
	/**
	* Add the options page in the admin menu
	*/
	function htmlCacheDel_reg_admin() {
		if (function_exists('add_options_page')) {
			add_options_page('html-cache-creator', 'CosHtmlCache',8, basename(__FILE__), 'cosHtmlOption');
			//add_options_page($page_title, $menu_title, $access_level, $file).
		}
	}
	sae_debug("cos-html-cache.php, succeed to define htmlCacheDel_reg_admin");
}

add_action('admin_menu', 'htmlCacheDel_reg_admin');
sae_debug("cos-html-cache.php, succeed to add action htmlCacheDel_reg_admin");
if(!function_exists("cosHtmlOption")) {
function cosHtmlOption(){
	do_cos_html_cache_action();
?>
	<div class="wrap" style="padding:10px 0 0 10px;text-align:left">
	<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
	<p>
	<?php _e("Click the button bellow to delete all the html cache files","cosbeta");?></p>
	<p><?php _e("Note:this will Not  delete data from your databases","cosbeta");?></p>
	<p><?php _e("If you want to rebuild all cache files, you should delete them first,and then the cache files will be built when post or page first visited","cosbeta");?></p>

	<p><b><?php _e("specify a post ID or Title to to delete the related cache file","cosbeta");?></b> <input type="text" id="cache_id" name="cache_id" value="" /> <?php _e("Leave blank if you want to delete all caches","cosbeta");?></p>
	<p><input type="submit" value="<?php _e("Delete Html Cache files","cosbeta");?>" id="htmlCacheDelbt" name="htmlCacheDelbt" onClick="return checkcacheinput(); " />
	</form>
	</div>

	<SCRIPT LANGUAGE="JavaScript">
	<!--
		function checkcacheinput(){
		document.getElementById('htmlCacheDelbt').value = 'Please Wait...';
		return true;
	}
	//-->
	</SCRIPT>
<?php
	}
	
	sae_debug("cos-html-cache.php, succeed to define cosHtmlOption");
}
/*
end of get url
*/
// deal with rebuild or delete
function do_cos_html_cache_action(){
	if ($_POST['indexHtmlCacheDelbt']) {
		$kv=new SaeKV();
		$kv->init();
        $ret=$kv->get(CosSiteHomeMain);
        if ($ret) {
	        if($kv->delete(CosSiteHomeMain)){
				$msg = __('Index Caches were deleted successfully','cosbeta');
				sae_debug("do_cos_html_cache_action 1, succeed to to delete index page, key=".CosSiteHomeMain);
	        }else{
				sae_debug("do_cos_html_cache_action 1, fail to to delete index page, key=".CosSiteHomeMain);
	        }
        }else{
			sae_debug("do_cos_html_cache_action 1, no index page to delete, key=".CosSiteHomeMain);
        }
	}
	if( !empty($_POST['htmlCacheDelbt']) ){
        $kv=new SaeKV();
		$kv->init();
        $ret=$kv->get(CosSiteHomeMain);
        if ($ret) {
	        $kv->delete(CosSiteHomeMain);
	        sae_debug("do_cos_html_cache_action 2, succeed to to delete index page, key=".CosSiteHomeMain);
        }
		global $wpdb;
		if( $_POST['cache_id'] * 1 > 0 ){
			//delete cache by id
			 DelCacheByUrl(get_permalink($_POST['cache_id']));
			 $msg = __('the post cache was deleted successfully: ID=','cosbeta').$_POST['cache_id'];
			 sae_debug("do_cos_html_cache_action 3, succeed to to delete cache, key=".$_POST['cache_id']);
		}
		else if( strlen($_POST['cache_id']) > 2  ){
			$postRes=$wpdb->get_results("SELECT `ID`  FROM `" . $wpdb->posts . "` WHERE post_title like '%".$_POST['cache_id']."%' LIMIT 0,1 ");
			DelCacheByUrl( get_permalink( $postRes[0]->ID ) );
			$msg = __('the post cache was deleted successfully: Title=','cosbeta').$_POST['cache_id'];
			sae_debug("do_cos_html_cache_action 4, succeed to to delete cache, key=".$_POST['cache_id']);
		}
		else{
			$postRes=$wpdb->get_results("SELECT `ID`  FROM `" . $wpdb->posts . "` WHERE post_status = 'publish' AND ( post_type='post' OR  post_type='page' )  ORDER BY post_modified DESC ");
			foreach($postRes as $post) {
				DelCacheByUrl(get_permalink($post->ID));
			}
			$msg = __('HTML Caches were deleted successfully','cosbeta');
			sae_debug("do_cos_html_cache_action 5, succeed to to delete cache");
		}
	}
	if($msg) {
		echo '<div class="updated"><strong><p>'.$msg.'</p></strong></div>';
	}
}

sae_debug("cos-html-cache.php, succeed to define do_cos_html_cache_action");
$is_add_comment_is = true;
/*
 * with ajax comments
 */
if ( !function_exists("cos_comments_js") ){
	function cos_comments_js($postID){
		global $is_add_comment_is;
		if( $is_add_comment_is ){
			$is_add_comment_is = false;
		?>
		<script language="JavaScript" type="text/javascript" src="<?php echo CosSiteHome;?>/wp-content/plugins/cos-html-cache/common.js.php?hash=<?php echo COOKIEHASH;?>"></script>
		<script language="JavaScript" type="text/javascript">
		//<![CDATA[
		var hash = "<?php echo COOKIEHASH;?>";
		var author_cookie = "comment_author_" + hash;
		var email_cookie = "comment_author_email_" + hash;
		var url_cookie = "comment_author_url_" + hash; 
		var adminmail = "<?php  echo str_replace('@','{_}',get_option('admin_email'));?>";
		var adminurl = "<?php  echo  get_option('siteurl') ;?>";
		setCommForm();
		//]]>
		</script>
	<?php
		}
	}
	
	sae_debug("cos-html-cache.php, succeed to define cos_comments_js");
}

function CosSafeTag(){
	if   ( is_single() || (is_home() && IS_INDEX) )  {
		echo "<!--cos-html-cache-safe-tag-->";
	}
}
function clearCommentHistory(){
global $comment_author_url,$comment_author_email,$comment_author;
$comment_author_url='';
$comment_author_email='';
$comment_author='';
}
//add_action('comments_array','clearCommentHistory');
add_action('get_footer', 'CosSafeTag');
add_action('comment_form', 'cos_comments_js');

/* end of ajaxcomments*/
add_action('publish_post', 'htmlCacheDelNb');
add_action('delete_post', 'htmlCacheDelNb');
add_action('edit_post', 'htmlCacheDel');

if(IS_INDEX){
	add_action('edit_post', 'createIndexHTML');
	add_action('delete_post', 'createIndexHTML');
	add_action('publish_post', 'createIndexHTML');
}
sae_debug("cos-html-cache.php, succeed to add actions");
?>
