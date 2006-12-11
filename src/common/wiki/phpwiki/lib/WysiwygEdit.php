<?php
rcs_id('$Id: WysiwygEdit.php,v 1.4 2006/05/13 19:59:54 rurban Exp $');
/**
 * Baseclass for WysiwygEdit/*
 *
 * ENABLE_WYSIWYG - Support for some WYSIWYG_BACKEND Editors:
 *   tinymce, htmlarea3, FCKeditor, spaw, htmlarea2, Wikiwyg
 * Not yet enabled as default, since we cannot convert HTML to Wiki Markup yet.
 * (See HtmlParser.php for the ongoing efforts)
 * We might use a PageType=html, which is contra wiki, but some people 
 * might prefer HTML markup.
 *
 * TODO: Change from ENABLE_WYSIWYG constant to user preference variable 
 *       (checkbox setting or edit click as in gmail),
 *       when HtmlParser is finished.
 * Based upon htmlarea3.php and tinymce.php
 *
 * WARNING! Probably incompatible with ENABLE_XHTML_XML (TestMe)
 *
 * @package WysiwygEdit
 * @author Reini Urban
 */

require_once("lib/InlineParser.php");

class WysiwygEdit {

    function WysiwygEdit() { 
        $this->_transformer_tags = false;
    }

    function Head($name='edit[content]') {
        trigger_error("virtual", E_USER_ERROR); 
    }

    // to be called after </textarea>
    function Textarea($textarea,$wikitext,$name='edit[content]') {
        trigger_error("virtual", E_USER_ERROR); 
    }

    /**
     * Handler to convert the Wiki Markup to HTML before editing.
     * This will be converted back by WysiwygEdit_ConvertAfter if required.
     *  *text* => '<b>text<b>'
     */
    function ConvertBefore($text) {
        require_once("lib/BlockParser.php");
    	$xml = TransformText($text, 2.0, $GLOBALS['request']->getArg('pagename'));
        return $xml->AsXML();
    }
    
    /**
     * FIXME: Handler to convert the HTML formatting back to wiki formatting.
     * Derived from InlineParser, but returning wiki text instead of HtmlElement objects.
     * '<b>text<b>' => '<SPAN style="FONT-WEIGHT: bold">text</SPAN>' => '*text*'
     *
     * TODO: Switch over to HtmlParser
     */
    function ConvertAfter($text) {
        static $trfm;
        if (empty($trfm)) {
            $trfm = new HtmlTransformer($this->_transformer_tags);
        }
        $markup = $trfm->parse($text); // version 2.0
        return $markup;
    }
}

// re-use these classes for the regexp's.
// just output strings instead of XmlObjects
class Markup_html_br extends Markup_linebreak {
    function markup ($match) {
        return $match;
    }
}

class Markup_html_simple_tag extends Markup_html_emphasis {
    function markup ($match, $body) {
        $tag = substr($match, 1, -1);
        switch ($tag) {
        case 'b':
        case 'strong':
            return "*".$body."*";
        case 'big': 
            return "<big>".$body."</big>";
        case 'i':
        case 'em':
            return "_".$body."_";
        }
    }
}

class Markup_html_p extends BalancedMarkup
{
    var $_start_regexp = "<(?:p|P)( class=\".*\")?>";

    function getEndRegexp ($match) {
        return "<\\/" . substr($match, 1);
    }
    function markup ($match, $body) {
        return $body."\n";
    }
}

//'<SPAN style="FONT-WEIGHT: bold">text</SPAN>' => '*text*'
class Markup_html_spanbold extends BalancedMarkup
{
    var $_start_regexp = "<(?:span|SPAN) style=\"FONT-WEIGHT: bold\">";

    function getEndRegexp ($match) {
        return "<\\/" . substr($match, 1);
    }
    function markup ($match, $body) {
        //Todo: convert style formatting to simplier nested <b><i> tags
        return "*".$body."*";
    }
}

class HtmlTransformer extends InlineTransformer
{
    function HtmlTransformer ($tags = false) {
        if (!$tags) $tags = 
            array('escape','html_br','html_spanbold','html_simple_tag',
                  'html_p',);
        /*
         'html_a','html_span','html_div',
         'html_table','html_hr','html_pre',
         'html_blockquote',
         'html_indent','html_ol','html_li','html_ul','html_img'
        */
        return $this->InlineTransformer($tags);
    }
}

/*
 $Log: WysiwygEdit.php,v $
 Revision 1.4  2006/05/13 19:59:54  rurban
 added wysiwyg_editor-1.3a feature by Jean-Nicolas GEREONE <jean-nicolas.gereone@st.com>
 converted wysiwyg_editor-1.3a js to WysiwygEdit framework
 changed default ENABLE_WYSIWYG = true and added WYSIWYG_BACKEND = Wikiwyg

 Revision 1.3  2005/10/31 17:20:40  rurban
 fix ConvertBefore

 Revision 1.2  2005/10/31 16:46:13  rurban
 move old default transformers to baseclass

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