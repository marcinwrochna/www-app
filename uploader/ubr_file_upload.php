<?php
//******************************************************************************************************
//	Name: ubr_file_upload.php
//	Revision: 3.3
//	Date: 9:59 PM October 8, 2009
//	Link: http://uber-uploader.sourceforge.net
//	Developer: Peter Schmandra
//	Description: Select and submit upload files.
//
//	Copyright (C) 2009  Peter Schmandra
//
//	This file is part of Uber-Uploader.
//
//	Uber-Uploader is free software: you can redistribute it and/or modify
//	it under the terms of the GNU General Public License as published by
//	the Free Software Foundation, either version 3 of the License, or
//	(at your option) any later version.
//
//	Uber-Uploader is distributed in the hope that it will be useful,
//	but WITHOUT ANY WARRANTY; without even the implied warranty of
//	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
//	GNU General Public License for more details.
//
//	You should have received a copy of the GNU General Public License
//	along with Uber-Uploader. If not, see http://www.gnu.org/licenses/.
//
//***************************************************************************************************************

//***************************************************************************************************************
//	The following possible query string formats are assumed
//
//	1. No query string
//	2. ?about
//***************************************************************************************************************

$THIS_VERSION = '3.3';        // Version of this file

require_once 'uploader/ubr_ini.php';
require_once 'uploader/ubr_lib.php';

if($_INI['php_error_reporting']){ error_reporting(E_ALL); }

//Set config file
if($_INI['multi_configs_enabled']){
	//////////////////////////////////////////////////////////////////////////////
	//	ATTENTION
	//
	//	Put your multi config file code here. eg
	//
	//	if($_SESSION['user_name'] == 'TOM'){ $config_file = 'tom_config.php'; }
	//	if($_COOKIE['user_name'] == 'TOM'){ $config_file = 'tom_config.php'; }
	//////////////////////////////////////////////////////////////////////////////
}
else{ $config_file = $_INI['default_config']; }

// Load config file
require_once 'uploader/'. $config_file;

if($_INI['debug_php']){ phpinfo(); exit(); }
elseif($_INI['debug_config']){ debug($_CONFIG['config_file_name'], $_CONFIG); exit(); }
elseif(isset($_GET['about'])){
	kak("<u><b>UBER UPLOADER FILE UPLOAD</b></u><br>UBER UPLOADER VERSION =  <b>" . $_INI['uber_version'] . "</b><br>UBR_FILE_UPLOAD = <b>" . $THIS_VERSION . "</b><br>\n", 1, __LINE__, $_INI['path_to_css_file']);
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>Uber-Uploader - Free File Upload Progress Bar</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8">
		<meta http-equiv="expires" content="-1">
		<meta name="robots" content="index,nofollow">
		<!-- Please do not remove this tag: Uber-Uploader Ver 6.8.1 http://uber-uploader.sourceforge.net -->
		<link rel="stylesheet" type="text/css" href="uploader/<?php print $_INI['path_to_css_file']; ?>">
		<script language="JavaScript" type="text/JavaScript" src="uploader/<?php print $_INI['path_to_jquery']; ?>"></script>
		<?php if($_INI['block_ui_enabled']){ ?><script language="JavaScript" type="text/JavaScript" src="uploader/<?php print $_INI['path_to_block_ui']; ?>"></script><?php } ?>
		<script language="javascript" type="text/javascript" src="uploader/<?php print $_INI['path_to_js_script']; ?>"></script>
		<script language="javascript" type="text/javascript">
			var JQ = jQuery.noConflict();

			UberUpload.path_to_link_script = "uploader/<?php print $_INI['path_to_link_script']; ?>";
			UberUpload.path_to_set_progress_script = "uploader/<?php print $_INI['path_to_set_progress_script']; ?>";
			UberUpload.path_to_get_progress_script = "uploader/<?php print $_INI['path_to_get_progress_script']; ?>";
			UberUpload.path_to_upload_script = "<?php print $_INI['path_to_upload_script']; ?>";
			UberUpload.check_allow_extensions_on_client = <?php print $_CONFIG['check_allow_extensions_on_client']; ?>;
			UberUpload.check_disallow_extensions_on_client = <?php print $_CONFIG['check_disallow_extensions_on_client']; ?>;
			<?php if($_CONFIG['check_allow_extensions_on_client']){ print "UberUpload.allow_extensions = /" . $_CONFIG['allow_extensions'] . "$/i;\n"; } ?>
			<?php if($_CONFIG['check_disallow_extensions_on_client']){ print "UberUpload.disallow_extensions = /" . $_CONFIG['disallow_extensions'] . "$/i;\n"; } ?>
			UberUpload.check_file_name_format = <?php print $_CONFIG['check_file_name_format']; ?>;
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.check_file_name_regex = /" . $_CONFIG['check_file_name_regex'] . "/;\n"; } ?>
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.check_file_name_error_message = '" . $_CONFIG['check_file_name_error_message'] . "';\n"; } ?>
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.max_file_name_chars = " . $_CONFIG['max_file_name_chars'] . ";\n"; } ?>
			<?php if($_CONFIG['check_file_name_format']){ print "UberUpload.min_file_name_chars = " . $_CONFIG['min_file_name_chars'] . ";\n"; } ?>
			UberUpload.check_null_file_count = <?php print $_CONFIG['check_null_file_count']; ?>;
			UberUpload.check_duplicate_file_count = <?php print $_CONFIG['check_duplicate_file_count']; ?>;
			UberUpload.max_upload_slots = <?php print $_CONFIG['max_upload_slots']; ?>;
			UberUpload.cedric_progress_bar = <?php print $_CONFIG['cedric_progress_bar']; ?>;
			UberUpload.cedric_hold_to_sync = <?php print $_CONFIG['cedric_hold_to_sync']; ?>;
			UberUpload.bucket_progress_bar = <?php print $_CONFIG['bucket_progress_bar']; ?>;
			UberUpload.progress_bar_width = <?php print $_INI['progress_bar_width']; ?>;
			UberUpload.show_percent_complete = <?php print $_CONFIG['show_percent_complete']; ?>;
			UberUpload.block_ui_enabled = <?php print $_INI['block_ui_enabled']; ?>;
			UberUpload.show_files_uploaded = <?php print $_CONFIG['show_files_uploaded']; ?>;
			UberUpload.show_current_position = <?php print $_CONFIG['show_current_position']; ?>;
			UberUpload.show_current_file = <?php if($_INI['cgi_upload_hook'] && $_CONFIG['show_current_file']){ print "1"; }else{ print "0"; } ?>;
			UberUpload.show_elapsed_time = <?php print $_CONFIG['show_elapsed_time']; ?>;
			UberUpload.show_est_time_left = <?php print $_CONFIG['show_est_time_left']; ?>;
			UberUpload.show_est_speed = <?php print $_CONFIG['show_est_speed']; ?>;

			JQ(document).ready(function(){
				UberUpload.resetFileUploadPage();
				JQ("#reset_button").bind("click", function(e){ UberUpload.resetFileUploadPage(); });
				JQ("#progress_bar_background").css("width", UberUpload.progress_bar_width);

				if(UberUpload.show_files_uploaded || UberUpload.show_current_position || UberUpload.show_elapsed_time || UberUpload.show_est_time_left || UberUpload.show_est_speed){
					JQ("#upload_stats_toggle").bind("click", function(e){ UberUpload.toggleUploadStats(); });
					JQ("#upload_stats_toggle").html("[+]");
					JQ("#upload_stats_toggle").attr("title", "Toggle Upload Statistics");
				}
			});
		</script>
	</head>
	<body bgcolor="#EEEEEE">
		<div id="main_container">
			<?php if($_INI['debug_ajax']){ ?><div id='ubr_debug'></div><?php } ?>
			<div id="ubr_alert"></div>

			<!-- Progress Bar -->
			<div id="progress_bar_container">
				<div id="upload_stats_toggle">&nbsp;</div>
				<div id="progress_bar_background">
					<div id="progress_bar"></div>
				</div>
				<div id="percent_complete">&nbsp;</div>
			</div>

			<br clear="all">

			<!-- Upload Stats -->
			<?php if($_CONFIG['show_files_uploaded'] || $_CONFIG['show_current_position'] || $_CONFIG['show_elapsed_time'] || $_CONFIG['show_est_time_left'] || $_CONFIG['show_est_speed']){ ?>
				<div id="upload_stats_container">
					<?php if($_CONFIG['show_files_uploaded']){ ?>
					<div class='upload_stats_label'>&nbsp;Files Uploaded:</div>
					<div class='upload_stats_data'><span id="files_uploaded">0</span> of <span id="total_uploads">0</span></div>
					<?php }if($_CONFIG['show_current_position']){ ?>
					<div class='upload_stats_label'>&nbsp;Current Position:</div>
					<div class='upload_stats_data'><span id="current_position">0</span> / <span id="total_kbytes">0</span> KBytes</div>
					<?php }if($_INI['cgi_upload_hook'] && $_CONFIG['show_current_file']){ ?>
					<div class='upload_stats_label'>&nbsp;Current File Uploading:</div>
					<div class='upload_stats_data'><span id="current_file"></span></div>
					<?php }if($_CONFIG['show_elapsed_time']){ ?>
					<div class='upload_stats_label'>&nbsp;Elapsed Time:</div>
					<div class='upload_stats_data'><span id="elapsed_time">0</span></div>
					<?php }if($_CONFIG['show_est_time_left']){ ?>
					<div class='upload_stats_label'>&nbsp;Est Time Left:</div>
					<div class='upload_stats_data'><span id="est_time_left">0</span></div>
					<?php }if($_CONFIG['show_est_speed']){ ?>
					<div class='upload_stats_label'>&nbsp;Est Speed:</div>
					<div class='upload_stats_data'><span id="est_speed">0</span> KB/s.</div>
					<?php } ?>
				</div>
				<br clear="all">
			<?php } ?>

			<!-- Container for upload iframe -->
			<div id="upload_container"></div>

			<!-- Start Upload Form -->
			<form id="ubr_upload_form" name="ubr_upload_form" method="post" enctype="multipart/form-data" onSubmit="return UberUpload.linkUpload();">
				<noscript><span class="ubrError">ERROR</span>: Javascript must be enabled to use Uber-Uploader.<br><br></noscript>
				<div id="file_picker_container"></div>
				<div id="upload_slots_container"></div>
				<div id="upload_form_values_container">
				<!-- Add Your Form Values Here -->
				</div>
				<div id="upload_buttons_container"><input type="button" id="reset_button" name="reset_button" value="Reset">&nbsp;&nbsp;&nbsp;<input type="submit" id="upload_button" name="upload_button" value="Upload"></div>
			</form>
		</div>
		<br clear="all">
	</body>
</html>
