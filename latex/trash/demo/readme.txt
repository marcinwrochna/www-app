Demo
----

This is a more sophisticated script than example.php as it allows you to change 
variables on the page.

1. Change the paths in latex.php to suit your configuration
2. Upload the contents of the demo folder and chmod tmp and pictures to 777
3. Do protect this folder. Opening up to everybody could soon fill up the pictures directory.
The files are small but lots of small files can take up precious space you may need.

If you haven't got extsizes installed, don't try 8pt as it may give errors and certainly not
the correct result.

You will find that the errors are explained in the rendering so for example, should the 
image size be too large to display, you can change the variables accordingly.

If you want to see the effect of changing font sizes, turn off file caching (untick the Cache
File checkbox) so that the image is re-rendered.

In order to force Internet Explorer to show new image files without refreshing, a timestamp
is added to the filename.

Clicking on an image will popup a window with the latex code so it can be copied. This feature can be turned off by changing 
$popupwindow = 1; 
to 
$popupwindow = 0;

The code for copying to clipboard may only work in Internet Explorer and not always then :-(
Try replacing "%0D%0A" by "%0A"

 