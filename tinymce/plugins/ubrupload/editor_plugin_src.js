(function() {
tinyMCE.PluginManager.requireLangPack("ubrupload");

tinymce.create('tinymce.plugins.UbrUploadPlugin', {	
	init : function(ed, url) {
		this.url = url + '/';
		ed.addButton('ubrupload', {
				title : 'ubrupload.desc',
				cmd : 'mceUbrUpload'
		});		
		
		ed.onNodeChange.add(function(ed,cm,node) {
			if (node == null)  return;

			do {
				if (node.nodeName.toLowerCase() == "a" && ed.dom.hasClass(node,'mceUbrUpload')) {
					cm.setActive('ubrupload', true);
					return true;
				}
			} while ((node = node.parentNode));

			cm.setActive('ubrupload', false);
			return true;
		});
		
		
		function getAttr(s, n) {
			n = new RegExp(n + '=\"([^\"]+)\"', 'g').exec(s);
			return n ? ed.dom.decode(n[1]) : '';
		};
		
	},
	
	getInfo : function() {
		return {
			longname : 'Uber Uploader Plugin',
			author : 'Marcin Wrochna',
			authorurl : '',
			infourl : '',
			version : "1.0"
		};
	},

	execCommand : function(command, ui, value) {
		// Handle commands
		switch (command) {
			case "mceUbrUpload":
				var value = "";
				var ed = tinyMCE.activeEditor;
				var lrurl = ed.getParam("ubrupload_getter");

				if (ed.selection.getNode() != null && ed.selection.getNode().nodeName.toLowerCase() == "a"
						&& ed.dom.hasClass(ed.selection.getNode(),'mceUbrUpload')) {					
					/*var nd = ed.selection.getNode();
					if (nd) {
						value = nd.getAttribute('href') ? nd.getAttribute('href') : "";
						value = value.replace(new RegExp('^.*uploader/getfile.php\\?', 'gi'), '');
						value = decodeURIComponent(value);
					}

					ed.windowManager.open({
							url : this.url + 'ubrupload.php',
							width:  600 + parseInt(ed.getLang('ubrupload.delta_width', 0)),
							height: 180 + parseInt(ed.getLang('ubrupload.delta_height', 0))
						},{
							value: value,
							mceDo: 'update'
					});*/
				} else {					
					var inst = ed;
					var value = '';
					
					ed.windowManager.open({
							url : this.url + 'ubrupload.php',
							width:  600 + parseInt(ed.getLang('ubrupload.delta_width', 0)),
							height: 180 + parseInt(ed.getLang('ubrupload.delta_height', 0))
						},{
							value: value,
							mceDo: 'insert'
						}
					);
				}

				return true;
		}

		// Pass to next handler in chain
		return false;
	}


});

tinymce.PluginManager.add('ubrupload', tinymce.plugins.UbrUploadPlugin);

}) ();

