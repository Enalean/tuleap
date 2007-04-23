<?php
require_once('common/widget/Widget_MySurveys.class.php');
require_once('common/widget/Widget_MyProjects.class.php');
require_once('common/widget/Widget_MyBookmarks.class.php');
require_once('common/widget/Widget_MyMonitoredForums.class.php');
require_once('common/widget/Widget_MyMonitoredFp.class.php');
require_once('common/widget/Widget_MyArtifacts.class.php');
require_once('common/widget/Widget_MyBugs.class.php');
require_once('common/widget/Widget_MySrs.class.php');
require_once('common/widget/Widget_MyTasks.class.php');
require_once('common/widget/Widget_MyRss.class.php');
/**
* Widget
* 
* TODO: description
* 
* Copyright (c) Xerox Corporation, CodeX Team, 2001-2007. All rights reserved
*
* @author  N. Terray
*/
/* abstract */ class Widget {
    
    var $id;
    var $hasPreferences;
    /**
    * Constructor
    */
    function Widget($id) {
        $this->id = $id;
    }
    
    function display($layout_id, $column_id, $is_minimized, $display_preferences) {
        if ($this->canBeDisplayed()) {
            $GLOBALS['HTML']->widget('widget_'.$this->id, $this->_getTitle(), $this->_getContent(), $layout_id, $column_id, $is_minimized, strlen($this->getPreferences()), ($display_preferences ? $this->getPreferences() : ''), $this->hasRss());
        }
    }
    function _getTitle() {
        return '';
    }
    function _getContent() {
        return '';
    }
    function canBeDisplayed() {
        return true;
    }
    function getPreferences() {
        return '';
    }
    function updatePreferences(&$request) {
        return true;
    }
    function getInstance($widget_name) {
        $o = null;
        switch($widget_name) {
            case 'mysurveys':
                $o =& new Widget_MySurveys();
                break;
            case 'myprojects':
                $o =& new Widget_MyProjects();
                break;
            case 'mybookmarks':
                $o =& new Widget_MyBookmarks();
                break;
            case 'mymonitoredforums':
                $o =& new Widget_MyMonitoredForums();
                break;
            case 'mymonitoredfp':
                $o =& new Widget_MyMonitoredFp();
                break;  
            case 'myartifacts':
                $o =& new Widget_MyArtifacts();
                break;
            case 'mybugs':
                $o =& new Widget_MyBugs();
                break;
            case 'mytasks':
                $o =& new Widget_MyTasks();
                break;
            case 'mysrs':
                $o =& new Widget_MySrs();
                break;
            case 'myrss':
                $o =& new Widget_MyRss();
                break;
            default:
                //TODO: handle portlets in plugins
                $em =& EventManager::instance();
                $em->processEvent('portlet_instance', array('widget' => $widget_name, 'instance' => &$o));
                break;
        }
        return $o;
    }
    function hasRss() {
        return false;
    }
}
?>
