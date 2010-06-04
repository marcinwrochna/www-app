Thanks to Orstio for providing these instructions for Simple Minds Forum

http://www.simplemachines.org/community/index.php?topic=12724.msg113966#msg113966
----------------------------------------------------------------------------------

If you have RC1 of the Simple Minds Forum then please use the alternative instructions 
below, also provided by Orstio.
----------------------------------------------------------------------------------

For those who do not have tetex, or as in my case, are on a server where the host has 
disabled exec(), you can also use the mimetex version.

You'll need the precompiled mimetex.cgi binary, which is not easy to come by, but I did 
find one, so I've provided a Linux version: http://www.everything-science.com/mimetex.zip  

If you need another version, go here:

http://moodle.org/download/mimetex/

right-click on the file that you need, and save as mimetex.cgi .

Upload this file to your cgi-bin and CHMOD to 755.


Upload the mimetex folder inside your Sources folder.  CHMOD the pictures folder to 777.

Edit mimetex.php to your server settings.

Try example.php .  If it works, do the mod:

in Subs.php, after line 728 add:

include_once('Sources/mimetex/mimetex.php');
$message = mimetex($message);

You can see it in action here:

http://www.everything-science.com/components/com_smf/index.php?topic=5207.msg49324

----------------------------------------------------------------------------------
RC1 
----------------------------------------------------------------------------------
Simple Machines have just upgraded to RC1, and the
upgrade breaks the MimeTex parsing.

So, I had to change a few more things to make it work.

Here are the instructions:

Step 1:

Copy the mimetex function from mimetex.php, and paste it into the SMF Subs.php
(I put it at the end, to keep it clean, but it can go anywhere, as long as it
is not inside another function).

Make sure the variables are set to your server settings.

Step 2:

Find this:

}
$message = substr(implode('', $parts), 1);

// Fix things.
$message = strtr($message, array('  ' => ' &nbsp;', "\r" => '', "\n" => '<br />'));

and change it to this:

}
$message = substr(implode('', $parts), 1);

    $message = mimetex($message);
// Fix things.
$message = strtr($message, array('  ' => ' &nbsp;', "\r" => '', "\n" => '<br />'));

Step 3:

Upload mimetex.cgi to the cgi-bin, and CHMOD to 755
Upload the modified Subs.php to the Sources folder
Upload the mimetex folder inside the Sources folder
CHMOD the pictures folder to 777


That's it; it worked after that.  The mimetex function has to be moved into the
Subs.php because the new version of SMF doesn't allow includes the same as the
previous version.  There is probably a work-around, by changing mimetex.php,
but either way, it needs to be modified or copied.
