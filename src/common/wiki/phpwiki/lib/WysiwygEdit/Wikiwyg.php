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

    function WysiwygEdit_Wikiwyg() {
        global $LANG;
        $this->_transformer_tags = false;
	$this->BasePath = DATA_PATH.'/themes/default/Wikiwyg';
	$this->_htmltextid = "edit:content";
        $this->_wikitextid = "editareawiki";
    	$this->_jsdefault = "
var base_url = '".DATA_PATH."';
var data_url = '$this->BasePath';
var script_url = '".deduce_script_name()."';
";
    }

    function Head($name='edit[content]') {
        global $WikiTheme;
        foreach (array("Wikiwyg.js","Wikiwyg/Toolbar.js","Wikiwyg/Preview.js","Wikiwyg/Wikitext.js",
                       "Wikiwyg/Wysiwyg.js","Wikiwyg/Phpwiki.js","Wikiwyg/HTML.js",
                       "Wikiwyg/Toolbar.js") as $js) {
            $WikiTheme->addMoreHeaders
                (Javascript('', array('src' => $this->BasePath . '/' . $js,
                                      'language' => 'JavaScript')));
        }
        $doubleClickToEdit = ($GLOBALS['request']->getPref('doubleClickEdit') or ENABLE_DOUBLECLICKEDIT) 
            ? 'true' : 'false';
        return JavaScript($this->_jsdefault . "
window.onload = function() {
   var wikiwyg = new Wikiwyg.Phpwiki();
   var config = {
            doubleClickToEdit:  $doubleClickToEdit,
            javascriptLocation: base_url+'/themes/default/Wikiwyg/',
            toolbar: {
	        imagesLocation: base_url+'/themes/default/Wikiwyg/images/',
		controlLayout: [
		       'save','preview','|','save_button','|',
                       'mode_selector', '/',
		       'p','|',
		       'h2', 'h3', 'h4','|',
		       'bold', 'italic', '|',
                       'sup', 'sub', '|',
                       'toc',
                       'wikitext','|',
		       'pre','|',
		       'ordered', 'unordered','hr','|',
		       'link','|',
                       'table'
		       ],
		styleSelector: [
		       'label', 'p', 'h2', 'h3', 'h4', 'pre'
				], 
		controlLabels: {
	               save:     '"._("Apply changes")."',
		       cancel:   '"._("Exit toolbar")."',
		       h2:       '"._("Title 1")."',
		       h3:       '"._("Title 2")."',
		       h4:       '"._("Title 3")."',
		       verbatim: '"._("Verbatim")."',
                       toc:   '"._("Table of content")."', 
                       wikitext:   '"._("Insert Wikitext section")."', 
                       sup:      '"._("Sup")."', 
                       sub:      '"._("Sub")."',
                       preview:  '"._("Preview")."',   
                       save_button:'"._("Save")."'   
	              }
            },
            wysiwyg: {
                iframeId: 'iframe0'
            },
	    wikitext: {
	      supportCamelCaseLinks: true
	    }
   };
   var div = document.getElementById(\"" . $this->_htmltextid . "\");
   wikiwyg.createWikiwygArea(div, config);
   wikiwyg_divs.push(wikiwyg);
   wikiwyg.editMode();
}");
    }

    function Textarea ($textarea, $wikitext, $name='edit[content]') {
        $htmltextid = $this->_htmltextid;
        $textarea->SetAttr('id', $htmltextid);
        $iframe0 = new RawXml('<iframe id="iframe0" height="0" width="0" frameborder="0"></iframe>');
        $out = HTML($textarea,
                    $iframe0,
		    "\n");
	return $out;
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