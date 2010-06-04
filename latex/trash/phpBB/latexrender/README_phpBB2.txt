################################################################# 
## Mod Title: LaTeX Rendering Class 
## Mod Version: 0.8
## Copyright (C) 2003/2004  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
## Maintained from version 0.7 by Steve Mayer <mayer@dial.pipex.com>
##
## Description: This Class is supposed to offer easy to use functionality to render LaTeX 
## formulas into pictures 
## Discussion on LatexRender can be found at http://www.phpbb.com/phpBB/viewtopic.php?t=94454
## In Linux do "which latex", "which convert" and "which identify" to find the paths which could
## well be the default given in the file. If they are not there they will need to be installed.
## For Unix compatible systems (distributions commonly include LaTeX), a common LaTeX 
## distribution is teTeX available from http://www.tug.org/teTeX/
## For Windows, MiKTeX can be found at http://www.miktex.org/
## ImageMagick is at http://www.imagemagick.org/
## 
## thanks
## Ulrich Klauer (for sending bad latex tags)
## Steve Mayer (for bugfixing, more phpbb tweaks and evangelism work :-))
##
## Benjamin Zeiss
##
## Installation Level: Easy
## Installation Time: 10 Minutes 
## Files To Edit: includes/bbcode.php, templates/subSilver/posting_body.tpl,
##		  language/lang_english/lang_main.php, posting.php
##
################################################################# 
## Before Adding This MOD To Your Forum, You Should Back Up All Files Related To This MOD 
################################################################# 
##
# 
# Make a new folder called latexrender directly below the forum's root folder
# Create latexrender/pictures and latexrender/tmp folders. 
# These must be readable and writeable by the webserver which may mean chmod 777

# Adjust the paths in phpbb_hook_2.php in the 2 lines:
#    $latexrender_path = "/home/domain_name/public_html/phpbb/latexrender";
#    $latexrender_path_http = "/phpbb/latexrender";

# Adjust the paths in class.latexrender.php, if necessary, to point to the latex, dvips and ImageMagick executables
#    var $_latex_path = "/usr/bin/latex";
#    var $_dvips_path = "/usr/bin/dvips";
#    var $_convert_path = "/usr/bin/convert";
#    var $_identify_path="/usr/bin/identify";
# For Windows the paths in class.latexrender.php must use \\ or / not just a single \ 
# For example
#    var $_latex_path = "C:\\texmf\\miktex\\bin\\latex.exe";
# or 
#    var $_latex_path = "C:/texmf/miktex/bin/latex.exe";

# Adjust any other variables at the beginning of class.latexrender.php that you wish to change,
# the default values should work fine

# Copy all files and folders to their respective locations
#
# latexrender/phpBB/class.latexrender.php		-> latexrender/class.latexrender.php
# latexrender/phpBB/index.php				-> latexrender/index.php
# latexrender/phpBB/phpbb_hook_1.php			-> latexrender/phpbb_hook_1.php
# latexrender/phpBB/phpbb_hook_2.php			-> latexrender/phpbb_hook_2.php
# latexrender/phpBB/pictures				-> latexrender/pictures
# latexrender/phpBB/pictures/index.php			-> latexrender/pictures/index.php
# latexrender/phpBB/tmp					-> latexrender/tmp
# latexrender/phpBB/tmp/index.php			-> latexrender/tmp/index.php


# 
#-----[ OPEN ]--------------------------------------------- 
# 
includes/bbcode.php

#
#-----[ FIND ]---------------------------------------------
# around line 195
// Patterns and replacements for URL and email tags..
$patterns = array();
$replacements = array();

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
## replace the path below with your path	
include("/home/domain_name/public_html/phpbb/latexrender/phpbb_hook_2.php");

#
#-----[ FIND ]---------------------------------------------
# around line 288
// [img]image_url_here[/img] code..
$text = preg_replace("#\[img\]((ht|f)tp://)([^\r\n\t<\"]*?)\[/img\]#sie", "'[img:$uid]\\1' . str_replace(' ', '%20', '\\3') . '[/img:$uid]'", $text);

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
## replace the path below with your path	
include("/home/domain_name/public_html/phpbb/latexrender/phpbb_hook_1.php");

## Add a TeX button which will add the [tex] and [/tex] tags
# 
#-----[ OPEN ]--------------------------------------------- 
# 
templates/subSilver/posting_body.tpl

#
#-----[ FIND ]---------------------------------------------
#
f_help = "{L_BBCODE_F_HELP}";

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
x_help = "{L_BBCODE_X_HELP}";

#
#-----[ FIND ]---------------------------------------------
#
bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[list]','[/list]','[list=]','[/list]','[img]','[/img]','[url]','[/url]');

# 
#-----[ REPLACE WITH ]---------------------------------------
# 
bbtags = new Array('[b]','[/b]','[i]','[/i]','[u]','[/u]','[quote]','[/quote]','[code]','[/code]','[list]','[/list]','[list=]','[/list]','[img]','[/img]','[url]','[/url]','[tex]','[/tex]');
## if you have other buttons just add '[tex]','[/tex]' to the end of the array

## widen the textboxes to line up with new buttons
#
#-----[ FIND ]---------------------------------------------
#
<input type="text" name="subject" size="45" maxlength="60" style="width:450px" tabindex="2" class="post" value="{SUBJECT}" />

# 
#-----[ REPLACE WITH ]---------------------------------------
# 
<input type="text" name="subject" size="45" maxlength="60" style="width:500px" tabindex="2" class="post" value="{SUBJECT}" />

#
#-----[ FIND ]---------------------------------------------
#
<td colspan="9"><span class="gen">
<textarea name="message" rows="15" cols="35" wrap="virtual" style="width:450px" tabindex="3" class="post" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);">{MESSAGE}</textarea>

# 
#-----[ REPLACE WITH ]---------------------------------------
# 
<td colspan="10"><span class="gen">
<textarea name="message" rows="15" cols="35" wrap="virtual" style="width:500px" tabindex="3" class="post" onselect="storeCaret(this);" onclick="storeCaret(this);" onkeyup="storeCaret(this);">{MESSAGE}</textarea>

#
#-----[ FIND ]---------------------------------------------
#
<td><span class="genmed"> 
<input type="button" class="button" accesskey="w" name="addbbcode16" value="URL" style="text-decoration: underline; width: 40px" onClick="bbstyle(16)" onMouseOver="helpline('w')" />
</span></td>

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
## replace 18 (twice) with an even number 2 more than the number in the previous lines.
## Change x (in accesskey="x") if you already have this letter for other buttons
<td><span class="genmed"> 
<input type="button" class="button" accesskey="x" name="addbbcode18" value="TeX" style="width: 40px"  onClick="bbstyle(18)" onMouseOver="helpline('x')" />
</span></td>
	
#
#-----[ FIND ]---------------------------------------------
# replace 18 (three times) with an even number 2 more than the number above
<select name="addbbcode18" onChange="bbfontstyle('[color=' + this.form.addbbcode18.options[this.form.addbbcode18.selectedIndex].value + ']', '[/color]')" onMouseOver="helpline('s')">

# 
#-----[ REPLACE WITH ]---------------------------------------
#
<select name="addbbcode20" onChange="bbfontstyle('[color=' + this.form.addbbcode20.options[this.form.addbbcode20.selectedIndex].value + ']', '[/color]')" onMouseOver="helpline('s')">

#
#-----[ FIND ]---------------------------------------------
# replace 20 (three times) with an even number 2 more than the number above
</select> &nbsp;{L_FONT_SIZE}:<select name="addbbcode20" onChange="bbfontstyle('[size=' + this.form.addbbcode20.options[this.form.addbbcode20.selectedIndex].value + ']', '[/size]')" onMouseOver="helpline('f')">

# 
#-----[ REPLACE WITH ]---------------------------------------
#
</select> &nbsp;{L_FONT_SIZE}:<select name="addbbcode22" onChange="bbfontstyle('[size=' + this.form.addbbcode22.options[this.form.addbbcode22.selectedIndex].value + ']', '[/size]')" onMouseOver="helpline('f')">


# 
#-----[ OPEN ]--------------------------------------------- 
# 
language/lang_english/lang_main.php

#
#-----[ FIND ]---------------------------------------------
#
$lang['bbcode_f_help'] = 'Font size: [size=x-small]small text[/size]';

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
$lang['bbcode_x_help'] = 'Formula: [tex]formula[/tex]  (alt+x)';

# 
#-----[ OPEN ]--------------------------------------------- 
# 
posting.php

#
#-----[ FIND ]---------------------------------------------
#
'L_BBCODE_F_HELP' => $lang['bbcode_f_help'],

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
'L_BBCODE_X_HELP' => $lang['bbcode_x_help'],

# 
#-----[ SAVE/CLOSE ALL FILES ]------------------------------------------ 
# 

Further notes:

1. LatexRender produces GIFs by default - if you prefer PNG then change the variable at the beginning
of class.latexrender.php. Be aware though that ImageMagick may not make PNGs transparent so may show them
with a white background

2. The default is to use article.cls for LaTeX which is a common class but it only supports 10,11,12 point
font sizes. For smaller (or larger fonts) in the image, install the extsizes package available from CTAN 
http://ctan.tug.org/. Add these files to a new extsizes directory in usr/share/texmf/tex/latex.
Refresh the database using "texhash" command, assuming you are using teTeX.
Then in class.latexrender.php you can change var $_font_size = 10; to var $_font_size = 8;

3. You can allow larger images and/or longer latex code by adjusting the default 500 in
class.latexrender.php in the lines
    var $_xsize_limit = 500;
    var $_ysize_limit = 500;
    var $_string_length_limit = 500;

4. You can make equation arrays and other code that starts with \begin, by prefacing them with 2 new lines.

5. Displayed formulae can be rendered using \displaystyle and <center> tag

# EoM 
