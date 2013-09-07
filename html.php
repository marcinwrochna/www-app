<?php
/*
 * html.php
 * Included in page.php Page::finish()
 * What is output in order:
 * - http headers (content encoding and standards, cache)
 * - html head (paths to non-php files, javascript configs, IE fixes)
 * - html body (content boxes: header, footer, menu)
 * - google analytics javascript.
 */

// Transform filenames (like 'css.css') into absolute paths
// with modification time to prevent caching.
function writeMTime($f)
{
	echo ABSOLUTE_PATH_PREFIX . $f .'?'. filemtime($f);
}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl" dir="ltr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<link rel="icon" type="image/png" href="<?php writeMTime('images/favicon.png'); ?>" />
	<link rel="stylesheet" type="text/css" href="<?php writeMTime('css.css'); ?>" />
	<link rel="stylesheet" type="text/css" href="<?php writeMTime('images/icons_png.css'); ?>" />
	<link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/themes/ui-lightness/jquery-ui.css" type="text/css" media="all" />
	<link rel="stylesheet" type="text/css" href="<?php writeMTime('fineuploader/fineuploader-3.3.1.css'); ?>" />
	<title>%title% - WWW</title>

	%head%
	<script type="text/javascript" src="<?php writeMTime('common.js'); ?>"></script>
	<script type="text/javascript" src="<?php writeMTime('tinymce/tiny_mce_gzip.js'); ?>"></script>
	<script type="text/javascript" src="<?php writeMTime('tinymce/config.js'); ?>"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>
	<script type="text/x-mathjax-config">
		MathJax.Hub.Config({
			tex2jax: {
				inlineMath: [ ['[tex]','[/tex]'] ], //, ['\\(','\\)']  ],
				displayMath: [ ['$$','$$'] ], //, ['\\[','\\]'] ],
				processEnvironments: false,
				processClass: "userHTML|tex2jax_process"
				// The <body> has class tex2jax_ignore, so only these classes get parsed.
				// Unfortunately skipping <code> tags doesn't work because of that.
			}
		});
	</script>
	<script type="text/javascript" src="http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>	
	<script type="text/javascript" src="fineuploader/jquery.fineuploader-3.3.1.js"></script>
	<script type="text/javascript">tinyMCE_GZ.init(tinyMCE_GZ_config);</script>
	<script type="text/javascript">
		tinyMCE_config.latex_renderUrl = "%latexPath%";
		tinyMCE_config.ubrupload_getter = "%uploadGetter%";
		tinyMCE.init(tinyMCE_config);
		%js%
		$(document).ready(function(){
			$('#tooltip').mouseenter(function(){$(this).stop(true,true).show();});
			$('#tooltip').mouseleave(function(){$(this).stop(true,true).fadeOut('fast');});
			%jsOnLoad%
		});
	</script>
	<!--[if lt IE 9]>
		<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script>
	<![endif]-->
	<!--[if lt IE 8]>
		<style type="text/css">body { font-size: 100.00%; } /* .contentBox.article { zoom:1; } */</style>
		<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE7-squish.js" type="text/javascript"></script>
	<![endif]-->
	<?php if (GOOGLE_ANALYTICS_ACCOUNT): ?>
		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', '<?php echo GOOGLE_ANALYTICS_ACCOUNT; ?>']);
			_gaq.push(['_setCustomVar', 1, 'uid', '<?php global $USER; echo $USER['uid']; ?>', 1]);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    			var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>
	<?php endif; ?>
</head>
<body class="tex2jax_ignore">
	<div id="tooltip"></div>
	<div id="globalContainer">
		<div id="headerBox"><h1><a href="homepage">
			<img src="<?php writeMTime('images/logo.gif') ?>" alt="Wakacyjne Warsztaty Wielodyscyplinarne" />
		</a></h1></div>

		<div id="middleContainer">
			<div id="contentBoxMargin"><div id="contentBox">
				%topContent%
				%headerTitle%
				%content%
			</div></div>

			<div id="menuPanel">
				<h2 style="display:none;">menu</h2>
				%menu%
			</div>
		</div>

		<div id="footerBox" >
			<span class="left">
				<a href="about">credits</a>
			</span>
			<span class="right">
				&copy; <?php echo strftime("%Y"); ?> Wakacyjne Warsztaty Wielodyscyplinarne
			</span>
		</div>
	</div>
	<!-- php time: <?php echo time()-$_SERVER['REQUEST_TIME']; ?>s -->
</body>
</html>
