function init() {
	tinyMCEPopup.resizeToInnerSize();

	var formObj = document.forms[0];
	var val = tinyMCE.getWindowArg('value');
	var lrurl = tinyMCE.getWindowArg('lrurl');
	
	formObj.formula.innerHTML  = val;
	formObj.insert.value = tinyMCE.getLang('lang_' + tinyMCE.getWindowArg('mceDo'),'Insert',true);
	formObj.lrurl.value = lrurl;
	if(!lrurl) {
		document.getElementById("preview_fieldset").style.display = 'none';
		document.getElementById("preview_row").style.display = 'none';
	}
	else {
		if(formObj.formula.value) {
			formObj.preview.src = lrurl+'?'+formObj.formula.value;
			formObj.preview.style.display='inline';
		}
		else
			formObj.preview.style.display='none';
	}
}

function updatePreview() {
	var formObj = document.forms[0];
	var lrurl = formObj.lrurl.value;
	
	if(lrurl && formObj.formula.value) {
		formObj.preview.src = lrurl+'?'+formObj.formula.value;
		formObj.preview.style.display='inline';
	}
	else
		formObj.preview.style.display='none';
}

function insertLatex() {
	var formObj = document.forms[0];
	var value   = formObj.formula.value;
	var lrurl = formObj.lrurl.value;
	var html = '';
	
	value = value.replace("\"", "&#34;");

	if(lrurl && value)
		html = '<img class="mceItemLatex" src="'+lrurl+'?'+value+'" />';
	else { 
		if(value)
			html = '[tex]'+value+'[/tex]';
		else
			html = ' ';
	}

	tinyMCEPopup.execCommand("mceInsertContent", false, html);
	tinyMCEPopup.close();
}

function cancelAction() {
	tinyMCEPopup.close();
}
