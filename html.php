<?php

function outputPage()
{	
	global $PAGE;
	header('Content-Language: pl');
	header('Content-Type: text/html; charset=UTF-8'); 
	
	// Too frequent version changes make things incompatible. Must-revalidate.
	header('Cache-Control: private, s-maxage=0, max-age=0, must-revalidate');
	header('Last-Modified: '. gmdate("D, d M Y H:i:s", time()) .' GMT');

	if (DEBUG>=2) $PAGE->content .= dumpSuperGlobals();
	$PAGE->latexPath = 'http://'. $_SERVER['HTTP_HOST'] . '/cgi-bin/mimetex.cgi';
	$PAGE->uploadGetter = 'http://'. $_SERVER['HTTP_HOST'] . '/uploader/getfile.php';

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<!--<meta name="keywords" content="" />
	<meta name="description" content="" />
	<meta name="author" content="" />-->
	
	<!--<link rel="icon" type="image/png" href="%[/images/favicon.png]%" />-->
	<!--<link rel="alternate" type="application/atom+xml" href="%[atom.php,lang=-]%" title="Atom feed" />-->
	<link rel="stylesheet" type="text/css" href="css.css?20100621" />
	<link rel="stylesheet" type="text/css" href="images/icons/icons_png.css?20100525b" />
	<title>WWW - %title%</title>

	%head%
	<script type="text/javascript" src="common.js?20100517"></script>
	<script type="text/javascript" src="tinymce/tiny_mce_gzip.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
	<script type="text/javascript">
		tinyMCE_GZ.init({
		plugins : 'nonbreaking,latex,paste,ubrupload',
		themes : 'advanced',
		languages : 'en,pl',
		disk_cache : true,
		debug : false,
		suffix : '_src'
	});
	</script>
	<script type="text/javascript"><!--	
		%js% 
		
		tinyMCE.init({
			language: "pl",
			mode : "specific_textareas",
			editor_selector : "mceEditor",
			plugins: "nonbreaking,latex,ubrupload",
			nonbreaking_force_tab : "true",
			entity_encoding : "raw",
			theme: "advanced",
			theme_advanced_toolbar_location: "top",
			theme_advanced_buttons1 :
				 "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,separator,undo,redo,|,link,unlink,image,ubrupload,hr", 
			theme_advanced_buttons2 : "formatselect,fontsizeselect,|,latex,charmap,sub,sup,outdent,indent,|,removeformat,code,|,forecolor,backcolor,", 
			theme_advanced_buttons3 : "",
			theme_advanced_blockformats : "p,h3,h4,h5,h6,pre,div", //blockquote,address,samp,dd,dt
			indentation: "20px",
			theme_advanced_path : true,
			theme_advanced_path_location : "bottom",
			latex_renderUrl : "%latexPath%",
			ubrupload_getter : "%uploadGetter%",
			button_tile_map : true
		 });
				
		$(document).ready(function(){   
			%jsOnLoad%			
		});
	--></script>
	<!--[if lt IE 7]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE7.js" type="text/javascript"></script>
		<style type="text/css">
			body { behavior: url("iecsshover3.htc"); }
		</style>
	<![endif]-->

</head>
<body>
	<div id="tooltip" onmouseover="tiptipon()" onmouseout="tipoff()"></div>
	<div id="globalContainer">
		<div id="headerBox"><h1><a href="index.php">
			<img src="images/logo.gif" alt="Wakacyjne Warsztaty Wielodyscyplinarne" />
		</a></h1></div>

		<div id="middleContainer">
			<div id="contentBoxMargin"><div id="contentBox">
				%topContent%
				%content%
			</div></div>

			<div id="menuPanel">
				<h2 style="display:none;">menu</h2>
				%menu%
			</div>
		</div>
		
		<div id="footerBox" >
			<span class="left">
				<a href="?action=about">credits</a>
			</span>
			<span class="right">
				&copy; <?php echo strftime("%Y"); ?> Wakacyjne Warsztaty Wielodyscyplinarne
			</span>
		</div>
	</div>
	
	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try
		{
			var pageTracker = _gat._getTracker("UA-12926426-2");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
	<!-- php time: <?php echo time()-$_SERVER['REQUEST_TIME']; ?>s -->
</body>
</html>
	<?php
	echo $PAGE->finish();
}
