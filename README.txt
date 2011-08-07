== Installation ==
=== Requirements ===
PHP >= 5.2 - required to support enums and database magic syntax.
PostgreSQL >= 9? - while it should be easy to modify db/postgresql.php, I only test on pg,
	so some queries might require modification if you use another DBMS.
CGI and Perl for latex and upload status bar
	(TODO give sane fallbacks for those, and maybe use some better methods -
		mimetex isn't beautiful, jsmath matured very much,
		uber-upload is uber-complicated, TinyMCE now features a HTML upload handler).
=== Configuration ===
See config.php.

== Utilities ==
=== internationalization ===
Standard gettext is used, with two additional string-sources to parse.
Strings to be translated are either:
- passed directly to the _(), N_() functions,
- enclosed in double brackets {{string}}, or
- defined in a parseTable column with a t modifier.
When you add/change a string to be translated, run respectively:
- cd locale; xgettext -k_ --from-code utf-8 -o auto.pot --no-wrap ../*.php ../*/*.php
- manually edit templates.pot
- manually edit tables.pot
TODO automate that process.
Then run  (still inside locale/, replacing pl_PL with all the langs you need)
	msgcat auto.pot tables.pot templates.pot > messages.pot
	msgmerge -U pl_PL/LC_MESSAGES/messages.po messages.pot
Then fill each language's messages.po (with an editor like poEdit).

For details see locale/README.txt

=== icons map ===
Whenever you add/edit an icon in images/icons/, run mapIcons.py (in images/).

This is to avoid making many request for all the small icons, they're merged in one file by mapIcons.py.
In code use getIcon() to display the icons.
For details see mapIcons.py.

== License ==
The code is licensed under the MIT license (without additional clauses).
As permissive as it is, if you're still unwilling to comply, I probably won't sue you, I only
expect you to kindly send an e-mail to mwrochna+www at gmail so I know my work is of some use.

PHP/HTML/JS components used:
- the WYSIWIG editor     - TinyMCE        - LGPL         - http://www.tinymce.com/
- oauth and smtp library - Zend Framework - new BSD      - http://framework.zend.com/wiki/display/ZFDEV/Subversion+Standards#SubversionStandards-AnonymousCheckout
CGI components used:
- simple latex rendering - mimetex.cgi    - GPL          - ?
- upload status bar      - uber-upload    - GPLv3        - http://uber-uploader.sourceforge.net/
Icons:
- icons                  - FatCow         - CC-A-3.0     - http://www.fatcow.com/free-icons/
- icons                  - twotiny        - Artisitc/GPL - http://code.google.com/p/twotiny/
