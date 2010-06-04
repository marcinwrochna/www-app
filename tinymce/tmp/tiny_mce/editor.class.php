<?php // $Id: editor.class.php,v 1.20 2007/02/13 15:39:19 seb Exp $
if ( count( get_included_files() ) == 1 ) die( '---' );
/**
 * CLAROLINE
 *
 * Driver for tinyMCE wysiwyg editor ( http://tinymce.moxiecode.com/ )
 *
 * @version 1.8 $Revision: 1.20 $
 *
 * @copyright 2001-2006 Universite catholique de Louvain (UCL)
 *
 * @license http://www.gnu.org/copyleft/gpl.html (GPL) GENERAL PUBLIC LICENSE
 *
 * @see http://www.claroline.net/wiki/config_def/
 *
 * @package EDITOR
 *
 * @author Claro Team <cvs@claroline.net>
 * @author S�bastien Piraux <pir@cerdecam.be>
 *
 */
 
require dirname(__FILE__) . '/../GenericEditor.class.php';
/**
 * Class to manage htmlarea overring simple textarea html
 * @package EDITOR
 */
class editor extends GenericEditor
{
    /**
     * @var $_tag metadata comment added to identify editor
     */
    var $_tag;

    /**
     * @var $_askStrip ask user if the content can be cleaned ?
     */
    var $_askStrip;
    
    /**
     * constructor
     *
     * @author S�bastien Piraux <pir@cerdecam.be>
     * @param string $name content for attribute name and id of textarea
     * @param string $content content of the textarea
     * @param string $rows number of lines of textarea
     * @param string $cols number of cols of textarea
     * @param string $optAttrib additionnal attributes that can be added to textarea
     * @param string $webPath path to access via the web to the directory of the editor
     */
    function editor( $name,$content,$rows,$cols,$optAttrib,$webPath )
    {
        parent::GenericEditor( $name,$content,$rows,$cols,$optAttrib,$webPath );
        
        if(!$this->content) // UJM fix bug of tiny_mce removing this comment
       		$this->_tag = '<p></p><!-- content: html tiny_mce -->';
       	else
       		$this->_tag = '<!-- content: html tiny_mce -->';
        		
        // test content before preparing because preparation adds $this->_tag
        $this->_askStrip = $this->needCleaning();		

        $this->prepareContent();
    }
    
   
    /**
     * Returns the html code needed to display an advanced (default) version of the editor
     * @return string html code needed to display an advanced (default) version of the editor
       */
    function getAdvancedEditor()
    {
        // TODO limit to one editor object instance that will give output of several textarea instance
        global $isJsLoaded;
        
        $returnString = '';
        
        if( !isset($isJsLoaded) )
        {
            $returnString .=
                "\n\n"
                .'<script language="javascript" type="text/javascript" src="'.$this->webPath.'/tiny_mce.js"></script>'."\n";
                
            $isJsLoaded = true;
        }
        
        // configure this editor instance (UJM : add latex plugin)
        $returnString .=
            "\n"
            .'<script language="javascript" type="text/javascript">'."\n"
            .'tinyMCE.init({'."\n"
            .'    mode : "exact",'."\n"
            .'    elements: "'.$this->name.'",'."\n"
            .'    theme : "advanced",'."\n"
            .'    browsers : "msie,gecko,opera",' . "\n" // disable tinymce for safari. default value is "msie,gecko,safari,opera"
            .'    plugins : "media,paste,table,latex",'."\n"
            .'    theme_advanced_buttons1 : "fontselect,fontsizeselect,formatselect,bold,italic,underline,strikethrough,separator,sub,sup,separator,undo,redo",'."\n"
            .'    theme_advanced_buttons2 : "cut,copy,paste,pasteword,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,outdent,indent,separator,forecolor,backcolor,separator,hr,link,unlink,image,media,code",'."\n"
            .'    theme_advanced_buttons3 : "tablecontrols,separator,latex,separator,help",'."\n"
            .'    theme_advanced_toolbar_location : "top",'."\n"
            .'    theme_advanced_toolbar_align : "left",'."\n"
            .'    theme_advanced_path : true,'."\n"
            .'    theme_advanced_path_location : "bottom",'."\n"
            .'    apply_source_formatting : true,'."\n"
            .'    convert_urls : false,'."\n" // prevent forced conversion to relative url 
            .'    relative_urls : false,'."\n"; // prevent forced conversion to relative url
		
		// UJM : add latex plugin configuration option
		$returnString .= 
			'    latex_renderUrl : "' . get_conf('claro_texRendererUrl', '') . '",' . "\n";
		
		// if required call the function that will ask user if the text has to be cleaned
		if( $this->_askStrip ) $returnString .='    setupcontent_callback : "strip_old_htmlarea",'."\n";
            
        $returnString .=
            '    extended_valid_elements : "a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]"'."\n"
            .'});'."\n\n"
            .'</script>'."\n\n";
        
		if( $this->_askStrip )
		{
			$returnString .=
	            "\n\n"
            	.'<script language="javascript" type="text/javascript">'."\n\n"
        	    .'function strip_old_htmlarea(editor_id,body,doc)'."\n"
		        .'{'."\n"
        	    .'    if( confirm(" '.clean_str_for_javascript(get_lang('This text layout should be modified to be editable in this editor. Cancel to keep your original text layout.')).' ") )'."\n"
    	        .'    {'."\n"
				.'        content = body.innerHTML;'."\n\n"	
        	    .'        content = content.replace(/style="[^"]*"/g, "");'."\n"
    	        .'        content = content.replace(/<span[^>]*>/g, "");'."\n"
	            .'        content = content.replace(/<\/span>/g, "");'."\n\n"
        	    .'        body.innerHTML = content ;'."\n"
    	        .'        return true;'."\n"            
	            .'    }'."\n"            
        	    .'    return false;'."\n"
    	        .'}'."\n\n"
	            .'</script>'."\n\n";
        }
        
        // add standard text area
        $returnString .= $this->getTextArea();
            
        return  $returnString;
    }
    
    /**
     * Introduce a comment stating that the content is html and edited with this editor
     *
     * @access private
     */
    function prepareContent()
    {
    	// remove old 'metadata' and add the good one
    	$this->content = preg_replace('/<!-- content:[^(\-\->)]*-->/', '', $this->content) . $this->_tag;

        return true;
    }
    
    /**
     * check if the text require a cleaning to be editable by tinymce
     *
     * @return boolean is content requiring a cleaning to be
     * @access private
     */
    function needCleaning()
    {
    	// if we already have the tinymce tag content cleaning is not required
	    if( strpos($this->content,$this->_tag) !== false ) return false;    

	    // if content contains only the tiny_mce tag cleaning is not required
	    if( '' == str_replace($this->_tag,'',$this->content) ) return false;

    	if( preg_match('/style="[^"]*"/',$this->content) )
    	{
   			// if we have style attributes : cleaning is required
    		return true;
    	}    	
    	elseif( preg_match('/<span[^>]*>/', $this->content) )
    	{
  			// if we have span tags : cleaning is required
    		return true;
    	}
    	else
    	{
    		// nor style attributes neither span tags : cleaning is not required
    		return false;
    	}    	
    }

}
?>
