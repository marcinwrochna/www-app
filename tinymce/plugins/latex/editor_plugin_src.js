(function() {
/* Import plugin specific language pack */
tinyMCE.PluginManager.requireLangPack("latex");

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
				if (node.nodeName.toLowerCase() == "img" && ed.dom.hasClass(node,'latexEquation')) {
					cm.setActive('latex', true);
					return true;
				}
			} while ((node = node.parentNode));

			cm.setActive('latex', false);
			return true;
		});
		
		ed.onBeforeSetContent.add(function(ed, o) {
			var lrurl;
			lrurl = ed.getParam("latex_renderUrl");
			if(lrurl)
			{
				o.content = o.content.replace(/\[tex\]/gi, '<img class="latexEquation" src="'+lrurl+'?');
				o.content = o.content.replace(/\[\/tex\]/gi, '" border="0" align="absmiddle" alt="latex" />');
			}
		});
		
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ? ed.dom.decode(n[1]) : '';
		};

		ed.onPostProcess.add(function(ed, o) {
			var lrurl;
			lrurl = ed.getParam("latex_renderUrl");
			if (o.set) {
				if(lrurl)
				{
					o.content = o.content.replace(/\[tex\]/gi, '<img class="latexEquation" title="mimetex" src="'+lrurl+'?');
					o.content = o.content.replace(/\[\/tex\]/gi, '" border="0" align="absmiddle" alt="latex" />');
				}
			}
			if (o.get) {
				o.content = o.content.replace(/<img[^>]+>/g, function(im) {
					if(!/latexEquation/.test(getAttr(im,'class')))
						return im;
					var src = getAttr(im,'src');
					//src = src.replace(new RegExp(lrurl, 'gi'), '');
					src = src.replace(new RegExp('^.*mimetex.cgi\\?', 'gi'), '');
					//src = src.replace(/^\?/gi, '');
					html = '[tex]'+decodeURIComponent(src)+'[/tex]';
					return html;
				});	
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
				var template = new Array() , value = "", lrurl;
				var ed = tinyMCE.activeEditor;
				lrurl = ed.getParam("latex_renderUrl");

				if (ed.selection.getNode() != null && ed.selection.getNode().nodeName.toLowerCase() == "img"
						&& ed.dom.hasClass(ed.selection.getNode(),'latexEquation')) {					
					var nd = ed.selection.getNode();
					if (nd) {
						value = nd.getAttribute('src') ? nd.getAttribute('src') : "";
						value = value.replace(new RegExp('^.*mimetex.cgi\\?', 'gi'), '');
						value = decodeURIComponent(value);
					}

					ed.windowManager.open({
							url : this.url + 'latex.htm',
							width:  600 + parseInt(ed.getLang('latex.delta_width', 0)),
							height: 480 + parseInt(ed.getLang('latex.delta_height', 0))
						},{
							value: value,
							lrurl: lrurl,
							mceDo: 'update'
					});
				} else {					
					var inst = ed;
					var	st = inst.selection.getContent();
					var html = '', value = '';
					
					if(st && st.length > 0) {
						st = st.replace("\"", "&#34;");
						if(lrurl)
							html = '<img class="latexEquation" src="'+lrurl+'?'+st+'" border="0" align="absmiddle" alt="latex" />';
						else
							html = '[tex]'+st+'[/tex]';
						inst.execCommand("mceInsertContent", false, html);
					}
					else {
						ed.windowManager.open({
								url : this.url + 'latex.htm',
								width:  600 + parseInt(ed.getLang('latex.delta_width', 0)),
								height: 480 + parseInt(ed.getLang('latex.delta_height', 0))
							},{
								value: value,
								lrurl: lrurl,
								mceDo: 'insert'
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

