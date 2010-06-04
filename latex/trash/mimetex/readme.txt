mimeTeX
-------

mimeTeX is a cgi program written by John Forkosh which will convert LaTeX code to an image.
It is complete in that it doesn't require any external programs like LaTeX or Imagemagick.
You can find full details and download the program from http://www.forkosh.dreamhost.com/mimetex.html

Follow the instructions there for compiling the program - if you cannot or do not wish to 
use the command line on your server then the site provides pre-compiled versions.

You should put mimetex.cgi in your cgi-bin directory and chmod mimetex.cgi to 755 (Linux).

PhpBB
-----
See README_phpBB.txt in this folder

Simple Minds SMF
----------------
See README_SMF.txt in this folder

Other programs
--------------
For other PHP programs create a new folder called mimetex and inside this copy
a) mimetex.php
b) an empty subfolders /pictures which must be writeable by the scripts so may need to be 
chmod 777

In mimetex.php change the lines
	$mimetex_path = "/home/domain_name/public_html/cgi-bin/mimetex.cgi";
	$mimetex_path_http = "http://domain_name/mimetex";
	$mimetex_cgi_path_http="http://domain_name/cgi-bin/mimetex.cgi";
	$pictures_path = "/home/domain_name/public_html/mimetex/pictures";
to reflect your paths.

In your program take the text that you wish to render. Surround any latex code
with the tags [tex]...[/tex]. Suppose this is in the variable $latextext.

For example: 
$latextext = "This is just text but [tex]\sqrt{2}[/tex] should be shown as an image and so should [tex]\frac {1}{2}[/tex]";

Call mimetex with the 2 lines:

include_once('/full_path_here_to/mimetex/mimetex.php'); 
$latextext=mimetex($latextext);

$latextext will now contain a link to the image in mimetex/pictures

A simple working example is given in example.php. Copy this to the mimetex directory and then
run the program.

Problem
-------
If you get an error message similar to
  Warning: system() has been disabled for security reasons in /your.path/mimetex.php on line 31 
  Output lines = 0 
then your host won't let you use system()

Midgard has provided a workaround:

In mimetex.php and phpbb_hook_2.php change
	$system_disabled=0;
to
	$system_disabled=1;

As Midgard says, it will be a little slower and produces more traffic, but it should work.

Note
----
If you find the font too small then you can change the size. A mixture (or just one) of the following options are available: 
1. let the users decide by putting \fsn (where n is an integer between 0 and 5) 
before the latex code eg \fs4 \sqrt{2} 
and/or
2. change the line in mimetex.php
$mimetex_formula = tex_matches[1][$i];
to
$mimetex_formula = "n$".$tex_matches[1][$i];
** replacing n ** by an integer from 0 to 5.
and/or
3. compile mimetex with -DNORMALSIZE=n option (n an integer from 0 to 5). See manual at
http://www.forkosh.dreamhost.com/mimetexmanual.html#options)

The default is for the size to correspond to n=2

Steve Mayer mayer@dial.pipex.com