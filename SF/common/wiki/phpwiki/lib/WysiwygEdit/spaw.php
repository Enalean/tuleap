<?php
/**
 * Just IE 5.5+ and Gecko
 *
 * Download: http://sourceforge.net/projects/spaw
 * requires installation of spaw as lib/spaw
 * modify lib/spaw/config/spaw_control.config.php to your needs.
 *
 * @package WysiwygEdit
 * @author Reini Urban
 */

require_once("lib/WysiwygEdit.php");

class WysiwygEdit_spaw extends WysiwygEdit {

    function Head($name='edit[content]') {
        $basepath = DATA_PATH.'/lib/spaw/';
        $spaw_root = PHPWIKI_DIR . "/lib/spaw/";
        $spaw_base_url = "$basepath";
        $spaw_dir  = "$basepath";
        $this->spaw_root =& $spaw_root;
        include_once($spaw_root. "spaw_control.class.php");
    }

    function Textarea($textarea, $wikitext, $name='edit[content]') {
        // global $LANG, $WikiTheme;
        $id = "spaw_editor";
        /*SPAW_Wysiwyg(
              $control_name='spaweditor', // control's name
              $value='',                  // initial value
              $lang='',                   // language
              $mode = '',                 // toolbar mode
              $theme='',                  // theme (skin)
              $width='100%',              // width
              $height='300px',            // height
              $css_stylesheet='',         // css stylesheet file for content
              $dropdown_data=''           // data for dropdowns (style, font, etc.) 
        */
        $this->SPAW = new SPAW_Wysiwyg($id, $textarea->_content);
        $textarea->SetAttr('id', $name);
        $this->SPAW->show();
        $out = HTML::div(array("id"=>$id, 'style'=>'display:none'),
                                         $wikitext);
        return $out;
    }
}

/*
 $Log: spaw.php,v $
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