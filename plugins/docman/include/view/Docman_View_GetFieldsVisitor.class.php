<?php
/**
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
* 
* $Id$
*
* Docman_View_GetFieldsVisitor
*/

require_once('Docman_View_GetSpecificFieldsVisitor.class.php');
require_once(dirname(__FILE__).'/../Docman_MetadataHtml.class.php');

class Docman_View_GetFieldsVisitor /* implements Visitor*/ {
    var $mdLabelToSkip;

    function Docman_View_GetFieldsVisitor($mdLabelToSkip = array()) {
        $this->mdLabelToSkip = $mdLabelToSkip;
    }

    function _buildFieldArray(&$mdIter, $formName, $themePath) {
        $mdIter->rewind();
        while($mdIter->valid()) {
            $md =& $mdIter->current();                      
            
            if(!in_array($md->getLabel(), $this->mdLabelToSkip)) {

                $fields[$md->getLabel()] = Docman_MetadataHtmlFactory::getFromMetadata($md, array('form_name' => $formName,
                                                                                                  'theme_path' => $themePath));
            }

            $mdIter->next();
        }
        return $fields;       
    }

    function visitItem(&$item, $params = array()) {
        $mdIter =& $item->getMetadataIterator();
        $formName = '';
        if(isset($params['form_name'])) {
            $formName = $params['form_name'];
        }
        $themePath = '';
        if(isset($params['theme_path'])) {
            $themePath = $params['theme_path'];
        }
        return $this->_buildFieldArray($mdIter, $formName, $themePath);
    }
    function visitFolder(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    function visitDocument(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    function visitWiki(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    function visitLink(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    function visitFile(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
    function visitEmbeddedFile(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
}
?>