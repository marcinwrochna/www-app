################################################################# 
## Mod Title: mimeTeX for PHPBB 
## Copyright (C) 2003/2004  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
## Adapted from LatexRender by Steve Mayer <mayer@dial.pipex.com>
## 
## Included are two files which form a modification for the well known web-based bulletin board
## PHPBB (www.phpbb.org). The modification is very minor and update-patches from the phpbb crew
## should still work as long as this is the only modification.
##
## thanks
## Ulrich Klauer (for sending bad latex tags)
## Steve Mayer (for bugfixing, more phpbb tweaks and evangelism work :-))
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
# Make a new folder called mimetex directly below the forum's root folder
# Create mimetex/pictures folder. 
# This must be readable and writeable by the webserver which may mean chmod 777

# Adjust the paths in phpbb_hook_2.php in the 4 lines:
#  $mimetex_path = "/home/domain_name/public_html/cgi-bin/mimetex.cgi";
#  $mimetex_path_http = "http://domain_name/mimetex";
# $mimetex_cgi_path_http="http://domain_name/cgi-bin/mimetex.cgi";
#  $pictures_path = "/home/domain_name/public_html/mimetex/pictures";

# Copy all files and folders to their respective locations
#
# mimetex/index.php				-> mimetex/index.php
# mimetex/phpbb_hook_1.php			-> mimetex/phpbb_hook_1.php
# mimetex/phpbb_hook_2.php			-> mimetex/phpbb_hook_2.php
# mimetex/pictures				-> mimetex/pictures
# mimetex/pictures/index.php			-> mimetex/pictures/index.php


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
include("/home/domain_name/public_html/phpbb/mimetex/phpbb_hook_2.php");

#
#-----[ FIND ]---------------------------------------------
# around line 288
// [img]image_url_here[/img] code..
$text = preg_replace("#\[img\]((ht|f)tp://)([^\r\n\t<\"]*?)\[/img\]#sie", "'[img:$uid]\\1' . str_replace(' ', '%20', '\\3') . '[/img:$uid]'", $text);

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
## replace the path below with your path	
include("/home/domain_name/public_html/phpbb/mimetex/phpbb_hook_1.php");

## Add a TeX button which will add the [tex] and [/tex] tags
# 
#-----[ OPEN ]--------------------------------------------- 
# 
templates/subSilver/posting_body.tpl

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

# EoM 
