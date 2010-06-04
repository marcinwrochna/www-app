<?php
/*
	html.php defines the page's global html. Everything directly sent to the
	browser is here, including headers.
	Included at the end of index.php and panel.php
	Requires common.php
	Uses global $pageVars = array(
		'menu' => left menu, usually an <ul>,
		'pageTitle' => <title>,
		'pageContent' => central content - the article, forms, panels, ...,
		'pageContentTop => same, but always above pageContent, usually messages
		'head' => things to add inside <head>, usually external js scripts,
		'javascriptOnLoad' => javascript to run onload,
	);
*/

header('Content-Language: pl');
header('Content-Type: text/html; charset=UTF-8'); 

// Too frequent version changes make things incompatible. Must-revalidate.
header('Cache-Control: private, s-maxage=0, max-age=0, must-revalidate');
header('Last-Modified: '. gmdate("D, d M Y H:i:s", time()) .' GMT');

if (DEBUG>=2) $pageVars['pageContent'].= dumpSuperGlobals();

$template = new SimpleTemplate($pageVars);
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<!--<meta name="keywords" content="" />
	<meta name="description" content="" />
	<meta name="author" content="" />-->
	
	<!--<link rel="icon" type="image/png" href="%[/images/favicon.png]%" />-->
	<!--<link rel="alternate" type="application/atom+xml" href="%[atom.php,lang=-]%" title="Atom feed" />-->
	<link rel="stylesheet" type="text/css" href="css.css" />
	<title>%pageTitle%</title>

	%head%
	<script type="text/javascript"><!--
		function onLoad()
		{
			%javascriptOnLoad%
		}
	--></script>
	<!--[if lt IE 7]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE7.js" type="text/javascript"></script>
		<style type="text/css">
			body { behavior: url("iecsshover3.htc"); }
		</style>
	<![endif]-->

</head>
<body onload="onLoad()">
	<div id="globalContainer">
		<div id="headerBox"><a href="index.php"><h1>
			<img src="images/logo.gif" alt="Wakacyjne Warsztaty Wielodyscyplinarne" />
		</h1></a></div>

		<div id="middleContainer">
			<div id="contentBoxMargin"><div id="contentBox">
				%pageContentTop%
				%pageContent%
			</div></div>

			<div id="menuPanel">
				<h2 style="display:none;">menu</h2>
				%menu%
			</div>
		</div>
		
		<div id="footerBox" >
			&copy; <?php echo strftime("%Y"); ?> Wakacyjne Warsztaty Wielodyscyplinarne
		</div>
	</div>
</body>
</html>
<?php echo $template->finish(); ?>
