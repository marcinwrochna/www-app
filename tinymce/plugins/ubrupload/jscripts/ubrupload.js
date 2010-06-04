var UbrUploadDialog = {
	
init : function(ed) {
	var formObj = document.forms.ubruploaderform;
	var val = tinyMCEPopup.getWindowArg('value');	
	var lrurl = tinyMCEPopup.getWindowArg('lrurl');
	
	//formObj.formula.innerHTML  = val;
	//formObj.insert.value = tinyMCEPopup.getLang('ubrupload.' + tinyMCEPopup.getWindowArg('mceDo'),'Insert',true);
	formObj.lrurl.value = lrurl;
	
	//tinyMCEPopup.resizeToInnerSize();
},

updatePreview : function () {
	var formObj = document.forms[0];
	var lrurl = formObj.lrurl.value;
	
	if(lrurl && formObj.formula.value) {
		formObj.preview.src = lrurl+'?'+formObj.formula.value;
		formObj.preview.style.display='inline';
	}
	else {
		formObj.preview.style.display='none';
	}
},

insertUpload : function () {
	var ed = tinyMCEPopup.editor, dom = ed.dom;
	var formObj = document.forms[0];
	var value   = '';//formObj.formula.value;
	var lrurl = formObj.lrurl.value;
	var html = '';
	
	value = value.replace("\"", "&#34;");

	html = '<a href="?">aaa</a>';
	/*html = dom.createHTML('s', {
		href : lrurl + '?' + value,
		'class': 'mceUbrUpload'		
	});*/
	//html = '<img class="latexEquation" align="absmiddle" src="'+lrurl+'?'+value+'" alt="latex"/>';

	tinyMCEPopup.execCommand("mceInsertRawHTML", false, html);
	tinyMCEPopup.close();
},

cancelAction : function() {
	tinyMCEPopup.close();
}

};

tinyMCEPopup.onInit.add(UbrUploadDialog.init, UbrUploadDialog);
