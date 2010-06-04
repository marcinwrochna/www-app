#!/bin/sh 
#
# Shell script to create the menu items for the LaTeX editor
#
# Copyright (c) 2005 David Hausheer
# 
# This script is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
# 
# This script is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# 
# You should have received a copy of the GNU General Public License
# along with this script; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA 
#
# usage: ./lssym.sh lssym.txt

outpath="$HOME/www/latexeditor"
outfile="menu_items.js"
menu=""
height=0; width=0; counter=0; files=""; items=""
menucounter=0

###################################
 
editmap () {
first=0
while read html; do
case "$html" in
*coords*) if test $1 != "_blank_"; then coords=$(echo "$html" | sed -e "s/.*coords=\"\(.*\)\".*/\1/g"); latexcode=$(echo "$1" | sed -e "s/\(.*{\).*\(}\)/\1 \2/g" | awk '{print "'\''"$1"'\'','\''"$2"'\''"}'); echo ","; echo -n "[$latexcode,'$coords']"; fi; shift ;;
esac
done
}

###################################

menuitem () {
if test "$menu"; then
echo $menu $height $width $counter
let menucounter=$menucounter+1

longmenu=$(echo "$menu" | sed -e 's/Menuitem //g')
compmenu=$(echo "$longmenu" | sed -e 's/ /_/g')

if test $menucounter -gt 1; then
echo "," >> $outpath/$outfile
fi
echo -n "['$longmenu'" >> $outpath/$outfile

for ((i=1; $i<=$counter; i=$i+1)); do files="$files $i.eps"; done
cd /tmp; /usr/bin/montage -density 300 -geometry 20x20+2+2 -tile $width"x"$height $files $compmenu.html
items=$(echo $items | sed -e 's/\\/\\\\/g' | sed -e 's/\//\\\//g' | sed -e "s/'/\\\'/g")

cat $compmenu""_map.shtml | editmap $items >> $outpath/$outfile

echo -n "]" >> $outpath/$outfile

mv $compmenu.gif $outpath/menus/
rm $compmenu""_map.shtml $compmenu.html $files
fi
menu=$1
height=0; width=0; counter=0; files=""; items=""
}

###################################
 
symbols () {
if test $# -gt 0; then

let height=height+1
if test $# -gt $width; then let width=$#; fi

for sym in $*; do
let counter=counter+1; items="$items $sym";
sym=$(echo $sym | sed -e 's/_blank_//g')
texinput="\documentclass[fleqn]{article} \usepackage{amssymb,amsmath} \usepackage[latin1]{inputenc} \begin{document} \thispagestyle{empty} \mathindent0cm \parindent0cm \begin{displaymath} $sym \end{displaymath} \end{document}"
echo $texinput > /tmp/symbol.tex
cd /tmp; /usr/bin/latex -interaction=nonstopmode /tmp/symbol > /dev/null 2>&1
/usr/bin/dvips -q -E /tmp/symbol.dvi -o /tmp/$counter.eps
done 

fi
}
 
###################################

readinput () {
while read next; do
case "$next" in 
Menuitem*) menuitem "$next" ;;
*) symbols $next
esac
done
menuitem
rm -rf /tmp/symbol* /tmp/magic*
}

###################################

echo "var MENU_ITEMS = [" > $outpath/$outfile 

cat -r $1 | readinput

echo "];" >> $outpath/$outfile

