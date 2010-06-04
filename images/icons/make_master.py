#!/usr/bin/python

# for f in *.png *.gif ; do echo `echo $f | tr \. \_ `:$f; done > icon_map.txt
# This work is licensed under the Creative Commons Attribution 3.0 United 
# States License. To view a copy of this license, visit 
# http://creativecommons.org/licenses/by/3.0/us/ or send a letter to Creative
# Commons, 171 Second Street, Suite 300, San Francisco, California, 94105, USA.

import os
import Image
import re

iconMap=[]
for filename in os.listdir('.'):
	m=re.match('(.*)\.(gif|png)',filename)
	if m and m.group(1)!='master':
		iconMap.append([m.group(1)+'_'+m.group(2),m.group(0)])
iconMap = sorted(iconMap)
print iconMap

images = [Image.open(filename) for cssClass, filename in iconMap]

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

print "saving master.gif...",
master.save('master.gif', transparency=0 )
print "saved!"

print "saving master.png...",
master.save('master.png')
print "saved!"


cssTemplate = '''.%s{background-position:0 %dpx;width:%dpx;height:%dpx;}
'''

for format in ['png','gif']:
	print 'saving icons_%s.css...' % format,
	iconCssFile = open('icons_%s.css' % format ,'w')
	iconCssFile.write('''.icon{background-image:url(master.%s);}'''%(format))
	for count, pair in enumerate(iconMap):
		cssClass, filename = pair
		w,h=images[count].size 
		location = image_height*count*2
		iconCssFile.write( cssTemplate % (cssClass, -location,w,h) )
	iconCssFile.close()
	print 'created!'

