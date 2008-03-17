<?php

/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* 
*
* Docman_View_NewFolder
*/

require_once('Docman_View_New.class.php');
require_once('Docman_View_GetFieldsVisitor.class.php');
require_once(dirname(__FILE__).'/../Docman_Document.class.php');

class Docman_View_NewDocument extends Docman_View_New {
    
    function _getTitle($params) {
        return $GLOBALS['Language']->getText('plugin_docman', 'new_document');
    }
    function _getEnctype() {
        return ' enctype="multipart/form-data" ';
    }
    function _getAction() {
        return 'createDocument';
    }
    function _getActionText() {
        return $GLOBALS['Language']->getText('plugin_docman', 'new_document_action');
    }
    
    function _getSpecificProperties($params) {
        $html = '';
        $currentItemType = null;
        if(isset($params['force_item'])) {
            $currentItemType = Docman_ItemFactory::getItemTypeForItem($params['force_item']);
        }
        $specifics = array(
            array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                'label'   => $GLOBALS['Language']->getText('plugin_docman', 'new_document_empty'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMPTY)? $params['force_item'] : new Docman_Empty(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMPTY)
            ),
            array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'label'   => $GLOBALS['Language']->getText('plugin_docman', 'new_document_link'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_LINK)? $params['force_item'] : new Docman_Link(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_LINK)
                ));
        $wikiAvailable = true;
        if(isset($params['group_id'])) {
            $go = project_get_object($params['group_id']);
            $wikiAvailable = $go->usesWiki();

        }
        if($wikiAvailable) {
            $specifics[] = array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                'label'   => $GLOBALS['Language']->getText('plugin_docman', 'new_document_wiki'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI)? $params['force_item'] : new Docman_Wiki(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_WIKI)
                );
        }

        $specifics[] = array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_FILE,
                'label'   => $GLOBALS['Language']->getText('plugin_docman', 'new_document_file'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) ? $params['force_item'] : new Docman_File(),
                'checked' => ($currentItemType !== null) ? ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_FILE) : true
                );

        if ($this->_controller->getProperty('embedded_are_allowed')) {
            $specifics[] = array(
                'type'    =>  PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                'label'   => $GLOBALS['Language']->getText('plugin_docman', 'new_document_embedded'),
                'obj'     => isset($params['force_item']) && ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) ? $params['force_item'] : new Docman_EmbeddedFile(),
                'checked' => ($currentItemType == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE)
            );
        }
        $get_specific_fields = new Docman_View_GetSpecificFieldsVisitor();
        
        foreach ($specifics as $specific) {
            $html .= '<div><input type="radio" name="item[item_type]" value="'. $specific['type'] .'" id="item_item_type_'. $specific['type'] .'" '. ($specific['checked']?'checked="checked"':'') .'/>';
            $html .= '<b><label for="item_item_type_'. $specific['type'] .'">'. $specific['label'] .'</label></b></div>';
            $html .= '<div style="padding-left:20px" id="item_item_type_'. $specific['type'] .'_specific_properties">';
            $fields = $specific['obj']->accept($get_specific_fields, array('request' => &$this->controller->request));
            $html .= '<table>';
            foreach($fields as $field) {
                $html .= '<tr style="vertical-align:top;"><td><label>'. $field->getLabel() .'</label></td><td>'. $field->getField() .'</td></tr>';
            }
            $html .= '</table>';
            $html .= '</div>';
        }
        return $html;
    }

    function _getNewItem() {
        $i = new Docman_Document();
        return $i;
    }
}

?>
