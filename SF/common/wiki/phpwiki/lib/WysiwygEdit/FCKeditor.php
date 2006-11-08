<?php
rcs_id('$Id: FCKeditor.php,v 1.1 2005/10/31 16:41:46 rurban Exp $');
/**
 * FCKeditor is compatible with most internet browsers which
 * include: IE 5.5+ (Windows), Firefox 1.0+, Mozilla 1.3+
 * and Netscape 7+.
 *
 * Download: http://fckeditor.net/
 * Suggested installation into themes/default/FCKeditor/
 * or the default /FCKeditor/. See $this->BasePath below.
 *
 * @package WysiwygEdit
 * @author Reini Urban
 */

require_once("lib/WysiwygEdit.php");

class WysiwygEdit_FCKeditor extends WysiwygEdit {

    function WysiwygEdit_FCKeditor() {
        global $LANG;
        $this->_transformer_tags = false;
	$this->BasePath = DATA_PATH.'/themes/default/FCKeditor/';
	$this->_htmltextid = "edit:content"; // FCKEditor1;
        $this->_wikitextid = "editareawiki";
    	$this->_jsdefault = "
oFCKeditor.BasePath	= '$this->BasePath';
oFCKeditor.Height	= 300;
// oFCKeditor.ToolbarSet	= 'Basic' ;
oFCKeditor.Config.DefaultLanguage = '$LANG';
oFCKeditor.Config.LinkBrowserURL  = oFCKeditor.BasePath + 'editor/filemanager/browser/default/browser.html?Connector=connectors/php/connector.php';
oFCKeditor.Config.ImageBrowserURL = oFCKeditor.BasePath + 'editor/filemanager/browser/default/browser.html?Type=Image&Connector=connectors/php/connector.php';
";
    	if (!empty($_REQUEST['start_debug']))
    	    $this->_jsdefault = "\noFCKeditor.Config.Debug = true;";
    }

    function Head($name='edit[content]') {
        global $WikiTheme;
        $WikiTheme->addMoreHeaders
            (Javascript('', array('src' => $this->BasePath . 'fckeditor.js',
                                  'language' => 'JavaScript')));
	return JavaScript("
window.onload = function()
{
var oFCKeditor = new FCKeditor( '$this->_htmltextid' ) ;"
. $this->_jsdefault . "
// force textarea in favor of iFrame?
// oFCKeditor._IsCompatibleBrowser = function() { return false; }
oFCKeditor.ReplaceTextarea();
}");
    }

    function Textarea ($textarea, $wikitext, $name='edit[content]') {
    	return $this->Textarea_Replace($textarea, $wikitext, $name);
    }

    /* either iframe or textarea */
    function Textarea_Create ($textarea, $wikitext, $name='edit[content]') {
        $htmltextid = $name;
        $out = HTML(
		    JavaScript("
var oFCKeditor = new FCKeditor( '$htmltextid' ) ;
oFCKeditor.Value	= '" . $textarea->_content[0]->asXML() . "';" 
. $this->_jsdefault . "
oFCKeditor.Create();"),
		    HTML::div(array("id"    => $this->_wikitextid, 
				    'style' => 'display:none'),
			      $wikitext),
		    "\n");
	return $out;
    }
    
    /* textarea only */
    function Textarea_Replace ($textarea, $wikitext, $name='edit[content]') {
        $htmltextid = $this->_htmltextid;
        $textarea->SetAttr('id', $htmltextid);
        $out = HTML($textarea,
		    HTML::div(array("id"    => $this->_wikitextid, 
				    'style' => 'display:none'),
			      $wikitext),
		    "\n");
	return $out;
    }
    
    /* via the PHP object */
    function Textarea_PHP ($textarea, $wikitext, $name='edit[content]') {
        global $LANG;
	$this->FilePath = realpath(PHPWIKI_DIR.'/themes/default/FCKeditor') . "/";

        $htmltextid = "edit:content";

	include_once($this->FilePath . 'fckeditor.php');
	$this->oFCKeditor = new FCKeditor($htmltextid) ;
	$this->oFCKeditor->BasePath = $this->BasePath;
	$this->oFCKeditor->Value = $textarea->_content[0]->asXML();

	$this->oFCKeditor->Config['AutoDetectLanguage']	= true ;
	$this->oFCKeditor->Config['DefaultLanguage'] = $LANG;
	$this->oFCKeditor->Create();
	
	return HTML::div(array("id"   => $this->_wikitextid, 
			      'style' => 'display:none'),
			      $wikitext);
    }

}


/*
 $Log: FCKeditor.php,v $
 Revision 1.1  2005/10/31 16:41:46  rurban
 added FCKeditor + spaw


*/

// Local Variables:
// mode: php
// tab-width: 8
// c-basic-offset: 4
// c-hanging-comment-ender-p: nil
// indent-tabs-mode: nil
// End:
?>