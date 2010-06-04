################################################################# 
## Mod Title: LaTeX Rendering Class 
## Mod Version: 0.8
## Copyright (C) 2003/2004  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
## Maintained from version 0.7 by Steve Mayer <mayer@dial.pipex.com>
##
## Revised version for phpBB3 following sisteczko at
## http://www.phpbb.com/community/viewtopic.php?f=72&t=653165&st=0&sk=t&sd=a#p3671325
## phpBB3 automates the adding of buttons so the process is simpler
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
# latexrender/phpBB/phpbb_hook_2.php			-> latexrender/phpbb_hook_2.php
# latexrender/phpBB/pictures				-> latexrender/pictures
# latexrender/phpBB/pictures/index.php			-> latexrender/pictures/index.php
# latexrender/phpBB/tmp					-> latexrender/tmp
# latexrender/phpBB/tmp/index.php			-> latexrender/tmp/index.php
#
# phpbb_hook_1.php is not required for phpBB3

# 
#-----[ OPEN ]--------------------------------------------- 
# 
phpbb_hook_2.php

#
#-----[ FIND ]---------------------------------------------
Two occurances of $uid
#
#-----[ REPLACE WITH]---------------------------------------------
$bbcode_uid

#
#-----[ FIND ]---------------------------------------------
Six occurances of $text
#
#-----[ REPLACE WITH]---------------------------------------------
$message

# 
#-----[ OPEN ]--------------------------------------------- 
# 
includes/bbcode.php

#
#-----[ FIND ]---------------------------------------------
# around line 118
// Remove the uid from tags that have not been transformed into HTML

# 
#-----[ BEFORE, ADD ]-------------------------------------- 
# 
## replace the path below with your path	
include("/var/www/phpBB3/latexrender/phpbb_hook_2.php");


## Add a TeX button which will add the [tex] and [/tex] tags
# 
#-----[ OPEN ]--------------------------------------------- 
# 
Administrative Control Panel 

#
#-----[ FIND ]---------------------------------------------
#
posting->BBCode usage

# 
#-----[ AFTER, ADD ]-------------------------------------- 
# 
[tex]{TEXT}[/tex]

Check Display on Posting, and click Submit

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
