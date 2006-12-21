<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_GetSpecificFieldsVisitor
*/

require_once(dirname(__FILE__).'/../Docman_ValidateUpload.class.php');
require_once(dirname(__FILE__).'/../Docman_MetadataHtml.class.php');

class Docman_MetadataHtmlWiki extends Docman_MetadataHtml {
    var $pagename;

    function Docman_MetadataHtmlWiki($pagename) {
        $this->pagename = $pagename;
    }

    function getLabel() {
        return $GLOBALS['Language']->getText('plugin_docman', 'specificfield_pagename');
    }
    
    function getField() {
        return '<input type="text" name="item[wiki_page]" value="'. htmlentities($this->pagename, ENT_QUOTES) .'" /> '. $GLOBALS['Language']->getText('plugin_docman', 'warn_wiki_perms');
    }

    function &getValidator() {
        $msg = $GLOBALS['Language']->getText('plugin_docman', 'error_field_wiki_required');
        $validator = new Docman_ValidateValueNotEmpty($this->pagename, $msg);
        return $validator;
    }

}

class Docman_MetadataHtmlLink extends Docman_MetadataHtml {
    var $link_url;

    function Docman_MetadataHtmlLink($link_url) {
        $this->link_url = $link_url;
    }

    function getLabel() {
        return $GLOBALS['Language']->getText('plugin_docman', 'specificfield_url');
    }
    
    function getField() {
        return '<input type="text" name="item[link_url]" value="'. htmlentities($this->link_url, ENT_QUOTES) .'" />';
    }

    function &getValidator() {
        $msg = $GLOBALS['Language']->getText('plugin_docman', 'error_field_link_required');
        $validator = new Docman_ValidateValueNotEmpty($this->link_url, $msg);
        return $validator;
    }

}

class Docman_MetadataHtmlFile extends Docman_MetadataHtml {
   
    function Docman_MetadataHtmlFile() {
        
    }

    function getLabel() {
        return $GLOBALS['Language']->getText('plugin_docman', 'specificfield_embeddedcontent');
    }
    
    function getField() {
        $html = '<input type="hidden" name="max_file_size" value="'. $GLOBALS['sys_max_size_upload'] .'" /><input type="file" name="file" />';
        $html .= '<br /><em>'. $GLOBALS['Language']->getText('plugin_docman','max_size_msg',array(formatByteToMb($GLOBALS['sys_max_size_upload']))) .'</em>';

        return $html;
    }

    function &getValidator() {
        $validator = new Docman_ValidateUpload();
        return $validator;
    }

}

class Docman_MetadataHtmlEmbeddedFile extends Docman_MetadataHtml {
    var $content;
    function Docman_MetadataHtmlEmbeddedFile($content) {
        $this->content = $content;
    }

    function getLabel() {
        return $GLOBALS['Language']->getText('plugin_docman', 'specificfield_embeddedcontent');
    }
    
    function getField() {
        return '<textarea name="content" cols="50" rows="15">'. $this->content .'</textarea>';
    }

    function &getValidator() {
        $validator = null;
        return $validator;
    }

}

class Docman_View_GetSpecificFieldsVisitor {
    
    function visitFolder(&$item, $params = array()) {
        return array();
    }
    function visitWiki(&$item, $params = array()) {
        $pagename = '';
        if(isset($params['force_item'])) {
            if(Docman_ItemFactory::getItemTypeForItem($params['force_item']) == PLUGIN_DOCMAN_ITEM_TYPE_WIKI) {
                $pagename = $params['force_item']->getPagename();
            }
        }
        else {
            $pagename = $item->getPagename();
        }
        return array(new Docman_MetadataHtmlWiki($pagename));
    }
    
    function visitLink(&$item, $params = array()) {
        $link_url = '';
        if(isset($params['force_item'])) {
            if($params['force_item']->getType() == PLUGIN_DOCMAN_ITEM_TYPE_LINK) {
                $link_url = $params['force_item']->getUrl();
            }
        }
        else {
            $link_url = $item->getUrl();
        }
        return array(new Docman_MetadataHtmlLink($link_url));
    }
    
    function visitFile(&$item, $params = array()) {
        return array(new Docman_MetadataHtmlFile());
    }
    
    function visitEmbeddedFile(&$item, $params = array()) {
        $content = '';
        $version =& $item->getCurrentVersion();
        if ($version && is_file($version->getPath())) {
            $content = file_get_contents($version->getPath());
        }
        return array(new Docman_MetadataHtmlEmbeddedFile($content));    
    }
}
?>