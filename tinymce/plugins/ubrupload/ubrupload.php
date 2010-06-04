<?php include_once dirname(__FILE__).'/../../../uploader/ubr.php'; ?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>{#ubrupload.desc}</title>
	
	<script language="javascript" type="text/javascript" src="../../tiny_mce_popup_src.js"></script>
	<script language="javascript" type="text/javascript" src="jscripts/ubrupload.js"></script>
	<script language="javascript" type="text/javascript" src="../../utils/mctabs.js"></script>
	<link href="css/ubrupload.css" rel="stylesheet" type="text/css" />
	
	<?php			
		if (isset($_GET['upload_id']))
		{
			$files = getUploadInfo();
			echo '<script language="javascript" type="text/javascript" >';
			echo 'tinyMCEPopup.onInit.add(function(){';
			foreach($files as $f)
			{
				$name = htmlspecialchars($f['realname'], ENT_QUOTES);
				$href = 'uploader/getfile.php?f='. urlencode($f['name']);
				$size = formatBytes($f['size']);
				$html = '<a class="mceUbrUpload" href="'. $href .'">'. $name .'</a> ('. $size .')';
				$html = addslashes($html);
				echo 'tinyMCEPopup.execCommand("mceInsertRawHTML", false, \''. $html.'\');';
				echo 'tinyMCEPopup.close();';
			}
			echo '});</script>';
			echo '<script language="javascript" type="text/javascript" >';
			//echo 'tinyMCEPopup.onInit.add(UbrUploadDialog.insertUpload, UbrUploadDialog);';
			echo '</script>';
		}
		else
		{	
			echo buildUberUploadHead('/tinymce/plugins/ubrupload/ubrupload.php');
		}
	?>
	
	<base target="_self" />
</head>
<body>
	<div class="tabs">
		<ul>
			<li id="general_tab" class="current"><span><a href="javascript:mcTabs.displayTab('general_tab','general_panel');" onmousedown="return false;">{#ubrupload.desc}</a></span></li>
		</ul>
	</div>

	<div class="panel_wrapper">
		<div id="general_panel" class="panel current">
			<?php
				if (!isset($_GET['upload_id']))
					echo buildUberUploaderBody();
			?>
		</div>
	</div>

<form onsubmit="return false;" action="#" name="ubruploaderform" id="ubruploaderform">
	<input type="hidden" name="lrurl" value="" />
	<div class="mceActionPanel">
		<!--<div style="float: left">
			<input type="button" id="insert" name="insert" value="{#insert}" onclick="UbrUploadDialog.insertUpload();" />
		</div>-->

		<div style="float: right">
			<input type="button" id="cancel" name="cancel" value="{#cancel}" onclick="tinyMCEPopup.close();" />
		</div>
	</div>
</form>
</body>
</html>
