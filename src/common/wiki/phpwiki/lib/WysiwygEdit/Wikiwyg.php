<?php
rcs_id('$Id: Wikiwyg.php,v 1.3 2006/05/31 19:59:57 jeannicolas Exp $');
/**
 * Wikiwyg is compatible with most internet browsers which
 * include: IE 5.5+ (Windows), Firefox 1.0+, Mozilla 1.3+
 * and Netscape 7+.
 *
 * Download: http://openjsan.org/doc/i/in/ingy/Wikiwyg/
 * Suggested installation into themes/default/Wikiwyg/
 *
 * @package WysiwygEdit
 * @author  Reini Urban, based on a patch by Jean-Nicolas GEREONE, STMicroelectronics, 2006
 */

require_once("lib/WysiwygEdit.php");

class WysiwygEdit_Wikiwyg extends WysiwygEdit {

    function WysiwygEdit_Wikiwyg($request) {
        global $LANG;
	
        $this->_transformer_tags = false;
	$this->BasePath = DATA_PATH.'/themes/default/Wikiwyg';
	$this->_htmltextid = "edit:content";
        $this->_wikitextid = "editareawiki";
	$this->_pagename = $request->getArg('pagename');
    }

    function Head($name='edit[content]') {
        global $WikiTheme, $wysiwyg_editor_params, $group_id;
	
	$doubleClickToEdit = ($GLOBALS['request']->getPref('doubleClickEdit') or ENABLE_DOUBLECLICKEDIT)
	    ? 'true' : 'false';
	    
	$wysiwyg_editor_params['WIKIWYG_SCRIPTS'] = array("Wikiwyg.js", "Wikiwyg/Toolbar.js" , "Wikiwyg/Preview.js", "Wikiwyg/Wikitext.js",
				"Wikiwyg/Wysiwyg.js", "Wikiwyg/Phpwiki.js", "Wikiwyg/HTML.js", "Wikiwyg/Toolbar.js");
	
	$wysiwyg_editor_params['WYSIWYG_SCRIPT'] = "
var base_url = '/wiki';
var data_url = '/wiki/themes/default/Wikiwyg';
var script_url = '/wiki/index.php';
var groupid = $group_id;
var pagename = '$this->_pagename';

window.onload = function() {
   var wikiwyg = new Wikiwyg.Phpwiki();
   var config = {
            doubleClickToEdit:  $doubleClickToEdit,
            javascriptLocation: base_url+'/themes/default/Wikiwyg/',
            toolbar: {
	        imagesLocation: base_url+'/themes/default/Wikiwyg/images/',
		controlLayout: [
		       'save', 'preview', 'save_button', '|',
		       'p', 'h2', 'h3', 'h4', 'bold', 'italic','sup', 'sub', 'ordered', 'unordered', 'hr', 'pre', 'toc', '|',
		       'link', 'image', 'wikitext', '|', '|',
		       'table'
		       ],
		styleSelector: [
		       'label', 'p', 'h2', 'h3', 'h4', 'pre'
				], 
		controlLabels: {
	               save:     'Apply changes',
		       cancel:   'Exit toolbar',
		       p:	 'Format plain text',
		       h2:       'Format Level 1 header',
		       h3:       'Format Level 2 header',
		       h4:       'Format Level 3 header',
		       bold:	 'Format bold text',
		       italic:	 'Format italic text',
		       verbatim: 'Verbatim',
                       toc:	 'Insert Table Of Content', 
		       pre:	 'Insert preformatted text',
		       table:	 'Create Rich Table (EXPERIMENTAL feature)',
		       image:	 'Insert an already uploaded image',
                       wikitext: 'Insert ASCII wikitext', 
                       sup:      'Format Superscript text', 
                       sub:      'Format Subscript text',
		       ordered:	 'Format numbered list',
		       unordered:'Format unordered list',
		       hr:	 'Insert horizontal line',
                       preview:  'Preview wiki page',   
                       save_button:'Save wiki page'
	              }
            },
            wysiwyg: {
                iframeId: 'iframe0'
            },
	    wikitext: {
	      supportCamelCaseLinks: true
	    }
   };
   var div = document.getElementById(\"edit:content\");
   wikiwyg.createWikiwygArea(div, config);
   wikiwyg_divs.push(wikiwyg);
   wikiwyg.editMode();
}";
	
	$wysiwyg_editor_params['WYSIWYG_TEXTAREA'] = '';
	
	// Small scripts for online wysiwyg edition rules documentation/help.
	$wysiwyg_editor_params['WYSIWYG_HELP_SCRIPT'] = 'function showWysiwygHelp(){ return true;}';
	$wysiwyg_editor_params['WYSIWYG_NOHELP_SCRIPT'] = 'function showWysiwygHelp(){ return false;}';
	
	// Support for CodeX-lite theme
        if ($WikiTheme->_name == "CodeX-lite"){
	    if ($_REQUEST['action'] = 'edit' and isset($_REQUEST['mode']) and ($_REQUEST['mode'] == 'wysiwyg')){ 
	        foreach($wysiwyg_editor_params['WIKIWYG_SCRIPTS'] as $js){
	        
	        $WikiTheme->addMoreHeaders
                    (Javascript('', array('src' => $this->BasePath . '/' . $js,
                                          'language' => 'JavaScript')));
	        }
	        
	        $WikiTheme->addMoreHeaders(Javascript($wysiwyg_editor_params['WYSIWYG_SCRIPT'], array('language' => 'JavaScript')));
	    
	        if (isset($wysiwyg_editor_params['WYSIWYG_TEXTAREA'])){
	            $WikiTheme->addMoreHeaders(Javascript($wysiwyg_editor_params['WYSIWYG_TEXTAREA'], array('language' => 'JavaScript')));
	        }
	        if (isset($wysiwyg_editor_params['WYSIWYG_HELP_SCRIPT'])){
		    $WikiTheme->addMoreHeaders(Javascript($wysiwyg_editor_params['WYSIWYG_HELP_SCRIPT'], array('language' => 'JavaScript')));
		}
	    }else{
		$WikiTheme->addMoreHeaders(Javascript($wysiwyg_editor_params['WYSIWYG_NOHELP_SCRIPT'], array('language' => 'JavaScript')));
	    }
	}
    }

    function Textarea ($textarea, $wikitext, $name='edit[content]') {
        global $wysiwyg_editor_params;
        $htmltextid = $this->_htmltextid;
        $textarea->SetAttr('id', $htmltextid);
        $iframe0 = new RawXml('<iframe id="iframe0" height="0" width="0" frameborder="0"></iframe>');
        $out = HTML($textarea, $iframe0, "\n");
	$wysiwyg_editor_params['WYSIWYG_TEXTAREA'] = $out;
    }

    /**
     * Handler to convert the Wiki Markup to HTML before editing.
     * This will be converted back by WysiwygEdit_ConvertAfter if required.
     *  *text* => '<b>text<b>'
     */
    function ConvertBefore($text) {
        return $text;
    }

    /* 
     * No special PHP HTML->Wikitext conversion needed. This is done in js thanksfully. 
     * Avoided in editpage.php: PageEditor->getContent
     */
    function ConvertAfter($text) {
        return TransformInline($text);
    }
}


/*
 $Log: Wikiwyg.php,v $
 Revision 1.3  2006/05/31 19:59:57  jeannicolas


 Added wysiwyg_editor 1.1b

 Revision 1.2  2006/05/14 17:52:20  rurban
 fix syntax error. delete a left-over attempt to add CSS links also. We did put everything into phpwiki.css for browser compatibility.

 Revision 1.1  2006/05/13 19:59:55  rurban
 added wysiwyg_editor-1.3a feature by Jean-Nicolas GEREONE <jean-nicolas.gereone@st.com>
 converted wysiwyg_editor-1.3a js to WysiwygEdit framework
 changed default ENABLE_WYSIWYG = true and added WYSIWYG_BACKEND = Wikiwyg


*/

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>