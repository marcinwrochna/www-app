<HTML>
<HEAD>
<TITLE>LaTeX Code</TITLE>
<SCRIPT TYPE="text/javascript">
<!--
window.focus();
//-->
</SCRIPT>
<SCRIPT LANGUAGE="JavaScript">

function Copy()
{
<?php
/*	Javascript objects to new lines here
	so urlencode, replace new line by escaped \\r\\n (\\r is needed by windows notepad)
	then unencode again - phew!
*/
?>
text="<?php echo urldecode(str_replace("%0D%0A","\\r\\n",urlencode($code))) ?>";
window.clipboardData.setData("Text", text);
}

</SCRIPT>
</HEAD>
<BODY BGCOLOR=#E5E5E5>
<center><tt><?php echo nl2br(stripslashes($code)) ?></tt>
<P>
<BUTTON onclick="Copy();">Copy to Clipboard</BUTTON>
</center>
</BODY>
</HTML>