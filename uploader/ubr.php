<?php
/*
	ubr.php
	Defined:
		buildUberUploadBody() - return the form and divs used by UberUploader
		buildUberUploadHead() - return <script> and other tags to be placed in <head>
*/
include_once(dirname(__FILE__).'/../common.php');
global $PAGE;
$PAGE->finish();
global $_INI, $_CONFIG;
require_once dirname(__FILE__).'/ubr_ini.php';
require_once dirname(__FILE__).'/ubr_lib.php';
require_once dirname(__FILE__).'/ubr_default_config.php'; //$_INI['default_config'];
require_once dirname(__FILE__).'/ubr_finished_lib.php';

function buildUberUploaderBody()
{
	$uploadDir = 'uploader/files/';
	global $_INI, $_CONFIG;
	$template = new SimpleTemplate();
	?>
		<div>
			<div id="ubr_alert"></div>

			<!-- Progress Bar -->
			<div id="progress_bar_container">
				<div id="upload_stats_toggle">&nbsp;</div>
				<div id="progress_bar_background">
					<div id="progress_bar"></div>
				</div>
				<div id="percent_complete">&nbsp;</div>
			</div>
			<br clear="all" />

			<!-- Upload Stats -->
			<?php if($_CONFIG['show_files_uploaded'] || $_CONFIG['show_current_position'] || $_CONFIG['show_elapsed_time'] || $_CONFIG['show_est_time_left'] || $_CONFIG['show_est_speed']){ ?>
				<div id="upload_stats_container">
					<?php if($_CONFIG['show_files_uploaded']){ ?>
					<div class='upload_stats_label'>&nbsp;Załadowanych plików:</div>
					<div class='upload_stats_data'><span id="files_uploaded">0</span> z <span id="total_uploads">0</span></div>
					<?php }if($_CONFIG['show_current_position']){ ?>
					<div class='upload_stats_label'>&nbsp;Postęp:</div>
					<div class='upload_stats_data'><span id="current_position">0</span> / <span id="total_kbytes">0</span> KBytes</div>
					<?php }if($_INI['cgi_upload_hook'] && $_CONFIG['show_current_file']){ ?>
					<div class='upload_stats_label'>&nbsp;Obecnie ładowany plik:</div>
					<div class='upload_stats_data'><span id="current_file"></span></div>
					<?php }if($_CONFIG['show_elapsed_time']){ ?>
					<div class='upload_stats_label'>&nbsp;Minęło:</div>
					<div class='upload_stats_data'><span id="elapsed_time">0</span></div>
					<?php }if($_CONFIG['show_est_time_left']){ ?>
					<div class='upload_stats_label'>&nbsp;Pozostało:</div>
					<div class='upload_stats_data'><span id="est_time_left">0</span></div>
					<?php }if($_CONFIG['show_est_speed']){ ?>
					<div class='upload_stats_label'>&nbsp;Prędkość:</div>
					<div class='upload_stats_data'><span id="est_speed">0</span> KB/s.</div>
					<?php } ?>
				</div>
				<br clear="all" />
			<?php } ?>

			<!-- Container for upload iframe -->
			<div id="upload_container"></div>

			<!-- Start Upload Form -->
			<form id="ubr_upload_form" name="ubr_upload_form" method="post" enctype="multipart/form-data" onSubmit="return UberUpload.linkUpload();">
				<input type="hidden" name="myuploaddir" value="<?php echo $uploadDir; ?>" />
				<noscript><span class="ubrError">Błąd</span>: Javascript musi być włączony by załączać pliki.<br><br></noscript>
				<div id="file_picker_container"></div>
				<div id="upload_slots_container"></div>
				<div id="upload_form_values_container">
				<!-- Add Your Form Values Here -->
				</div>
				<div id="upload_buttons_container">
				<!--<input type="button" id="reset_button" name="reset_button" value="Reset">&nbsp;&nbsp;&nbsp;--><input type="submit" id="upload_button" name="upload_button" value="Załaduj"></div>
			</form>
		</div>
	<?php
	return  $template->finish();
}	
	
function buildUberUploadHead($path_to_redirect)
{		
	global $_INI, $_CONFIG;
	$params = $_INI + $_CONFIG;
	$params['path_to_css_file'] = '../../../uploader/'. $params['path_to_css_file'];
	$params['path_to_jquery'] = '../../../uploader/'. $params['path_to_jquery'];
	$params['path_to_block_ui'] = '../../../uploader/'. $params['path_to_block_ui'];
	$params['path_to_js_script'] = '../../../uploader/'. $params['path_to_js_script'];
	$params['path_to_link_script'] = '../../../uploader/'. $params['path_to_link_script'];
	$params['path_to_set_progress_script'] = '../../../uploader/'. $params['path_to_set_progress_script'];
	$params['path_to_get_progress_script'] = '../../../uploader/'. $params['path_to_get_progress_script'];
	//$params['path_to_upload_script'] = '../../../uploader/'. $params['path_to_upload_script'];
	$template = new SimpleTemplate($params);
	?>
		
		<!-- Please do not remove this tag: Uber-Uploader Ver 6.8.1 http://uber-uploader.sourceforge.net -->
		<link rel="stylesheet" type="text/css" href="%path_to_css_file%">
		<script language="JavaScript" type="text/JavaScript" src="%path_to_jquery%"></script>
		<?php if($_INI['block_ui_enabled']){ ?><script language="JavaScript" type="text/JavaScript" src="%path_to_block_ui%"></script><?php } ?>
		<script language="javascript" type="text/javascript" src="%path_to_js_script%"></script>
		<script language="javascript" type="text/javascript">
			var JQ = jQuery.noConflict();

			UberUpload.path_to_link_script = "%path_to_link_script%";
			UberUpload.path_to_set_progress_script = "%path_to_set_progress_script%";
			UberUpload.path_to_get_progress_script = "%path_to_get_progress_script%";
			UberUpload.path_to_upload_script = "%path_to_upload_script%";
			UberUpload.check_allow_extensions_on_client = %check_allow_extensions_on_client%;
			UberUpload.check_disallow_extensions_on_client = %check_disallow_extensions_on_client%;
			<?php if($_CONFIG['check_allow_extensions_on_client']){ print "UberUpload.allow_extensions = /" . $_CONFIG['allow_extensions'] . "$/i;\n"; } ?>
			<?php if($_CONFIG['check_disallow_extensions_on_client']){ print "UberUpload.disallow_extensions = /" . $_CONFIG['disallow_extensions'] . "$/i;\n"; } ?>
			UberUpload.check_file_name_format = %check_file_name_format%;
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.check_file_name_regex = /" . $_CONFIG['check_file_name_regex'] . "/;\n"; } ?>
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.check_file_name_error_message = '" . $_CONFIG['check_file_name_error_message'] . "';\n"; } ?>
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.max_file_name_chars = " . $_CONFIG['max_file_name_chars'] . ";\n"; } ?>
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.min_file_name_chars = " . $_CONFIG['min_file_name_chars'] . ";\n"; } ?>
			UberUpload.check_null_file_count = %check_null_file_count%;
			UberUpload.check_duplicate_file_count = %check_duplicate_file_count%;
			UberUpload.max_upload_slots = %max_upload_slots%;
			UberUpload.cedric_progress_bar = %cedric_progress_bar%;
			UberUpload.cedric_hold_to_sync = %cedric_hold_to_sync%;
			UberUpload.bucket_progress_bar = %bucket_progress_bar%;
			UberUpload.progress_bar_width = %progress_bar_width%;
			UberUpload.show_percent_complete = %show_percent_complete%;
			UberUpload.block_ui_enabled = %block_ui_enabled%;
			UberUpload.show_files_uploaded = %show_files_uploaded%;
			UberUpload.show_current_position = %show_current_position%;
			UberUpload.show_current_file = <?php if($_INI['cgi_upload_hook'] && $_CONFIG['show_current_file']){ print "1"; }else{ print "0"; } ?>;
			UberUpload.show_elapsed_time = %show_elapsed_time%;
			UberUpload.show_est_time_left = %show_est_time_left%;
			UberUpload.show_est_speed = %show_est_speed%;
			UberUpload.path_to_redirect = '<?php print urlencode('http://'. $_SERVER['HTTP_HOST'] . $path_to_redirect); ?>';

			JQ(document).ready(function(){
				UberUpload.resetFileUploadPage();
				JQ("#reset_button").bind("click", function(e){ UberUpload.resetFileUploadPage(); });
				JQ("#progress_bar_background").css("width", UberUpload.progress_bar_width);

				if(UberUpload.show_files_uploaded || UberUpload.show_current_position || UberUpload.show_elapsed_time || UberUpload.show_est_time_left || UberUpload.show_est_speed){
					JQ("#upload_stats_toggle").bind("click", function(e){ UberUpload.toggleUploadStats(); });
					JQ("#upload_stats_toggle").html(" ");
					JQ("#upload_stats_toggle").attr("title", "Pokaż statystyki");
				}
			});
		</script>	
	<?php
	return  $template->finish();
}

function getUploadInfo()
{
	global $TEMP_DIR, $USER;
	if (!isset($_GET['upload_id']) || !preg_match("/^[a-zA-Z0-9]{32}$/", $_GET['upload_id']))
		return false;
	$UPLOAD_ID = $_GET['upload_id'];
	$xml_parser = new XML_Parser;                                  // XML parser
	$xml_parser->setXMLFile($TEMP_DIR, $_GET['upload_id']);        // Set upload_id.redirect file
	$xml_parser->setXMLFileDelete($_INI['delete_redirect_file']);  // Delete upload_id.redirect file when finished parsing
	$xml_parser->parseFeed();                                      // Parse upload_id.redirect file
	// Display message if the XML parser encountered an error
	if ($xml_parser->getError())  throw new KnownException($xml_parser->getErrorMsg());
	
	$_XML_DATA = $xml_parser->getXMLData();                        // Get xml data from the xml parser
	$_CONFIG_DATA = getConfigData($_XML_DATA);                     // Get config data from the xml data
	$_POST_DATA  = getPostData($_XML_DATA);                        // Get post data from the xml data
	$_FILE_DATA = getFileData($_XML_DATA);                         // Get file data from the xml data
	
	$files = array();
	foreach ($_FILE_DATA as $slot => $fileinfo)
	{	
		$file = get_object_vars($fileinfo);
		$file['realname'] = $_POST_DATA[$slot];
		$files[] = $file;
		db_insert('uploads',array(
			'filename' => $file['name'],
			'realname' => $file['realname'],
			'size' => $file['size'],
			'filename' => $file['name'],
			'uploader' => $USER['uid'],
			'utime' => time()
		));
	}
	//POST {"myuploaddir":"uploader\/files\/","upfile_1273473433362":"shortkehrli.txt"}
	//FILE {"upfile_1273473364588":{"slot":"upfile_1273473364588","name":"shortkehrli.txt","size":"24714","type":"text\/plain","status":"1","status_desc":"OK"}}
	
	return $files;
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'kB', 'MB', 'GB', 'TB'); 
   
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
   
    $bytes /= pow(1024, $pow); 
   
    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 
