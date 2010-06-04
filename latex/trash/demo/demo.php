<?php
/**
 * LaTeX Rendering Class - Simple Usage Example
 * Copyright (C) 2003  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * --------------------------------------------------------------------
 * @author Benjamin Zeiss <zeiss@math.uni-goettingen.de>
 * @version v0.8
 * @package latexrender
 *
 */
	// the final image is shown on the page using
	// echo latex_content($text);

    // this is just an example page

	/* Sending HTTP headers */
	@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 				// Date in the past
	@header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
	@header("Cache-Control: no-store, no-cache, must-revalidate"); 	// HTTP/1.1
	@header("Cache-Control: post-check=0, pre-check=0", false);
	@header("Pragma: no-cache"); 									// HTTP/1.0

	//set variables to integers otherwise they will appear as 0 when compared with maximum size
	if (isset($density)) (settype($density,"integer"));
	if (isset($xsize)) (settype($xsize,"integer"));
	if (isset($ysize)) (settype($ysize,"integer"));
	if (isset($stringlength)) (settype($stringlength,"integer"));
	if (isset($fontsize)) (settype($fontsize,"integer"));
	if (!(isset($_POST['latex_formula']))) ($fontsize = 10);
	if (isset($cachefiles)) (settype($cachefiles,"integer"));
	if (!(isset($_POST['latex_formula']))) ($cachefiles = 1);
?>
    <html><title>LatexRender Demo</title>
    <head>
    <script language="JavaScript" type="text/javascript">
	function addtags() {
		if (document.selection.createRange().text!='') {
	  		document.selection.createRange().text = '[tex]'+document.selection.createRange().text+'[/tex]';
	  	}
	}//--></script>

	<script language="JavaScript" type="text/javascript">
		function extsize() {
			document.renderer.latexclass[1].checked = true;
		  	}
	//--></script>

	</head>
    <body bgcolor='lightgrey'><center><h3>LatexRender Demo</h3>
    <font size=-1><i>Add tags around text you want to convert to an image<br>
    or press the button to add them around highlighted text</i></font>

 	<form method="post" name="renderer">
      <CENTER>
	    <P ALIGN=CENTER>
	     <TABLE WIDTH="100%" CELLPADDING="2" CELLSPACING="0" BORDER="0">
	      <TR>
	       <TD WIDTH="30%" VALIGN=CENTER>
	        <CENTER>
	         <P ALIGN=CENTER>
	          <TABLE CELLPADDING="2" CELLSPACING="0" BORDER="1">
	           <TR>
	            <TD COLSPAN="3" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">Formula Density</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD COLSPAN="3" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2"><INPUT TYPE=TEXT NAME="density" VALUE="<?php if (isset($density)) {echo $density;} else {echo '120';} ?>" SIZE="3" MAXLENGTH="4"></FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD COLSPAN="3" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">Size</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">x = <INPUT TYPE=TEXT NAME="xsize" VALUE="<?php if (isset($xsize)) {echo $xsize;} else {echo '500';} ?>" SIZE="3" MAXLENGTH="4"></FONT></FONT></TD>
	            <TD VALIGN=CENTER>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">&nbsp;by&nbsp; </FONT></FONT></TD>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">y = <INPUT TYPE=TEXT NAME="ysize" VALUE="<?php if (isset($ysize)) {echo $ysize;} else {echo '500';} ?>" SIZE="3" MAXLENGTH="4"></FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD COLSPAN="3" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">String Length</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD COLSPAN="3" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2"><INPUT TYPE=TEXT NAME="stringlength" VALUE="<?php if (isset($stringlength)) {echo $stringlength;} else {echo '500';} ?>" SIZE="3" MAXLENGTH="4"></FONT></FONT></TD>
	           </TR>

<?php
if ($cachefiles==0) {
	$chkbox="";
} else {
	$chkbox="checked";;
}
?>

			<TR><TD COLSPAN="3" VALIGN=TOP>
			 <CENTER>
			  <P ALIGN=CENTER>
			   <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2">Cache Files <INPUT TYPE=CHECKBOX NAME="cachefiles" VALUE=1 <?php echo $chkbox; ?>>&nbsp;</FONT></FONT></TD>
   			</TR>
	          </TABLE>
	          </TD>
	       <TD WIDTH="40%" VALIGN=TOP>
	        <CENTER>
	         <P ALIGN=CENTER>
	          <INPUT TYPE="BUTTON" NAME="btnCopy" VALUE="Add TeX Tags" onclick="addtags();"></P>
	         </CENTER>
	        <CENTER>
	         <P ALIGN=CENTER>
	          <TEXTAREA NAME="latex_formula" COLS="50" ROWS="8">
<?php
     if (isset($_POST['latex_formula'])) {
         echo stripslashes($_POST['latex_formula']);
     } else {
         echo "Example Text:\nThis is just text but [tex]\sqrt{2}[/tex] should be shown as an image and so should [tex]\frac {1}{2}[/tex].
 			\nAnother formula is [tex]\frac {43}{12} \sqrt {43}[/tex]";
     }

	$btn1="";
	$btn2="";
	$btn3="";
	$btn4="";
	switch ($fontsize) {
		case $fontsize==8:
			$btn1="checked";
			$latexclass="extarticle";
			break;
		case $fontsize==10:
			$btn2="checked";
			break;
		case $fontsize==11:
			$btn3="checked";
			break;
		case $fontsize==12:
			$btn4="checked";
			break;
		default:
			$btn2="checked";
	}
	$btn5="";
	$btn6="";
	if ($latexclass=="extarticle") {
		$btn6="checked";
	} else {
		$btn5="checked";
	}
	$btn7="";
	$btn8="";
	if ($imageformat=="png") {
		$btn8="checked";
	} else {
		$btn7="checked";
	}
?></TEXTAREA></TD>
	       <TD WIDTH="30%" VALIGN=CENTER>
	        <CENTER>
	         <P ALIGN=CENTER>
	          <TABLE CELLPADDING="2" CELLSPACING="0" BORDER="1">
	           <TR>
	            <TD COLSPAN="2" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT FACE="Verdana,Arial,Times New I2"><FONT SIZE="2">Font Size</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="fontsize" VALUE="8" <?php echo $btn1; ?> onClick="extsize();">&nbsp;
	               8 pt </FONT></FONT></TD>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="fontsize" VALUE="10" <?php echo $btn2; ?>>
	               10 pt </FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="fontsize" VALUE="11" <?php echo $btn3; ?>>
	               11 pt</FONT></FONT></TD>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="fontsize" VALUE="12" <?php echo $btn4; ?>>
	               12 pt</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD COLSPAN="2" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2">Latex Class</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="latexclass" VALUE="article" <?php echo $btn5; ?>>Article</FONT></FONT></TD>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="latexclass" VALUE="extarticle" <?php echo $btn6; ?>>Extarticle</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD COLSPAN="2" VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2">Image Format</FONT></FONT></TD>
	           </TR>
	           <TR>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="imageformat" VALUE="gif" <?php echo $btn7; ?>> GIF</FONT></FONT></TD>
	            <TD VALIGN=TOP>
	             <CENTER>
	              <P ALIGN=CENTER>
	               <FONT SIZE="2"><FONT FACE="Verdana,Arial,Times New I2"><INPUT TYPE=RADIO NAME="imageformat" VALUE="png" <?php echo $btn8; ?>> PNG</FONT></FONT></TD>
	           </TR>
	          </TABLE></TD>
	      </TR>
	     </TABLE></P>
	    </CENTER>
	   <CENTER>
	    <P ALIGN=CENTER>
   			<INPUT TYPE="submit" VALUE="Render">
 	</CENTER>
    </FORM>

<?php
    if (isset($_POST['latex_formula'])) {
    	$text=stripslashes($_POST['latex_formula']);
        echo "<u>Result</u><br><br>";
        // now convert and show the image
		include_once("latex.php");
     	echo nl2br(latex_content($text));
    }

    echo "</center></body></html>";
?>