<?php
/**
 *	Only 2 tiny changes to use Mimetex instead of LatexRender
 *	which is to change
 *		include_once("latex.php");
 *    	echo nl2br(latex_content($text));
 * 	to
 *		include_once("mimetex.php");
 *     	echo nl2br(mimetex($text));
 *
 */
	// the final image is shown on the page using
	// echo mimetex($text);

    // this is just an example page
    echo "<html><title>Mimetex Demo</title>
    <head><script language=\"JavaScript\" type=\"text/javascript\">
	function addtags() {
		if (document.selection.createRange().text!='') {
	  		document.selection.createRange().text = '[tex]'+document.selection.createRange().text+'[/tex]';
	  	}
	}//--></script></head>";
    echo "<body bgcolor='lightgrey'><center><h3>Mimetex Demo</h3>";
    echo "<font size=-1><i>Add tags around text you want to convert to an image<br>
    or press the button to add them around highlighted text</i></font>";

    echo "<form method='post'>";
    echo "<input onclick=\"addtags()\" type=\"button\" value=\"Add TeX tags\" name=\"btnCopy\"><br><br>";
	echo "<textarea name='latex_formula' rows=8 cols=50>";

    if (isset($_POST['latex_formula'])) {
        echo stripslashes($_POST['latex_formula']);
    } else {
        echo "Example Text:\nThis is just text but [tex]\sqrt{2}[/tex] should be shown as an image and so should [tex]\frac {1}{2}[/tex].
			\nAnother formula is [tex]\frac {43}{12} \sqrt {43}[/tex]";
    }

    echo "</textarea>";
    echo "<br><br><input type='submit' value='Render'>";
    echo "</form>";

    if (isset($_POST['latex_formula'])) {
    	$text=stripslashes($_POST['latex_formula']);
        echo "<u>Result</u><br><br>";
        // now convert and show the image
		include_once("mimetex.php");
     	echo nl2br(mimetex($text));
    }

    echo "</center></body></html>";
?>
