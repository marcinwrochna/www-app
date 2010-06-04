this documentation and the port itself are meant to be distributed with the original LaTeXRender.

instructions for installing/using port of LaTeXRender to Perl:

-edit the variables locating the programs used, at the top of LaTeXRender.pm

-if using the latex.pm file, change the marked variables to appropriate values
 and create a writable pictures and tmp directory under the latexrender path
 NOTE: latex.pm requires HTML::Entities

-either use the LaTeXRender package or the latex.pm package

to use the latex.pm package, require it, and then call
latex_content($text) to replace all occurences of [tex]...[/tex] with links to the appropriate images

the test.pl file can be used to test for correct installation.

Alex Gittens (swiftset@imap.cc)
