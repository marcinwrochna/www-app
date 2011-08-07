#!/usr/bin/python
# Run this script to merge all (.gif or .png) icons from icons/ into one .png.
# This is to avoid making many request when loading all the small icons.
#
# Files icons.png and icons_png.css will be created.
# Include the css in your html and instead of background and width/height use 
# <span class='iconname_png icon'></span> for icons/iconname.png

# This work is licensed under the Creative Commons Attribution 3.0 United 
# States License. To view a copy of this license, visit 
# http://creativecommons.org/licenses/by/3.0/us/ or send a letter to Creative
# Commons, 171 Second Street, Suite 300, San Francisco, California, 94105, USA.

import os
import Image
import re
import time

iconDir='icons/'
iconMap=[]
for filename in os.listdir(iconDir):
	m=re.match('(.*)\.(gif|png)',filename)
	if m and m.group(1)!='master':
		iconMap.append([m.group(1)+'_'+m.group(2),m.group(0)])
iconMap = sorted(iconMap)
print iconMap

images = [Image.open(iconDir + filename) for cssClass, filename in iconMap]

print "%d images will be combined." % len(images)

image_width, image_height = max(im.size for im in images)

print "all images assumed to be %d by %d." % (image_width, image_height)

master_width = image_width
#seperate each image with lots of whitespace
master_height = (image_height * len(images) * 2) - image_height
print "the master image will by %d by %d" % (master_width, master_height)
print "creating image...",
master = Image.new(
    mode='RGBA',
    size=(master_width, master_height),
    color=(0,0,0,0))  # fully transparent

print "created."

for count, image in enumerate(images):
    location = image_height*count*2
    print "adding %s at %d..." % (iconMap[count][1], location),
    master.paste(image,(0,location))
    print "added."
print "done adding icons."

print "saving icons.gif...",
master.save('icons.gif', transparency=0 )
print "saved!"

print "saving icons.png...",
master.save('icons.png')
print "saved!"


cssTemplate = '''.%s{background-position:0 %dpx;width:%dpx;height:%dpx;}
'''

for format in ['png','gif']:
	print 'saving icons_%s.css...' % format,
	iconCssFile = open('icons_%s.css' % format ,'w')
	iconCssFile.write('''.icon{background-image:url(icons.%s?%f);}'''%(format,time.time()))
	for count, pair in enumerate(iconMap):
		cssClass, filename = pair
		w,h=images[count].size 
		location = image_height*count*2
		iconCssFile.write( cssTemplate % (cssClass, -location,w,h) )
	iconCssFile.close()
	print 'created!'


