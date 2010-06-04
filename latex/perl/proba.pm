use LaTeXRender;
use HTML::Entities;
use CGI qw(escapeHTML);

# LaTeX Rendering Class - Calling function
# Copyright (C) 2003  Benjamin Zeiss <zeiss@math.uni-goettingen.de>
# Ported to Perl by Alex Gittens (swiftset@imap.cc)
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
# --------------------------------------------------------------------
# @author Benjamin Zeiss <zeiss@math.uni-goettingen.de>
# @version v0.8
# @package latexrender
# Revised by Steve Mayer
# Ported to Perl by Alex Gittens
# This file can be included in many Perl programs by using something like (see test.pl to see how it can be used)
# 		require latex;
# 		$text_to_be_converted=latex_content($text_to_be_converted);
# $text_to_be_converted will then contain the link to the appropriate image
# or an error code as follows (the 500 values can be altered in LaTeXRender.pm):
# 	0 OK
# 	1 Formula longer than 500 characters
# 	2 Includes a blacklisted tag
# 	3 (Not used) Latex rendering failed
# 	4 Cannot create DVI file
# 	5 Picture larger than 500 x 500 followed by x x y dimensions
# 	6 Cannot copy image to pictures directory

sub latex_content {
    my $text = shift;

# --------------------------------------------------------------------------------------------------
# adjust this to match your system configuration: e.g.
# my $latexrender_path = "/home/domain_name/public_html/latexrender";
    my $latexrender_path      = "/var/www/html/www/www/latex/perl/latexrender";
    my $latexrender_path_http = "/www/www/latex/perl/latexrender";

# --------------------------------------------------------------------------------------------------

    my $latex =
      new LaTeXRender( "$latexrender_path/pictures",
        "$latexrender_path_http/pictures",
        "$latexrender_path/tmp" );

        my $latex_formula = "\\sqrt{2}";

        $latex_formula = decode_entities($latex_formula);

        my $url = $latex->getFormulaURL($latex_formula);

        my $alt_latex_formula =
          escapeHTML($latex_formula)
          ;    # need to especially handle quote conversion!
        $alt_latex_formula =~ s/\\r/&#13;/g;
        $alt_latex_formula =~ s/\\n/&#10;/g;

        if ($url) {
            $text =
                "$left <img src='$url' title='$alt_latex_formula' "
              . "alt='$alt_latex_formula' align='absmiddle'> $right";
        }
        else {
            $text =
                "$left [Unparseable or potentially dangerous latex formula. "
              . "Error "
              . $_errorcode . " "
              . $_errorextra
              . "] $right";
        }
    }

    return $text;
}

1;
