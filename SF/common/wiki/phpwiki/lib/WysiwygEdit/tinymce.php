<?php
rcs_id('$Id: tinymce.php,v 1.2 2005/10/31 16:41:46 rurban Exp $');
/**
 * Multiple browser support, currently Mozilla (PC, Mac and Linux), 
 * MSIE (PC) and FireFox (PC, Mac and Linux) and some limited Safari support.
 *
 * Download: http://tinymce.moxiecode.com/
 * Suggested installation of the jscripts subdirectory
 *   tinymce/jscripts/tiny_mce/ into themes/default/tiny_mce/
 *
 * WARNING! Probably incompatible with ENABLE_XHTML_XML
 *
 * @package WysiwygEdit
 * @author Reini Urban
 */

require_once("lib/WysiwygEdit.php");

class WysiwygEdit_tinymce extends WysiwygEdit {

    function WysiwygEdit_tinymce() {
        $this->_transformer_tags = false;
	$this->BasePath = DATA_PATH.'/themes/default/tiny_mce/';
	$this->_htmltextid = "edit:content";
        $this->_wikitextid = "editareawiki";
    }

    function Head($name='edit[content]') {
        global $LANG, $WikiTheme;
        $WikiTheme->addMoreHeaders
            (Javascript('', array('src' => $this->BasePath . 'tiny_mce.js',
                                  'language' => 'JavaScript')));
        return Javascript("
tinyMCE.init({
	mode    : 'exact',
	elements: '$name',
        theme   : 'advanced',
        language: \"$LANG\",
        ask     : false,
	theme_advanced_toolbar_location : \"top\",
	theme_advanced_toolbar_align    : \"left\",
	theme_advanced_path_location    : \"bottom\",
	theme_advanced_buttons1 : \"bold,italic,underline,separator,strikethrough,justifyleft,justifycenter,justifyright,justifyfull,bullist,numlist,undo,redo,link,unlink\",
	theme_advanced_buttons2 : \"\",
	theme_advanced_buttons3 : \"\",
});");
        /*
        plugins : \"table,contextmenu,paste,searchreplace,iespell,insertdatetime\",
	extended_valid_elements : \"a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]\"
});
        */
    }

    // to be called after </textarea>
    // name ignored
    function Textarea($textarea, $wikitext, $name='edit[content]') {
        $out = HTML($textarea,
                    HTML::div(array("id" => $this->_wikitextid, 
                                    'style'=>'display:none'),
                              $wikitext),"\n");
        //TODO: maybe some more custom links
        return $out;
    }
}

/*
 $Log: tinymce.php,v $
 Revision 1.2  2005/10/31 16:41:46  rurban
 added FCKeditor + spaw

 Revision 1.1  2005/10/30 14:22:15  rurban
 refactor WysiwygEdit

*/

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>
