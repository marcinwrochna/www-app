package LaTeXRender;

# LaTeX Rendering Package
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
# @package LaTeXRender

use Cwd;
use CGI::Carp;
use File::Copy;
use Digest::MD5 qw(md5_hex);

require Exporter;

our @ISA    = ("Exporter");
our @EXPORT = qw(setPicturePath getPicturePath setPicturePathHTTPD
  getPicturePathHTTPD getFormulaURL $_errorcode $_errorextra);

# ====================================================================================
# Variable Definitions
# ====================================================================================
our $_picture_path       = "pictures";
our $_picture_path_httpd = "pictures";
our $_tmp_dir            = "tmp";

# i was too lazy to write mutator functions for every single program used
# just access it outside the class or change it here if necessary
our $_latex_path          = "/usr/bin/latex";
our $_dvips_path          = "/usr/bin/dvips";
our $_convert_path        = "/usr/bin/convert";
our $_identify_path       = "/usr/bin/identify";
our $_formula_density     = 120;
our $_xsize_limit         = 500;
our $_ysize_limit         = 500;
our $_string_length_limit = 500;
our $_font_size           = 10;
our $_latexclass          =
  "article";   # install extarticle class if you wish to have smaller font sizes
our $_tmp_filename;
our $_image_format = "gif";    # change to png if you prefer

# this most certainly needs to be extended. in the long term it is planned to use
# a positive list for more security. this is hopefully enough for now. i'd be glad
# to receive more bad tags !
our @_latex_tags_blacklist = (
    "include",           "def",
    "command",           "loop",
    "repeat",            "open",
    "toks",              "output",
    "input",             "catcode",
    "name",              "\\^\\^",
    "\\every",           "\\errhelp",
    "\\errorstopmode",   "\\scrollmode",
    "\\nonstopmode",     "\\batchmode",
    "\\read",            "\\write",
    "csname",            "\\newhelp",
    "\\uppercase",       "\\lowercase",
    "\\relax",           "\\aftergroup",
    "\\afterassignment", "\\expandafter",
    "\\noexpand",        "\\special"
);
our $_errorcode  = 0;
our $_errorextra = "";
our $_RANDMAX    = 500;

# ====================================================================================
# constructor
# ====================================================================================

#
# Initializes the class
#
# @param string path where the rendered pictures should be stored
# @param string same path, but from the httpd chroot
# @param string directory for storage of temporary files

sub new {
    my $invocant = shift;
    my $class = ref($invocant) || $invocant;    # Object or class name
    our $self = {};
    bless( $self, $class );
    $_picture_path       = shift;
    $_picture_path_httpd = shift;
    $_tmp_dir            = shift;
    $_tmp_filename       = md5_hex( int( rand $_RANDMAX ) );
    return $self;
}

# ====================================================================================
# public functions
# ====================================================================================

#
# Picture path Mutator function
#
# @param string sets the current picture path to a new location
#

sub setPicturePath {
    $_picture_path = shift;
}

#
# Picture path Accessor function
#
# @returns the current picture path
#
sub getPicturePath() {
    return $_picture_path;
}

#
# Picture path HTTPD Mutator function
#
# @param string sets the current httpd picture path to a new location
#

sub setPicturePathHTTPD {
    $_picture_path_httpd = shift;
}

#
# Picture path HTTPD Accessor function
#
# @returns the current picture path
#

sub getPicturePathHTTPD() {
    return $_picture_path_httpd;
}

#
# Tries to match the LaTeX Formula given as argument against the
# formula cache. If the picture has not been rendered before, it'll
# try to render the formula and drop it in the picture cache directory.
#
# @param string formula in LaTeX format
# @returns the webserver based URL to a picture which contains the
# requested LaTeX formula. If anything fails, the resultvalue is false.
#

sub getFormulaURL {
    my ( $self, $latex_formula ) = ( shift, shift );

    # circumvent certain security functions of web-software which
    # is pretty pointless right here

    $latex_formula =~ s/&gt;/>/i;
    $latex_formula =~ s/&lt;/</i;

    my $formula_hash = md5_hex($latex_formula);

    my $filename           = "$formula_hash.$_image_format";
    my $full_path_filename = getPicturePath() . "/$filename";

    if ( -f $full_path_filename ) {
        return getPicturePathHTTPD() . "/$filename";
    }
    else {

        # security filter: reject too long formulas
        if ( length($latex_formula) > $_string_length_limit ) {
            $_errorcode  = 1;
            $_errorextra = "This formula is too long.";
            return "";
        }

        # security filter: try to match against LaTeX-Tags Blacklist
        for ( my $i = 0 ; $i < @_latex_tags_blacklist ; $i++ ) {
            if ( $latex_formula =~ m/$_latex_tags_blacklist[$i]/ ) {
                $_errorcode  = 2;
                $_errorextra = "Includes a blacklisted tag.";
                return "";
            }
        }

        # security checks assume correct formula, let's render it
        if ( renderLatex($latex_formula) ) {
            return getPicturePathHTTPD() . "/$filename";
        }
        else {

            # uncomment if required
            # $_errorcode = 3;
            # $_errorextra = "Latex rendering failed";
            return "";
        }
    }
}

# ====================================================================================
# private functions
# ====================================================================================

#
# wraps a minimalistic LaTeX document around the formula and returns a string
# containing the whole document as string. Customize if you want other fonts for
# example.
#
# @param string formula in LaTeX format
# @returns minimalistic LaTeX document containing the given formula
#

sub wrap_formula {
    my $latex_formula = shift;
    my $string        = "\\documentclass[$_font_size" . "pt]{$_latexclass}\n";
    $string .= "\\usepackage[latin1]{inputenc}\n";
    $string .= "\\usepackage{amsmath}\n";
    $string .= "\\usepackage{amsfonts}\n";
    $string .= "\\usepackage{amssymb}\n";
    $string .= "\\pagestyle{empty}\n";
    $string .= "\\begin{document}\n";
    $string .= "\$" . $latex_formula . "\$\n";
    $string .= "\\end{document}\n";

    return $string;
}

#
# returns the dimensions of a picture file using 'identify' of the
# imagemagick tools. The resulting array can be adressed with either
# $dim[0] / $dim[1] or $dim["x"] / $dim["y"]
#
# @param string path to a picture
# @returns array containing the picture dimensions
#

sub getDimensions {
    my $filename = shift;
    my $output   = `$_identify_path $filename`;
    my @result   = split / /, $output;
    my @dim      = split /x/, $result[2];
    $dim[1] = int( $dim[1] );
    return @dim;
}

#
# Renders a LaTeX formula by the using the following method:
#  - write the formula into a wrapped tex-file in a temporary directory
#    and change to it
#  - Create a DVI file using latex (tetex)
#  - Convert DVI file to Postscript (PS) using dvips (tetex)
#  - convert, trim and add transparancy by using 'convert' from the
#    imagemagick package.
#  - Save the resulting image to the picture cache directory using an
#    md5 hash as filename. Already rendered formulas can be found directly
#    this way.
#
# @param string LaTeX formula
# @returns true if the picture has been successfully saved to the picture
#          cache directory
#

sub renderLatex {
    my $latex_formula  = shift;
    my $latex_document = wrap_formula($latex_formula);

    my $current_dir = getcwd();

    chdir $_tmp_dir;

    # create temporary latex file
    open( my $fp, ">$_tmp_filename.tex" );
    print $fp ($latex_document);
    close $fp;

    # create temporary dvi file
    my $command = "$_latex_path --interaction=nonstopmode $_tmp_filename.tex";
    my $status_code = system("$command > /dev/null 2>&1");

    if ($status_code) {
        cleanTemporaryDirectory();
        chdir($current_dir);
        $_errorcode  = 4;
        $_errorextra =
          "problem running latex (syntax error?), so dvi file not created\n$_tmp_filename\n$command\n";
        return "";
    }

    # convert dvi file to postscript using dvips
    $command     = "$_dvips_path -E $_tmp_filename.dvi -o $_tmp_filename.ps";
    $status_code = system("$command > /dev/null 2>&1");

    # imagemagick convert ps to image and trim picture
    $command =
"$_convert_path -density $_formula_density -trim -transparent \"#FFFFFF\" "
      . "$_tmp_filename.ps $_tmp_filename.$_image_format";
    $status_code = system("$command > /dev/null 2>&1");

    # test picture for correct dimensions
    my @dim = getDimensions("$_tmp_filename.$_image_format");

    if ( ( $dim[0] > $_xsize_limit ) or ( $dim[1] > $_ysize_limit ) ) {
        cleanTemporaryDirectory();
        chdir($current_dir);
        $_errorcode  = 5;
        $_errorextra = "resulting image too large : " . $dim[0] . "x" . $dim[1];
        return "";
    }

    # copy temporary formula file to cahed formula directory
    my $latex_hash = md5_hex($latex_formula);
    my $filename   = getPicturePath() . "/$latex_hash.$_image_format";

    $status_code = copy( "$_tmp_filename.$_image_format", $filename );

    cleanTemporaryDirectory();

    if ( !$status_code ) {
        chdir($current_dir);
        $_errorcode = 6;
        $_erroextra = "Cannot copy image to pictures directory";
        return "";
    }

    chdir($current_dir);

    return 1;
}

#
# Cleans the temporary directory
#

sub cleanTemporaryDirectory {
    my $current_dir = getcwd();
    chdir($_tmp_dir);

    unlink("$_tmp_dir/$_tmp_filename.tex");
    unlink("$_tmp_dir/$_tmp_filename.aux");
    unlink("$_tmp_dir/$_tmp_filename.log");
    unlink("$_tmp_dir/$_tmp_filename.dvi");
    unlink("$_tmp_dir/$_tmp_filename.ps");
    unlink("$_tmp_dir/$_tmp_filename.$_image_format");

    chdir($current_dir);
}

1;
