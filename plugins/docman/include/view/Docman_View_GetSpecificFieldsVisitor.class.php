<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
* 
* 
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
        $hp =& Codendi_HTMLPurifier::instance();
        return '<input type="text" class="docman_text_field" name="item[wiki_page]" value="'. $hp->purify($this->pagename) .'" /> ';
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
        $hp =& Codendi_HTMLPurifier::instance();
        return '<input type="text" class="docman_text_field" name="item[link_url]" value="'. $hp->purify($this->link_url) .'" />';
    }

    function &getValidator() {
        $msg = $GLOBALS['Language']->getText('plugin_docman', 'error_field_link_required');
        $validator = new Docman_ValidateValueNotEmpty($this->link_url, $msg);
        return $validator;
    }

}

class Docman_MetadataHtmlCloudstorage extends Docman_MetadataHtml {
    var $documentId; //cloudstorage url
	var $serviceName;
	
    function Docman_MetadataHtmlCloudstorage($documentId, $serviceName) {
        $this->documentId = $documentId;
        $this->serviceName = $serviceName;
    }

    function getLabel() {
        return $GLOBALS['Language']->getText('plugin_docman', 'specificfield_cloudstorage_folderid');
    }
    
    function getField() {
        $hp =& Codendi_HTMLPurifier::instance();
        $html = '
			<script language="javascript">
				function affichage_popup(nom_de_la_page, nom_interne_de_la_fenetre) {
					window.open(nom_de_la_page, nom_interne_de_la_fenetre, config="height=480, width=640, toolbar=no, menubar=no, scrollbars=yes, resizable=yes, location=no, directories=no, status=no")
				}
			</script>
		';

        $html .= '<input type="text" name="item[cs_docid]" id="cs_docid" size="45" value="'. $hp->purify($this->documentId) .'" />';
        $html .= '<select name="item[cs_service]">
        			<option value="null">Choose service...</option>
        			<option value="dropbox" onclick="javascript:affichage_popup(\'https://'.$_SERVER['HTTP_HOST'].'/plugins/cloudstorage/?group_id=1&action=dropbox&docman=yes\', \'Select folder name from your Dropbox storage\');">Dropbox</option>
        			<option value="drive" onclick="javascript:affichage_popup(\'https://'.$_SERVER['HTTP_HOST'].'/plugins/cloudstorage/?group_id=1&action=drive&docman=yes\', \'Select folder id from your Drive storage\');">Google Drive</option>
        		  </select>
        ';

        return $html;
    }

    function &getValidator() {
        $msg = $GLOBALS['Language']->getText('plugin_docman', 'error_field_link_required');
        $validator = new Docman_ValidateValueNotEmpty($this->documentId, $msg);
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

    function &getValidator(&$request) {
        $validator = new Docman_ValidateUpload($request);
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
        $hp =& Codendi_HTMLPurifier::instance();
        $html = '<script type="text/javascript" src="/scripts/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
var embedded_content_rte = null;
var Codendi_RTE = Class.create({
        initialize:function(element) {
            this.element = $(element);
            this.rte     = false;
            Element.insert(this.element, {before: \'<div><a href="javascript:embedded_rte.toggle();">Toggle rich text formatting</a></div>\'});
        },
        init_rte: function() {
            tinyMCE.init({
                    // General options
                    mode : "exact",
                    elements : this.element.id,
                    theme : "advanced",
                    language : "'. substr(UserManager::instance()->getCurrentUser()->getLocale(), 0, 2) .'",
                    
                    plugins : "safari,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,pagebreak",
                    
                    // Theme options
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_statusbar_location : "bottom",
                    theme_advanced_resizing : true,
                    theme_advanced_disable : "styleselect",
                    theme_advanced_buttons1_add_before : "",
                    theme_advanced_buttons1_add : "fontselect,fontsizeselect",
                    theme_advanced_buttons2_add_before : "insertdate,inserttime,preview,separator,forecolor,backcolor,|",
                    theme_advanced_buttons3_add_before : "tablecontrols,separator",
                    theme_advanced_buttons3_add : "emotions,media,advhr,separator,ltr,rtl,separator,fullscreen",
                    theme_advanced_buttons4 : "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator,insertlayer,moveforward,movebackward,absolute,|,styleprops,|,cite,abbr,acronym,|,visualchars,nonbreaking,blockquote,pagebreak,|,insertfile,insertimage",
                    
                    codendi:null //cheat to not have to remove the last comma in elements above. #*%@ IE !
            });
            this.rte = true;
        },
        toggle: function() {
            if (!this.rte) {
                this.init_rte();
            } else {
                if (!tinyMCE.get(this.element.id)) {
                    tinyMCE.execCommand("mceAddControl", false, this.element.id);
                } else {
                    tinyMCE.execCommand("mceRemoveControl", false, this.element.id);
                }
            }
        }
});
var embedded_rte = null;
document.observe("dom:loaded", function() { embedded_rte = new Codendi_RTE("embedded_content"); } );
</script>';
        $html .= '<textarea id="embedded_content" name="content" cols="80" rows="20">'. $hp->purify($this->content) .'</textarea>';
        return $html;
    }

    function &getValidator() {
        $validator = null;
        return $validator;
    }

}

/**
 */
class Docman_MetadataHtmlEmpty extends Docman_MetadataHtml {

    function Docman_MetadataHtmlEmpty() {
    }

    function getLabel() {
        return $GLOBALS['Language']->getText('plugin_docman', 'specificfield_empty');
    }
    
    function getField() {
        return '';
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
    
    function visitCloudstorage(&$item, $params = array()) {
        $link_url = '';
        $serviceName = '';
        if(isset($params['force_item'])) {
            if($params['force_item']->getType() == PLUGIN_DOCMAN_ITEM_TYPE_CLOUDSTORAGE) {
                $link_url = $params['force_item']->getDocumentId();
                $serviceName = $params['force_item']->getServiceName();
            }            
        }
        else {
            $link_url = $item->getDocumentId();
            $serviceName = $item->getServiceName();
        }
        return array(new Docman_MetadataHtmlCloudstorage($link_url, $serviceName));
    }    
    
    function visitFile(&$item, $params = array()) {
        return array(new Docman_MetadataHtmlFile($params['request']));
    }
    
    function visitEmbeddedFile(&$item, $params = array()) {
        $content = '';
        $version = $item->getCurrentVersion();
        if ($version) {
            $content = $version->getContent();
        }
        return array(new Docman_MetadataHtmlEmbeddedFile($content));
    }
    
    function visitEmpty(&$item, $params = array()) {
        return array(new Docman_MetadataHtmlEmpty());
    }
}
?>
