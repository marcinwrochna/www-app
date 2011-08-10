<?php
/* Usage: cd locale/; php updateTranslations.php;
 * (Re)creates the messages.pot file and updates/creates all .po files.
 */

$outputFile= 'messages.pot';
$languages = array('pl_PL','de_DE');

require_once('../utils.php');

function addGettextString($s, $column = null, $row = null)
{
	global $filename, $line, $poData, $count;
	$comment = 'template';
	if (!is_null($column))
		$comment = "column:'$column' row:'$row'";
	$poData[$s][] = "#: $filename:$line $comment\n";
	$count++;
}

chdir('..');
$poData = array();
$dirs = array('', 'user/', 'database/');
$filenames = array();
$total = 0;
foreach ($dirs as $dir)  foreach (glob($dir .'*.php') as $filename)
{
	$filenames[] = $filename;
	$file = file_get_contents($filename);
	$length = strlen($file);
	printf("Reading %20s size %9d:\t\t", $filename, $length);
	$count = 0;

	/* ParseTable columns with t modifier. */
	$offset = 0;
	$line = 0;
	$lineOffset = 0;
	$funcName = 'parse'.'Table';
	while (($offset = strpos($file, $funcName .'(', $offset)) !== false)
	{
		while ($lineOffset < $offset)
		{
			$line++;
			$lineOffset = strpos($file, "\n", $lineOffset+1);
		}

		$offset += strlen($funcName .'(');
		if (substr($file, $offset, 1) == '$')
			break;
		// Let $p = start of table, $q = afterend of table (table = text between "" or '', handling \').
		$p  = strpos($file, '\'', $offset); if (!$p) $p = $length;
		$p2 = strpos($file, '"',  $offset); if (!$p2) $p2 = $length;
		if ($p < $p2)
		{
			$p++;
			$q = strpos($file, '\'', $p);
			if (!$q)
					die("Unmatched quote $filename $offset $p..?:\n". substr($file,$offset));
			while (substr($file, $q - 1, 1) === '\\')
			{
				if (!$q)
					die("Unmatched quote $filename $offset $p..?:\n". substr($file,$offset));
				$q = strpos($file, '\'', $q + 1);
			}
		}
		else
		{
			$p = $p2 + 1;
			$q = strpos($file, '"', $p);
			if (!$q)
				die("Unmatched quote in $filename:$offset $p..?.\n". substr($file,$offset));
		}
		$table = stripslashes(substr($file, $p, $q - $p));
		//echo "Got table at $filename char $offset($p,$q):\n--------------------\n$table\n----------------\n";
		parseTable($table, 'addGettextString');
	}

	$countTable = $count;
	$count = 0;

	/* Search in templates (quite carelessly assume noone else uses '{{' and '}}' delimiters). */
	if (strpos($filename, 'template') === false)
	{
		$openDelimiter = '{{';
		$closeDelimiter = '}}';
		$offset = 0;
		$line = 0;
		$lineOffset = 0;
		while (($offset = strpos($file, $openDelimiter, $offset)) !== false)
		{
			// Let $offset = start of delimited string, $q = afterend of it.
			$offset += strlen($openDelimiter);
			while ($lineOffset < $offset)
			{
				$line++;
				$lineOffset = strpos($file, "\n", $lineOffset+1);
			}
			$q = strpos($file, $closeDelimiter, $offset+1);
			if (!$q)
				die("Unmatched delimiter in $filename:$offset");
			addGettextString(substr($file, $offset, $q - $offset));
		}
	}

	printf("%3d table and %d template strings found.\n", $countTable, $count);
	$total += $countTable + $count;
}


$filenames = implode(' ', $filenames);
// xgettext should be essentially equal to what the table search would do with $funcname='_';
echo `xgettext -k_ --from-code utf-8 -o locale/tmp1.pot --no-wrap $filenames`;

chdir('locale/');
echo `cp head.pot tmp2.pot`;
$poOutput = '';
foreach ($poData as $s => $comments)
{
	$poOutput .= implode('', $comments);
	$poOutput .= "msgid \"". addcslashes($s, "\"\\\n\t\x0..\x1F\x7F..\xFF") ."\"\n";
	$poOutput .= "msgstr \"\"\n\n";
}
file_put_contents('tmp2.pot', $poOutput, FILE_APPEND);
echo `msgcat tmp1.pot tmp2.pot > $outputFile`;
echo `rm tmp1.pot tmp2.pot`;
echo "Total: $total string found.\n";

foreach ($languages as $lang)
{
	$ll = substr($lang, 0, 2);
	if (!is_dir($lang))
		mkdir($lang);
	if (!is_dir("$lang/LC_MESSAGES"))
		mkdir("$lang/LC_MESSAGES");
	if (!is_file("$lang/LC_MESSAGES/messages.po"))
		echo `msginit -l $ll -o $lang/LC_MESSAGES/messages.po -i messages.pot`;
	else
		echo `msgmerge       -U $lang/LC_MESSAGES/messages.po    messages.pot`;
	echo "locale/$lang/LC_MESSAGES/messages.po updated - fill in missing translations with a .po editor.\n";
}
