tinyMCE_GZ_config =
{
	plugins : 'nonbreaking,latex,paste,ubrupload',
	themes : 'advanced',
	languages : 'en,pl',
	disk_cache : true,
	debug : false,
	suffix : '_src'
};

tinyMCE_config =
{
	language: "pl",
	mode : "specific_textareas",
	editor_selector : "mceEditor",
	plugins: "nonbreaking,latex,ubrupload",
	nonbreaking_force_tab : "true",
	entity_encoding : "raw",
	theme: "advanced",
	theme_advanced_toolbar_location: "top",
	theme_advanced_buttons1 :
		 "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,separator,undo,redo,|,link,unlink,image,ubrupload,hr", 
	theme_advanced_buttons2 : "formatselect,fontsizeselect,|,latex,charmap,sub,sup,outdent,indent,|,removeformat,code,|,forecolor,backcolor,", 
	theme_advanced_buttons3 : "",
	theme_advanced_blockformats : "p,h3,h4,h5,h6,pre,div", //blockquote,address,samp,dd,dt
	indentation: "20px",
	theme_advanced_path : true,
	theme_advanced_path_location : "bottom",
	button_tile_map : true
};
