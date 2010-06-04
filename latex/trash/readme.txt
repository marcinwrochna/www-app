LaTeX Rendering Class 0.8
Copyright (C) 2003/2004  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
Maintained from version 0.7 by Steve Mayer
----------------------

LatexRender is a set of scripts that allows one to call LaTeX (or mimeTeX) from PHP programs; 
in particular, this allows users to enter LaTeX commands in a forum and, on posting, 
have it replaced by a suitable gif or png. LatexRender requires either LaTeX and
ImageMagick or mimeTeX to be installed. The open source PHP code was originally 
designed for use with phpBB, but it can be adapted for use with other PHP programs. 
Details and example installations are given at http://www.mayer.dial.pipex.com/tex.htm#latexrender

-----------------------

class.latexrender.php
---------------------
This script prepares a simulated document then calls LaTeX and ImageMagick to output the image in 
GIF or PNG format. (Imagemagick calls Ghostscript so ensure that is installed as well)

It requires the user to put in the paths of the executables, latex, dvips, convert and identify.

In Linux do "which latex", "which convert" and "which identify" to find the paths which could
well be the default given in the file. If they are not there they will need to be installed.
For Unix compatible systems (distributions commonly include LaTeX), a common LaTeX distribution 
is teTeX available from http://www.tug.org/teTeX/
For Windows, MiKTeX can be found at http://www.miktex.org/
ImageMagick is at http://www.imagemagick.org/

For Windows the paths in class.latexrender.php must use \\ or / not just a single \. For example
 var $_latex_path = "C:\\texmf\\miktex\\bin\\latex.exe";
 or 
 var $_latex_path = "C:/texmf/miktex/bin/latex.exe";

LatexRender uses a cache so that the same formula is not re-processed to minimise the load on the server.

----------------------

The scripts were originally written to work with phpBB forums but can easily be adapted for use 
with other PHP programs.

PhpBB
-----
Full instructions on how to install for phpBB is given in the phpBB folder. All the necessary
files are in that folder: class.latexrender.php, index.php, phpbb_hook_1.php, phpbb_hook_2.php

Other programs
--------------
The files can be found in the otherPHP folder.
For other PHP programs create a new folder called latexrender and inside this copy
a) the 2 files: latex.php and class.latexrender.php
b) two empty subfolders /tmp and /pictures which must be writeable by the scripts so may need to be chmod 777

In latex.php change the lines
  $latexrender_path = "/home/domain_name/public_html/phpbb/latexrender";
  $latexrender_path_http = "/phpbb/latexrender";
to reflect your paths.

In your program take the text that you wish to render. Surround any latex code
with the tags [tex]...[/tex]. Suppose this is in the variable $latextext.

For example: 
$latextext = "This is just text but [tex]\sqrt{2}[/tex] should be shown as an image and so should [tex]\frac {1}{2}[/tex]";

Call latexrender with the 2 lines:

include_once('/full_path_here_to/latexrender/latex.php'); 
$latextext=latex_content($latextext);

$latextext will now contain a link to the image in latexrender/pictures


A simple working example is given in example.php. Copy this to the latexrender directory and then
run the program. [There is also a more sophisticated demo in the demo folder - see readme.txt 
there for details]

Instructions for installation on Simple Minds Forums are included courtesy of treo and Orstio.

There's an attempt to improve vertical alignment at the cost of extra processing in the 
offset beta directory. Users may wish to experiment with this.

Notes
-----
1. latex.php will show error codes 1-6 if there's a problem. The codes are explained in latex.php
but their meaning is probably best not broadcast

2. If you use a WYSIWYG editor such as htmlArea http://www.interactivetools.com/products/htmlarea/ then 
you may need to remove some tags from the text to be converted. Uncomment the lines near the end 
of latex.php to do this.

3. The default is to use article.cls for LaTeX which is a common class but it only supports 10,11,12 point 
font sizes. For smaller (or larger fonts) in the image, install the extsizes package available from CTAN 
http://ctan.tug.org/. Add these files to a new extsizes directory in usr/share/texmf/tex/latex.
Refresh the database using "texhash" command (if using teTeX) or MiKTex Options, Refresh Now (Windows) ,
Then in class.latexrender.php you can change var $_font_size = 10; to var $_font_size = 8;

4. You can allow larger images and/or longer latex code by adjusting the default 500 in
class.latexrender.php in the lines
    var $_xsize_limit = 500;
    var $_ysize_limit = 500;
    var $_string_length_limit = 500;

5. You can make equation arrays and other code that starts with \begin, by prefacing them with 2 new lines.

6. Displayed formulae can be rendered using \displaystyle and < center > tag

7. Examples of conversions can be found in example.htm

8. Do protect programs like example.php. Opening up to everybody could soon fill up the pictures directory.
The files are small but lots of small files can take up precious space you may need.

9. Although the default size is set (see 3. above) you can resize a formula by using \mbox as in 
\mbox{\huge\sqrt{2}} or \mbox{\footnotesize\sqrt{2}}

mimeTeX
-------
If you are unable to install LaTeX and ImageMagick (& Ghostscript) on your server then you can 
try mimeTeX. This doesn't require any external programs but doesn't quite give the same standard 
of output as LaTeX but is very acceptable. Full details in the mimetex folder.

Steve Mayer mayer@dial.pipex.com
