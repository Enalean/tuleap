<?php
/**
* Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
*
* 
*
* Docman_View_GetFieldsVisitor
*/

require_once('Docman_View_GetSpecificFieldsVisitor.class.php');
require_once(dirname(__FILE__).'/../Docman_MetadataHtml.class.php');

class Docman_View_GetFieldsVisitor /* implements Visitor*/ {
    var $mdLabelToSkip;

    function __construct($mdLabelToSkip = array()) {
        $this->mdLabelToSkip = $mdLabelToSkip;
    }

    function buildFieldArray($mdIter, $params) {
        $formName = '';
        if(isset($params['form_name'])) {
            $formName = $params['form_name'];
        }
        $themePath = '';
        if(isset($params['theme_path'])) {
            $themePath = $params['theme_path'];
        }
        $mdHtmlFactory = new Docman_MetadataHtmlFactory();
        return $mdHtmlFactory->buildFieldArray($mdIter, $this->mdLabelToSkip, false, $formName, $themePath);
    }

    function visitItem(&$item, $params = array()) {
        $mdIter = $item->getMetadataIterator();
        return $this->buildFieldArray($mdIter, $params);
    }

    function visitFolder(&$item, $params = array()) {
        $folderMetadata = array('title', 'description','create_date', 'update_date');
        $mda = array();
        foreach($folderMetadata as $mdLabel) {
            $mda[] = $item->getMetadataFromLabel($mdLabel);
        }
        $mdIter = new ArrayIterator($mda);
        return $this->buildFieldArray($mdIter, $params);
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

    function visitEmpty(&$item, $params = array()) {
        return $this->visitItem($item, $params);
    }
}
?>
