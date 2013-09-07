var LatexDialog = {
init : function(ed) {
	var formObj = document.forms[0];
	var val = tinyMCEPopup.getWindowArg('value');
	this.renderURL = tinyMCEPopup.getWindowArg('renderURL');
		
	formObj.formula.innerHTML  = val;
	formObj.insert.value = tinyMCEPopup.getLang('latex.' + tinyMCEPopup.getWindowArg('mceDo'),'Insert',true);

	formObj.formula.onkeyup = this.updatePreview;
	formObj.formula.onclick = this.updatePreview;
	this.updatePreview();
	MathJax.Hub.Queue(["Typeset",MathJax.Hub]);
	
	//tinyMCEPopup.resizeToInnerSize();
},

updatePreview : function () {
	var formula = document.forms[0].formula.value;
	var preview = document.getElementById("preview");
	preview.innerHTML = '[tex]'+ formula +'[/tex]';
	MathJax.Hub.Queue(["Typeset",MathJax.Hub,preview]);
	// var math = MathJax.Hub.getAllJax(preview)[0];
	// MathJax.Hub.Queue(["Text",math,formObj.formula.value]);
},

insertLatex : function () {
	var ed = tinyMCEPopup.editor, dom = ed.dom;
	var formula = document.forms[0].formula.value;
	var html = '';

	if (formula) {
		html = dom.createHTML('img', {
			'class': 'latexFormula',
			'alt': formula,
			'src': this.renderURL + encodeURIComponent(formula)
		});
	}

	tinyMCEPopup.execCommand("mceInsertRawHTML", false, html);
	tinyMCEPopup.close();
},

cancelAction : function() {
	tinyMCEPopup.close();
}

};

tinyMCEPopup.onInit.add(LatexDialog.init, LatexDialog);
