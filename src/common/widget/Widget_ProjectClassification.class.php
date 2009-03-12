<?php

require_once('Widget.class.php');

/**
* Widget_ProjectClassification
* 
* Copyright (c) Xerox Corporation, Codendi 2001-2009.
*
* @author  marc.nazarian@xrce.xerox.com
*/
class Widget_ProjectClassification extends Widget {
    public function __construct() {
        $this->Widget('projectclassification');
    }
    public function getTitle() {
        return $GLOBALS['Language']->getText('include_project_home','project_classification');
    }
    public function getContent() {
        $request =& HTTPRequest::instance();
        $group_id = $request->get('group_id');
        if ($GLOBALS['sys_use_trove'] != 0) {
            trove_getcatlisting($group_id,0,1);
        }       
    }
    public function canBeUsedByProject(&$project) {
        return true;
    }
    function getPreviewCssClass() {
        return parent::getPreviewCssClass('project_classification');
    }
}
?>