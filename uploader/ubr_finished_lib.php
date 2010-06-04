<?php
//******************************************************************************************************
//	Name: ubr_finished_lib.php
//	Revision: 3.3
//	Date: 9:25 PM July 24, 2009
//	Link: http://uber-uploader.sourceforge.net
//	Developer: Peter Schmandra
//	Description: Library for uu_finished.php
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
//*******************************************************************************************************

///////////////////////////////////////////////////////////////////////////////
//	Get/Set/Store uploaded file slot name, file name, file size and file type
///////////////////////////////////////////////////////////////////////////////
class FileInfo{
	var $slot = '';
	var $name = '';
	var $size = 0;
	var $type = '';
	var $status = '';
	var $status_desc = '';

	function getFileInfo($key){
		if(strcasecmp($key, 'slot') == 0){ return $this->slot; }
		elseif(strcasecmp($key, 'name') == 0){ return $this->name; }
		elseif(strcasecmp($key, 'size') == 0){ return $this->size; }
		elseif(strcasecmp($key, 'type') == 0){ return $this->type; }
		elseif(strcasecmp($key, 'status') == 0){ return $this->status; }
		elseif(strcasecmp($key, 'status_desc') == 0){ return $this->status_desc; }
		else{ print "Error: Invalid get member $key value on FileInfo object<br>\n"; }
	}

	function setFileInfo($key, $value){
		if(strcasecmp($key, 'slot') == 0){ $this->slot = $value; }
		elseif(strcasecmp($key, 'name') == 0){ $this->name = $value; }
		elseif(strcasecmp($key, 'size') == 0){ $this->size = $value; }
		elseif(strcasecmp($key, 'type') == 0){ $this->type = $value; }
		elseif(strcasecmp($key, 'status') == 0){ $this->status = $value; }
		elseif(strcasecmp($key, 'status_desc') == 0){ $this->status_desc = $value; }
		else{ print "Error: Invalid set member $key value on FileInfo object<br>\n"; }
	}
}

///////////////////////////////////////////////////////////////////////
//	XML Parser
//	Contributor: http://www.php.net/manual/en/function.xml-parse.php
///////////////////////////////////////////////////////////////////////
class XML_Parser{
	var $XML_Parser;
	var $error_msg = '';
	var $delete_xml_file = 1;
	var $in_error = 0;
	var $xml_file = '';
	var $raw_xml_data = '';
	var $xml_post_data = '';
	var $xml_data = array();
	var $upload_id = '';

	function setXMLFileDelete($delete_xml_file){ $this->delete_xml_file = $delete_xml_file; }
	function setXMLFile($temp_dir, $upload_id){
		$this->xml_file = $temp_dir . $upload_id . ".redirect";
		$this->upload_id = $upload_id;
	}
	function getError(){ return $this->in_error; }
	function getErrorMsg(){ return $this->error_msg; }
	function getRawXMLData(){ return $this->raw_xml_data; }
	function getXMLData(){ return $this->xml_data; }

	function startHandler($parser, $name, $attribs){
		$_content = array('name' => $name);

		if(!empty($attribs)){ $_content['attrs'] = $attribs; }

		array_push($this->xml_data, $_content);
	}

	function dataHandler($parser, $data){
		if(count(trim($data))){
			$_data_idx = count($this->xml_data) - 1;

			if(!isset($this->xml_data[$_data_idx]['content'])){ $this->xml_data[$_data_idx]['content'] = ''; }

			$this->xml_data[$_data_idx]['content'] .= $data;
		}
	}

	function endHandler($parser, $name){
		if(count($this->xml_data) > 1){
			$_data = array_pop($this->xml_data);
			$_data_idx = count($this->xml_data) - 1;
			$this->xml_data[$_data_idx]['child'][] = $_data;
		}
	}

	function parseFeed(){
		// read the upload_id.redirect file
		if($this->xml_post_data = readUbrFile($this->xml_file)){
			// store the raw xml file
			$this->raw_xml_data = $this->xml_post_data;

			// format the xml data into 1 long string
			$this->xml_post_data = preg_replace('/\>(\n|\r|\r\n| |\t)*\</','><', $this->xml_post_data);

			// create the xml parser
			$this->XML_Parser = xml_parser_create('');

			// set xml parser options
			xml_set_object($this->XML_Parser, $this);
			xml_parser_set_option($this->XML_Parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($this->XML_Parser, "startHandler", "endHandler");
			xml_set_character_data_handler($this->XML_Parser, "dataHandler");

			// parse upload_id.redirect file
			if(!xml_parse($this->XML_Parser, $this->xml_post_data)){
				$this->in_error = true;
				$this->error_msg = sprintf("<span class='ubrError'>XML ERROR</span>: %s at line %d", xml_error_string(xml_get_error_code($this->XML_Parser)), xml_get_current_line_number($this->XML_Parser));
			}

			xml_parser_free($this->XML_Parser);

			// delete upload_id.redirect file
			if($this->delete_xml_file){
				for($i = 0; $i < 3; $i++){
					if(@unlink($this->xml_file)){ break; }
					else{ sleep(1); }
				}
			}
		}
		else{
			$this->in_error = true;
			$this->error_msg = "<span class='ubrError'>ERROR</span>: Failed to open redirect file " . $this->upload_id . ".redirect";
		}
	}
}

///////////////////////////////////////////
//	Parse config data out of the xml data
///////////////////////////////////////////
function getConfigData($_XML_DATA){
	$_config_data = array();

	if(isset($_XML_DATA[0]['child'][0]['child'])){
		$num_configs = count($_XML_DATA[0]['child'][0]['child']);

		//config data is assumed to be stored in $_XML_DATA[0]['child'][0]
		for($i = 0; $i < $num_configs; $i++){
			if(isset($_XML_DATA[0]['child'][0]['child'][$i]['name']) && isset($_XML_DATA[0]['child'][0]['child'][$i]['content'])){
				$_config_data[$_XML_DATA[0]['child'][0]['child'][$i]['name']] = $_XML_DATA[0]['child'][0]['child'][$i]['content'];
			}
		}
	}

	return $_config_data;
}

/////////////////////////////////////////
//	Parse post data out of the xml data
/////////////////////////////////////////
function getPostData($_XML_DATA){
	$_post_value = array();
	$_post_data = array();

	if(isset($_XML_DATA[0]['child'][1]['child'])){
		$num_posts = count($_XML_DATA[0]['child'][1]['child']);

		//post data is assumed to be stored in $_XML_DATA[0]['child'][1]
		for($i = 0; $i < $num_posts; $i++){
			if(isset($_XML_DATA[0]['child'][1]['child'][$i]['name']) ){
				if(isset($_XML_DATA[0]['child'][1]['child'][$i]['content'])){
					$_post_value[$_XML_DATA[0]['child'][1]['child'][$i]['name']][$i] = $_XML_DATA[0]['child'][1]['child'][$i]['content'];
				}
				else{
					$_post_value[$_XML_DATA[0]['child'][1]['child'][$i]['name']][$i] = '';
				}
			}
		}

		foreach($_post_value as $key => $value){
			if(count($_post_value[$key]) > 1){
				$j = 0;

				foreach($_post_value[$key] as $content){
					$_post_data[$key][$j] = $content;
					$j++;
				}
			}
			else{
				foreach($_post_value[$key] as $content){ $_post_data[$key] = $content; }
			}
		}
	}

	return $_post_data;
}

/////////////////////////////////////////
//	Parse file data out of the xml data
/////////////////////////////////////////
function getFileData($_XML_DATA){
	$_file_data = array();

	if(isset($_XML_DATA[0]['child'][2]['child'])){
		$num_files = count($_XML_DATA[0]['child'][2]['child']);

		//file data is assumed to be stored in $_XML_DATA[0]['child'][2]
		for($i = 0; $i < $num_files; $i++){
			$file_info = new FileInfo;

			// file slot name
			if(isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][0]['name']) && $_XML_DATA[0]['child'][2]['child'][$i]['child'][0]['name'] === 'slot' && isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][0]['content'])){
				$file_info->setFileInfo($_XML_DATA[0]['child'][2]['child'][$i]['child'][0]['name'], $_XML_DATA[0]['child'][2]['child'][$i]['child'][0]['content']);
			}

			// file name
			if(isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][1]['name']) && $_XML_DATA[0]['child'][2]['child'][$i]['child'][1]['name'] === 'name' && isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][1]['content'])){
				$file_info->setFileInfo($_XML_DATA[0]['child'][2]['child'][$i]['child'][1]['name'], $_XML_DATA[0]['child'][2]['child'][$i]['child'][1]['content']);
			}

			// file size
			if(isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][2]['name']) && $_XML_DATA[0]['child'][2]['child'][$i]['child'][2]['name'] === 'size'  && isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][2]['content'])){
				$file_info->setFileInfo($_XML_DATA[0]['child'][2]['child'][$i]['child'][2]['name'], $_XML_DATA[0]['child'][2]['child'][$i]['child'][2]['content']);
			}

			// file type
			if(isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][3]['name']) && $_XML_DATA[0]['child'][2]['child'][$i]['child'][3]['name'] === 'type' && isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][3]['content'])){
				$file_info->setFileInfo($_XML_DATA[0]['child'][2]['child'][$i]['child'][3]['name'], $_XML_DATA[0]['child'][2]['child'][$i]['child'][3]['content']);
			}

			// file transfer status
			if(isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][4]['name']) && $_XML_DATA[0]['child'][2]['child'][$i]['child'][4]['name'] === 'status' && isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][4]['content'])){
				$file_info->setFileInfo($_XML_DATA[0]['child'][2]['child'][$i]['child'][4]['name'], $_XML_DATA[0]['child'][2]['child'][$i]['child'][4]['content']);
			}

			// file transfer status description
			if(isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][5]['name']) && $_XML_DATA[0]['child'][2]['child'][$i]['child'][5]['name'] === 'status_desc' && isset($_XML_DATA[0]['child'][2]['child'][$i]['child'][5]['content'])){
				$file_info->setFileInfo($_XML_DATA[0]['child'][2]['child'][$i]['child'][5]['name'], $_XML_DATA[0]['child'][2]['child'][$i]['child'][5]['content']);
			}

			if(strlen($file_info->getFileInfo('slot')) > 0){ $_file_data[$file_info->slot] = $file_info; }
		}
	}

	return $_file_data;
}

//////////////////////////////////////////////////////////////////////
//	Send an email with the upload results.
//////////////////////////////////////////////////////////////////////
function emailUploadResults($_FILE_DATA, $_CONFIG_DATA, $_POST_DATA){
	$_FILE_DATA_EMAIL = getFileDataEmail($_FILE_DATA, $_CONFIG_DATA, $_POST_DATA);

	$headers = '';
	$message = '';

	if($_CONFIG_DATA['html_email_support']){
		$headers = 'Content-type: text/html; charset=utf-8; format=flowed' . "\r\n";
		$end_char = "<br>\n";
	}
	else{
		$headers = 'Content-type: text/plain; charset=utf-8; format=flowed' . "\r\n";
		$end_char = "\n";
	}

	// add config data to email
	$headers .= "From: " . $_CONFIG_DATA['from_email_address'] . "\r\n";
	$message .= "Upload ID: ". $_CONFIG_DATA['upload_id'] . $end_char;
	$message .= "Start Upload: ". date("M j, Y, g:i:s", $_CONFIG_DATA['start_upload']) . $end_char;
	$message .= "End Upload: ". date("M j, Y, g:i:s", $_CONFIG_DATA['end_upload']) . $end_char;
	$message .= "Remote IP: " . $_CONFIG_DATA['remote_addr'] . $end_char;
	$message .= "Browser: " . $_CONFIG_DATA['http_user_agent'] . $end_char . $end_char;

	// add file upload info to email
	$message .= $_FILE_DATA_EMAIL;

	// add any post or config values to the email here. eg.
	// $message .= "The client ID is " . $_POST_DATA['client_id'] . $end_char;
	// $message .= "The secret ID is " . $_CONFIG_DATA['secret_id'] . $end_char;

	mail($_CONFIG_DATA['to_email_address'], $_CONFIG_DATA['email_subject'], $message, $headers);
}

function getFormattedUploadResults($_FILE_DATA, $_CONFIG_DATA, $_POST_DATA){
	$upload_results = "<div id='upload_results_file_name_header'>FILE NAME</div><div id='upload_results_file_size_header'>FILE SIZE</div>\n";
	$col = 0;

	foreach($_FILE_DATA as $slot => $value){
		$file_slot = $_FILE_DATA[$slot]->getFileInfo('slot');
		$file_name = $_FILE_DATA[$slot]->getFileInfo('name');
		$file_size = $_FILE_DATA[$slot]->getFileInfo('size');
		$file_type = $_FILE_DATA[$slot]->getFileInfo('type');
		$file_status = $_FILE_DATA[$slot]->getFileInfo('status');
		$file_status_desc = $_FILE_DATA[$slot]->getFileInfo('status_desc');
		$formatted_file_size = formatBytes($file_size);

		if($col %= 2){ $css_class = "upload_results_even"; }
		else{ $css_class = "upload_results_odd"; }

		if($file_size > 0){
			if($_CONFIG_DATA['link_to_upload'] == 1){
				$file_path = $_CONFIG_DATA['path_to_upload'] . $file_name;
				$upload_results .= "<div class='upload_results_file_name $css_class'><a href=\"$file_path\" target=\"_blank\">$file_name</a></div><div class='upload_results_file_size $css_class'>$formatted_file_size</div>\n";
			}
			else{ $upload_results .= "<div class='upload_results_file_name $css_class'>$file_name</div><div class='upload_results_file_size $css_class'>$formatted_file_size</div>\n"; }
		}
		else{ $upload_results .= "<div class='upload_results_file_name $css_class'>$file_name</div><div class='upload_results_file_size $css_class'><span class='ubrError'>Failed To Upload</span></div>\n"; }

		$col++;
	}

	return $upload_results;
}

///////////////////////////////////////////////////////
//	Create an email string based on file upload data
///////////////////////////////////////////////////////
function getFileDataEmail($_FILE_DATA, $_CONFIG_DATA, $_POST_DATA){
	$email_file_list = '';
	$end_char = "\n";

	if($_CONFIG_DATA['html_email_support']){ $end_char = "<br>\n"; }

	foreach($_FILE_DATA as $slot => $value){
		$file_slot = $_FILE_DATA[$slot]->getFileInfo('slot');
		$file_name = $_FILE_DATA[$slot]->getFileInfo('name');
		$file_size = $_FILE_DATA[$slot]->getFileInfo('size');
		$file_type = $_FILE_DATA[$slot]->getFileInfo('type');
		$file_status = $_FILE_DATA[$slot]->getFileInfo('status');
		$file_status_desc = $_FILE_DATA[$slot]->getFileInfo('status_desc');
		$formatted_file_size = formatBytes($file_size);

		if($file_size > 0){
			if($_CONFIG_DATA['link_to_upload_in_email']){ $email_file_list .= "File Name: " . $_CONFIG_DATA['path_to_upload'] . $file_name . "     File Size: " . $formatted_file_size . $end_char; }
			else{
				if($_CONFIG_DATA['unique_upload_dir']){
					$email_file_list .= 'File Name: ' . $_CONFIG_DATA['upload_id'] . '/' . $file_name . "     File Size: " . $formatted_file_size . $end_char;
				}
				else{ $email_file_list .= 'File Name: ' . $file_name . "     File Size: " . $formatted_file_size . $end_char; }
			}
		}
		else{ $email_file_list .= 'File Name: ' . $file_name . "     File Size: Failed To Upload !" . $end_char; }
	}

	return $email_file_list;
}

/////////////////////////////////////////////
//	Create a thumbfile of a jpg or png file
/////////////////////////////////////////////
function createThumbFile($source_file_path, $source_file_name, $thumb_file_path, $thumb_file_name, $thumb_file_width, $thumb_file_height){
	list($source_file_width, $source_file_height, $type, $attr) = getimagesize($source_file_path . $source_file_name);
	$source_file_extention = getFileExtension($source_file_name);

	if($source_file_extention == 'jpg' || $source_file_extention == 'jpeg'){ $src_img = imagecreatefromjpeg($source_file_path . $source_file_name); }
	elseif($source_file_extention == 'png'){ $src_img = imagecreatefrompng($source_file_path . $source_file_name); }
	else{ return false; }

	$thumb = getScale($source_file_width, $source_file_height, $thumb_file_width, $thumb_file_height);
	$dst_img = ImageCreateTrueColor($thumb['width'], $thumb['height']);
	$thumb_file = $thumb_file_path . $thumb_file_name;

	imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb['width'], $thumb['height'], $source_file_width, $source_file_height);
	imagejpeg($dst_img, $thumb_file);
	imagedestroy($dst_img);
	imagedestroy($src_img);

	return true;
}

////////////////////////////////////////////////////////////
//	Get image scale
//	Contributor: http://icant.co.uk/articles/phpthumbnails/
////////////////////////////////////////////////////////////
function getScale($old_w, $old_h, $new_w, $new_h){
	$thumb = array();

	if($old_w > $old_h) {
		$thumb_w = $new_w;
		$thumb_h = ($new_w / $old_w) * $old_h;
	}

	if($old_w < $old_h) {
		$thumb_w = ($new_h / $old_h) * $old_w;
		$thumb_h = $new_h;
	}

	if($old_w == $old_h){
		if($new_w < $new_h){
			$thumb_w = $new_w;
			$thumb_h = $new_w;
		}
		else{
			$thumb_w = $new_h;
			$thumb_h = $new_h;
		}
	}

	$thumb['width'] = round($thumb_w);
	$thumb['height'] = round($thumb_h);

	return $thumb;
}

?>
