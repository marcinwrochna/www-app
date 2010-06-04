
/* Import plugin specific language pack */
tinyMCE.importPluginLanguagePack("latex");

var TinyMCE_LatexPlugin = {
	getInfo : function() {
		return {
			longname : 'Insert Latex Formula',
			author : 'Marina Zelwer',
			authorurl : '',
			infourl : '',
			version : tinyMCE.majorVersion + "." + tinyMCE.minorVersion
		}
	},

	getControlHTML : function(cn) {
		switch (cn) {
			case "latex":
				return tinyMCE.getButtonHTML(cn, 'lang_insert_latex_desc', '{$pluginurl}/images/latex.gif', 'mceLatex');
		}

		return "";
	},

	/**
	 * Executes the mceLatex command.
	 */
	execCommand : function(editor_id, element, command, user_interface, value) {
		// Handle commands
		switch (command) {
			case "mceLatex":
				var template = new Array() , value = "", lrurl;
				
				lrurl = tinyMCE.getParam("latex_renderUrl");

				template['file']   = '../../plugins/latex/latex.htm'; // Relative to theme
				template['width']  = 600;
				template['height'] = 480;

				template['width']  += tinyMCE.getLang('lang_latex_delta_width', 0);
				template['height'] += tinyMCE.getLang('lang_latex_delta_height', 0);

				if (tinyMCE.selectedElement != null && tinyMCE.selectedElement.nodeName.toLowerCase() == "img" && tinyMCE.hasCSSClass(tinyMCE.selectedElement, 'mceItemLatex')) {
					tinyMCE.inputElement = tinyMCE.selectedElement;

					if (tinyMCE.inputElement) {
						value = tinyMCE.inputElement.getAttribute('src') ? tinyMCE.inputElement.getAttribute('src') : "";
						value = value.replace(new RegExp(lrurl, 'gi'), '');
						value = value.replace(/^\?/gi, '');
						value = decodeURIComponent(value);
					}

					tinyMCE.openWindow(template, {editor_id : editor_id, value : value, lrurl: lrurl, mceDo : 'update'});
				} else {					
					var inst = tinyMCE.getInstanceById(editor_id);
					var	st = inst.selection.getSelectedText();
					var html = '', value = '';
					
					if(st && st.length > 0) {
						st = st.replace("\"", "&#34;");
						if(lrurl)
							html = '<img class="mceItemLatex" title="latexEquation" src="'+lrurl+'?'+st+'" border="0" align="absmiddle" />';
						else
							html = '[tex]'+st+'[/tex]';
						inst.execCommand("mceInsertContent", false, html);
					}
					else {
						tinyMCE.openWindow(template, {editor_id : editor_id, value : value, lrurl: lrurl, mceDo : 'insert'});
					}
				}

				return true;
		}

		// Pass to next handler in chain
		return false;
	},

	cleanup : function(type, content, inst) {
		var nl, img, i, ne, d, s, ci;
		var lrurl;
		lrurl = tinyMCE.getParam("latex_renderUrl");

		switch (type) {
			case "insert_to_editor":
				if(lrurl)
				{
					content = content.replace(/\[tex\]/gi, '<img class="mceItemLatex" title="mimetex" src="'+lrurl+'?');
					content = content.replace(/\[\/tex\]/gi, '" border="0" align="absmiddle" />');
				}
				break;

			case "insert_to_editor_dom":
				break;

			case "get_from_editor":
				var startPos = -1, endPos, attribs, chunkBefore, chunkAfter, html, src, url;
				
				while ((startPos = content.indexOf('<img', startPos+1)) != -1) {
					endPos = content.indexOf('/>', startPos);
					attribs = TinyMCE_MediaPlugin._parseAttributes(content.substring(startPos + 4, endPos));
					//if not latex skip it
					if(!/mceItemLatex/.test(attribs['class']))
						continue;
					endPos += 2;
					
					src = attribs['src'];
					src = src.replace(new RegExp(lrurl, 'gi'), '');
					src = src.replace(/^\?/gi, '');
					html = '[tex]'+decodeURIComponent(src)+'[/tex]';
					
					// Insert latex tag
					chunkBefore = content.substring(0, startPos);
					chunkAfter = content.substring(endPos);
					content = chunkBefore + html + chunkAfter;
				}
				break;
		}

		return content;
	},

	handleNodeChange : function(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {
		if (node == null)
			return;

		do {
			if (node.nodeName.toLowerCase() == "img" && tinyMCE.hasCSSClass(node, 'mceItemLatex')) {
				tinyMCE.switchClass(editor_id + '_latex', 'mceButtonSelected');
				return true;
			}
		} while ((node = node.parentNode));

		tinyMCE.switchClass(editor_id + '_latex', 'mceButtonNormal');

		return true;
	}

};

tinyMCE.addPlugin("latex", TinyMCE_LatexPlugin);
