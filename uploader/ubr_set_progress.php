<?php
//******************************************************************************************************
//	Name: ubr_set_progress.php
//	Revision: 3.1
//	Date: 10:07 PM October 15, 2009
//	Link: http://uber-uploader.sourceforge.net
//	Developer: Peter Schmandra
//	Description: Initialize the progress bar
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
//	1. ?upload_id=32_character_alpha_numeric_string
//	2. ?about
//***************************************************************************************************************

$THIS_VERSION    = '3.1';        // Version of this file
$UPLOAD_ID = '';                 // Initialize upload id

require_once 'ubr_ini.php';
require_once 'ubr_lib.php';

if($_INI['php_error_reporting']){ error_reporting(E_ALL); }

if(isset($_GET['upload_id']) && preg_match("/^[a-zA-Z0-9]{32}$/", $_GET['upload_id'])){ $UPLOAD_ID = $_GET['upload_id']; }
elseif(isset($_GET['about'])){ kak("<u><b>UBER UPLOADER SET PROGRESS</b></u><br>UBER UPLOADER VERSION =  <b>" . $_INI['uber_version'] . "</b><br>UBR_SET_PROGRESS = <b>" . $THIS_VERSION . "<b><br>\n", 1, __LINE__, $_INI['path_to_css_file']); }
else{ kak("<span class='ubrError'>ERROR</span>: Invalid parameters passed<br>", 1, __LINE__, $_INI['path_to_css_file']); }

$flength_file = $TEMP_DIR . $UPLOAD_ID . '.dir/' . $UPLOAD_ID . '.flength';
$hook_file = $TEMP_DIR . $UPLOAD_ID . '.dir/' . $UPLOAD_ID . '.hook';
$found_flength_file = false;
$found_hook_file = false;

// Keep trying to read the flength file until timeout
for($i = 0; $i < $_INI['flength_timeout_limit']; $i++){
	if($total_upload_size = readUbrFile($flength_file, $_INI['debug_ajax'])){
		$found_flength_file = true;
		$start_time = time();
		break;
	}

	clearstatcache();
	sleep(1);
}

// Failed to find the flength file in the alloted time
if(!$found_flength_file){
	if($_INI['debug_ajax']){ showDebugMessage("Failed to find flength file $flength_file"); }
	showAlertMessage("<span class='ubrError'>ERROR</span>: Failed to find <a href='http://uber-uploader.sourceforge.net/?section=flength' target='_new'>flength file</a>", 1);
}
elseif(strstr($total_upload_size, "ERROR")){
	// Found the flength file but it contains an error
	list($error, $error_msg) = explode($DATA_DELIMITER, $total_upload_size);

	if($_INI['debug_ajax']){ showDebugMessage($error_msg); }

	if(!deleteDir($TEMP_DIR . $UPLOAD_ID . '.dir')){
		if($_INI['debug_ajax']){ showDebugMessage("Failed to delete " . $TEMP_DIR . $UPLOAD_ID . ".dir"); }
	}

	stopUpload();
	showAlertMessage("<span class='ubrError'>ERROR</span>: " . $error_msg, 1);
}
else{
	// Keep trying to read the hook file until timeout
	if($_INI['cgi_upload_hook']){
		for($i = 0; $i < $_INI['hook_timeout_limit']; $i++){
			if($hook_data = readUbrFile($hook_file, $_INI['debug_ajax'])){
				$found_hook_file = true;
				break;
			}

			clearstatcache();
			sleep(1);
		}
	}

	// Failed to find the hook file in the alloted time
	if($_INI['cgi_upload_hook'] && !$found_hook_file){
		if($_INI['debug_ajax']){ showDebugMessage("Failed to find hook file $hook_file"); }
		showAlertMessage("<span class='ubrError'>ERROR</span>: Failed to find hook file", 1);
	}

	if($_INI['debug_ajax']){
		showDebugMessage("Found flength file $flength_file");
		if($_INI['cgi_upload_hook']){ showDebugMessage("Found hook file $hook_file"); }
	}

	startProgressBar($UPLOAD_ID, $total_upload_size, $start_time);
}

?>