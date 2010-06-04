#!/usr/bin/perl -w
use LaTeXRender;
use HTML::Entities;
use CGI qw(escapeHTML);
use Cwd;

print "content-type:text/html; charset=utf-8\n\n";

$formula = "\\sqrt{\\alpha}";
$p = getcwd();
$latex = new LaTeXRender( "$p/pictures", "pictures", "$p/tmp");         
print $p . "\n";

$url = $latex->getFormulaURL($formula);
print "url $url e1 " . $_errorcode . " e2 ".  $_errorextra . "\n";


1;
