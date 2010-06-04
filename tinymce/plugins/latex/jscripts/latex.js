var LatexDialog = {
	
init : function(ed) {
	var formObj = document.forms[0];
	var val = tinyMCEPopup.getWindowArg('value');
	var lrurl = tinyMCEPopup.getWindowArg('lrurl');
	
	
	formObj.formula.innerHTML  = val;
	formObj.insert.value = tinyMCEPopup.getLang('latex.' + tinyMCEPopup.getWindowArg('mceDo'),'Insert',true);
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
		else {
			formObj.preview.style.display='none';
		}
	}
	
	tinyMCEPopup.resizeToInnerSize();
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

insertLatex : function () {
	var ed = tinyMCEPopup.editor, dom = ed.dom;
	var formObj = document.forms[0];
	var value   = formObj.formula.value;
	var lrurl = formObj.lrurl.value;
	var html = '';
	
	value = value.replace("\"", "&#34;");

	if(lrurl && value) {
		html = dom.createHTML('img', {
			src : lrurl + '?' + value,
			alt : "latex",
			align: "absmiddle",
			'class': 'latexEquation'
			
		});
		//html = '<img class="latexEquation" align="absmiddle" src="'+lrurl+'?'+value+'" alt="latex"/>';
	}
	else { 
		if(value) {
			html = '[tex]'+value+'[/tex]';
		} else {
			html = ' ';
		}
	}

	tinyMCEPopup.execCommand("mceInsertRawHTML", false, html);
	tinyMCEPopup.close();
},

cancelAction : function() {
	tinyMCEPopup.close();
}

};

tinyMCEPopup.onInit.add(LatexDialog.init, LatexDialog);
