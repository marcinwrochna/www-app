function insert(aTag, eTag) {
  var input = document.forms['latex2png'].elements['source'];
  input.focus();
  /* Internet Explorer */
  if(typeof document.selection != 'undefined') {
    /* Inserting code */
    var range = document.selection.createRange();
    var insText = range.text;
    range.text = aTag + insText + eTag;
    /* Change cursor position */
    range = document.selection.createRange();
    if (insText.length == 0) {
      range.move('character', -eTag.length);
    } else {
      range.moveStart('character', aTag.length + insText.length + eTag.length);      
    }
    range.select();
  }
  /* Gecko-based browsers */
  else if(typeof input.selectionStart != 'undefined')
  {
    /* Inserting Code */
    var start = input.selectionStart;
    var end = input.selectionEnd;
    var insText = input.value.substring(start, end);
    input.value = input.value.substr(0, start) + aTag + insText + eTag + input.value.substr(end);
    /* Change cursor position */
    var pos;
    if (insText.length == 0) {
      pos = start + aTag.length;
    } else {
      pos = start + aTag.length + insText.length + eTag.length;
    }
    input.selectionStart = pos;
    input.selectionEnd = pos;
  }
  /* Other browsers */
  else
  {
    alert("Sorry, your browser does not support this function");
  }
}

function nop()
{
} 

function menu_collapse(eid) {
  clearTimeout(this.showtimer);   
  document.getElementById(eid).style.visibility = 'hidden';
  if (!this.isactive)
    document.getElementById('Latexmenu').style.visibility = 'hidden';
}

function menu_expand(eid) {
  if (this.hidetimer)
    return;
  if (this.lastopened && this.lastopened != 'Latexmenu')
    menu_collapse(this.lastopened);
  this.lastopened=eid;
  document.getElementById(eid).style.visibility = 'visible';
}

function menu_onmouseout(eid) {
  this.isactive=false;
  this.hidetimer=setTimeout("menu_collapse('"+eid+"')", 200);  
}

function menu_onmouseover(eid) {
  this.isactive=true;
  clearTimeout(this.hidetimer);
  this.hidetimer = null;
  clearTimeout(this.showtimer);
  this.showtimer=setTimeout("menu_expand('"+eid+"')", 200);
}

function menu(ITEMS) {
  menucounter=0; menuheight=14; menuwidth=180; addlength=9;
  if (navigator.appName.indexOf("Explorer") != -1) addlength=1;
  document.write('<div style="position:absolute; top:5px; left:510px"><a id="Menu_Latexmenu" href="javascript:nop()" style="position:absolute; top:0px; left:0px; background:#255B73; color:#ffffff; padding:4px; width:100px; height:'+menuheight+'px" onmouseover="menu_onmouseover(\'Latexmenu\')" onmouseout="menu_onmouseout(\'Latexmenu\')">Latex Symbols</a><div id="Latexmenu" style="position:absolute; top:'+(menuheight+9)+'px; visibility:hidden">');
  for(i=0;i<ITEMS.length;i++) {
    menucounter++;
    leftoffset=(menucounter-1)%2*(menuwidth+addlength);
    topoffset=Math.floor((menucounter-1)/2)*(menuheight+9);
    menutopoffset=topoffset+menuheight+9;
    menuleftoffset=leftoffset+20;

    longmenu=ITEMS[i][0];
    compmenu=longmenu.replace(/\s/g,"_");
    document.write('<a id="Menu_'+compmenu+'" href="javascript:nop()" style="position:absolute; top:'+topoffset+'px; left:'+leftoffset+'px; width:'+menuwidth+'px; height:'+menuheight+'px; background:#255B73; color:#ffffff; padding:4px; z-index:1" onmouseover="menu_onmouseover(\''+compmenu+'\')" onmouseout="menu_onmouseout(\''+compmenu+'\')">'+longmenu+'</a>');
    document.write('<div id="'+compmenu+'" style="position:absolute; top:'+menutopoffset+'px; left:'+menuleftoffset+'px; visibility:hidden; z-index:2" onmouseover="menu_onmouseover(\''+compmenu+'\')" onmouseout="menu_onmouseout(\''+compmenu+'\')">');
    document.write('<map name="'+compmenu+'"');
    for(j=1;j<ITEMS[i].length;j++) {
      mapitem=ITEMS[i][j];
      mapitem[0]=mapitem[0].replace(/\\/,"\\\\");
      mapitem[0]=mapitem[0].replace(/\'/,"\\\'");
      document.write('<area href="javascript:insert(\''+mapitem[0]+'\',\''+mapitem[1]+'\')" shape="rect" coords="'+mapitem[2]+'" alt=""/>');
    }
    document.write('</map>');
    document.write('<img src="menus/'+compmenu+'.gif" alt="'+longmenu+'" usemap="#'+compmenu+'" style="background:#ffffff; border: 1px solid #255B73"/>');
    document.write('</div>');
  }
  document.write('</div></div>');
}
