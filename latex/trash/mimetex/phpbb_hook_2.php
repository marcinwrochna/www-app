<?php
/**
 * LaTeX Rendering Class - PHPBB Hook
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
 * @package latexrender
 * Adapted for use with mimetex
 */
		// --------------------------------------------------------------------------------------------------
		// adjust this to match your system configuration
		$mimetex_path = "/home/domain_name/public_html/cgi-bin/mimetex.cgi";
		$mimetex_path_http = "http://domain_name/mimetex";
		$mimetex_cgi_path_http="http://domain_name/cgi-bin/mimetex.cgi";
		$pictures_path = "/home/domain_name/public_html/mimetex/pictures";
		// --------------------------------------------------------------------------------------------------

		// change $system_disabled to 1 if you get an error message similar to
		// Warning: system() has been disabled for security reasons
		$system_disabled=0;

		$pictures_path_http = $mimetex_path_http."/pictures";

            preg_match_all("#\[tex:$uid\](.*?)\[/tex:$uid\]#si",$text,$tex_matches);

        for ($i=0; $i < count($tex_matches[0]); $i++) {
			$pos = strpos($text, $tex_matches[0][$i]);
			$mimetex_formula = html_entity_decode($tex_matches[1][$i]);

		    $formula_hash = md5($mimetex_formula);

			$filename = $formula_hash.".gif";
			$full_path_filename = $pictures_path."/".$filename;

			if (is_file($full_path_filename)) {
				$url = $pictures_path_http."/".$filename;
			} else {
				$command = "$mimetex_path -e ".$full_path_filename." ".escapeshellarg($mimetex_formula);

			if ($system_disabled==0) {
				system($command,$status_code);
			} else {
				$status_code=0;
			}

				if ($status_code != 0) {
					$url=false;
				} else {
					$url = $pictures_path_http."/".$filename;
				}
			}

			$alt_mimetex_formula = htmlentities($mimetex_formula, ENT_QUOTES);
			$alt_mimetex_formula = str_replace("\r","&#13;",$alt_mimetex_formula);
			$alt_mimetex_formula = str_replace("\n","&#10;",$alt_mimetex_formula);

			if ($url != false) {
				if ($system_disabled==0) {
					$text = substr_replace($text, "<img src='".$url."' title='".$alt_mimetex_formula."' alt='".$alt_mimetex_formula."' align=absmiddle>",$pos,strlen($tex_matches[0][$i]));
				} else {
					$text = substr_replace($text, "<img src='".$mimetex_cgi_path_http."?".$mimetex_formula."' title='".$alt_mimetex_formula."' alt='".$alt_mimetex_formula."' align=absmiddle>",$pos,strlen($tex_matches[0][$i]));
				}
			} else {
				$text = substr_replace($text, "[Mimetex cannot convert this formula]",$pos,strlen($tex_matches[0][$i]));
			}
		}

?>
