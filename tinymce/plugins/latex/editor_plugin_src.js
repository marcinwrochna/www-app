(function() {
/* Import plugin specific language pack */
tinymce.PluginManager.requireLangPack("latex");

tinymce.create('tinymce.plugins.LatexPlugin', {	
	init : function(ed, url) {
		this.url = url + '/';

		ed.addButton('latex', {
				title : 'latex.desc',
				cmd : 'mceLatex'
		});		
		
		ed.onNodeChange.add(function(ed,cm,node) {
			if (node == null)  return;

			do {
				if (node.nodeName.toLowerCase() == "img" && ed.dom.hasClass(node,'latexFormula')) {
					cm.setActive('latex', true);
					return true;
				}
			} while ((node = node.parentNode));

			cm.setActive('latex', false);
			return true;
		});
		
		ed.onBeforeSetContent.add(function(ed, o) {
			o.content = o.content.replace(/\[tex\]((.|\n|\r)*?)\[\/tex\]/gi, function(match0, match1) {
				return '<img class="latexFormula" '+
					'alt="'+ match1.replace("\"", "&#34;") +'" '+
					'src="'+ ed.getParam("latex_renderUrl") + encodeURIComponent(match1) +'"/>';
			}); 
		});


		ed.onSetContent.add(function(ed, o) {
		});

		ed.onPostProcess.add(function(ed, o) {
			/*if (o.set) {
				o.content = o.content.replace(/\[tex\]/gi, '<span class="latexFormula"[^>]*>[tex]');
				o.content = o.content.replace(/\[\/tex\]/gi, '[/tex]</span>');
			}*/
			if (o.get) {
				o.content = o.content.replace(new RegExp('<img class="latexFormula"[^>]* alt="([^"]*)"[^>]*>','g'),
					function(match0, match1) { return '[tex]'+ match1.replace("&#34;", "\"").replace("&quot;", "\"") +'[/tex]'; });
			}
		});
		
	},
	
	getInfo : function() {
		return {
			longname : 'Insert Latex Formula',
			author : 'Marina Zelwer, Marcin Wrochna',
			authorurl : '',
			infourl : '',
			version : "2.0"
		};
	},

	/**
	 * Executes the mceLatex command.
	 */
	//execCommand : function(editor_id, element, command, user_interface, value) {
	execCommand : function(command, ui, value) {
		// Handle commands
		switch (command) {
			case "mceLatex":
				var template = new Array() , value = "";
				var ed = tinyMCE.activeEditor;

				var nd = ed.selection.getNode();
				if (nd != null && nd.nodeName.toLowerCase() == "img"
						&& ed.dom.hasClass(nd,'latexFormula')) {
					if (nd) {
						value = nd.alt;
					}

					ed.windowManager.open({
							url : this.url + 'latex.htm',
							width:  600 + parseInt(ed.getLang('latex.delta_width', 0)),
							height: 480 + parseInt(ed.getLang('latex.delta_height', 0))
						},{
							value: value,
							mceDo: 'update',
							renderURL: ed.getParam("latex_renderUrl")
					});
				} else {					
					var inst = ed;
					var st = inst.selection.getContent();
					var html = '', value = '';
					
					if(st && st.length > 0) {
						html = '<img class="latexFormula" '+
							'alt="'+ st.replace("\"", "&#34;") +'" '+
							'src="'+ ed.getParam("latex_renderUrl") + encodeURIComponent(st) +'"/>';
						inst.execCommand("mceInsertContent", false, html);
					}
					else {
						ed.windowManager.open({
								url : this.url + 'latex.htm',
								width:  600 + parseInt(ed.getLang('latex.delta_width', 0)),
								height: 480 + parseInt(ed.getLang('latex.delta_height', 0))
							},{
								value: value,
								mceDo: 'insert',
								renderURL: ed.getParam("latex_renderUrl")
							}
						);
					}
				}

				return true;
		}

		// Pass to next handler in chain
		return false;
	}


});

tinymce.PluginManager.add('latex', tinymce.plugins.LatexPlugin);

}) ();

