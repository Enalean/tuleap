<?php
require_once('common/widget/WidgetLayoutManager.class.php');

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
require_once('common/widget/Widget_MyAdmin.class.php');
require_once('common/widget/Widget_ProjectLatestFileReleases.class.php');
require_once('common/widget/Widget_ProjectLatestNews.class.php');
require_once('common/widget/Widget_ProjectPublicAreas.class.php');
require_once('common/widget/Widget_ProjectRss.class.php');
require_once('common/widget/Widget_ProjectLatestSvnCommits.class.php');
require_once('common/widget/Widget_ProjectLatestCvsCommits.class.php');

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
    
    var $content_id;
    var $id;
    var $hasPreferences;
    var $owner_id;
    var $owner_type;
    /**
    * Constructor
    */
    function Widget($id) {
        $this->id = $id;
        $this->content_id = 0;
    }
    
    function display($layout_id, $column_id, $readonly, $is_minimized, $display_preferences, $owner_id, $owner_type) {
        $GLOBALS['HTML']->widget($this, $layout_id, $readonly, $column_id, $is_minimized, $display_preferences, $owner_id, $owner_type);
    }
    function getTitle() {
        return '';
    }
    function getContent() {
        return '';
    }
    function getPreferencesForm($layout_id, $owner_id, $owner_type) {
        $prefs  = '';
        $prefs .= '<form method="POST" action="/widgets/widget.php?owner='. $owner_type.$owner_id .'&amp;action=update&amp;name['. $this->id .']='. $this->getInstanceId() .'&amp;content_id='. $this->getInstanceId() .'&amp;layout_id='. $layout_id .'">';
        $prefs .= '<fieldset><legend>'. $GLOBALS['Language']->getText('widget', 'preferences_title') .'</legend>';
        $prefs .= $this->getPreferences();
        $prefs .= '<br />';
        $prefs .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />&nbsp;';
        $prefs .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $prefs .= '</fieldset>';
        $prefs .= '</form>';
        return $prefs;
    }
    function getInstallPreferences() {
        return '';
    }
    function getPreferences() {
        return '';
    }
    function updatePreferences(&$request) {
        return true;
    }
    function hasRss() {
        return false;
    }
    function isUnique() {
        return true;
    }
    function isAvailable() {
        return true;
    }
    function getInstanceId() {
        return $this->content_id;
    }
    function loadContent($id) {
    }
    function setOwner($owner_id, $owner_type) {
        $this->owner_id = $owner_id;
        $this->owner_type = $owner_type;
    }
    function canBeUsedByProject(&$project) {
        return false;
    }
    /**
    * cloneContent
    * 
    * Take the content of a widget, clone it and return the id of the new content
    * 
    * @param $id the id of the content to clone
    * @param $owner_id the owner of the widget of the new widget
    * @param $owner_type the type of the owner of the new widget (see WidgetLayoutManager)
    */
    function cloneContent($id, $owner_id, $owner_type) {
        return $this->getInstanceId();
    }
    function create(&$request) {
    }
    function destroy($id) {
    }
    /* static */ function & getInstance($widget_name) {
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
            case 'myadmin':
                if (user_is_super_user()) { //This widget is only for super admin
                    $o =& new Widget_MyAdmin();
                }
                break;
            case 'projectlatestfilereleases':
                $o =& new Widget_ProjectLatestFileReleases();
                break;
            case 'projectlatestnews':
                $o =& new Widget_ProjectLatestNews();
                break;
            case 'projectpublicareas':
                $o =& new Widget_ProjectPublicAreas();
                break;
            case 'projectrss':
                $o =& new Widget_ProjectRss();
                break;
            case 'projectlatestsvncommits':
                $o =& new Widget_ProjectLatestSvnCommits();
                break;
            case 'projectlatestcvscommits':
                $o =& new Widget_ProjectLatestCvsCommits();
                break;
            default:
                $em =& EventManager::instance();
                $em->processEvent('widget_instance', array('widget' => $widget_name, 'instance' => &$o));
                break;
        }
        if (!$o || !is_a($o, 'Widget')) {
            $o = null;
        }
        return $o;
    }
    /* static */ function getCodeXWidgets($owner_type) {
        $lm = new WidgetLayoutManager();
        switch ($owner_type) {
            case $lm->OWNER_TYPE_USER:
                $widgets = array('myadmin', 'mysurveys', 'myprojects', 'mybookmarks', 
                    'mymonitoredforums', 'mymonitoredfp', 'myartifacts', 'mybugs',
                    'mytasks', 'mysrs'
                );
                break;
            case $lm->OWNER_TYPE_GROUP:
                $widgets = array('projectlatestfilereleases', 'projectlatestnews', 
                    'projectpublicareas', 'projectlatestsvncommits', 'projectlatestcvscommits'
                );
                break;
            case $lm->OWNER_TYPE_HOME:
                $widgets = array();
                break;
            default:
                $widgets = array();
                break;
        }
        
        $plugins_widgets = array();
        $em =& EventManager::instance();
        $em->processEvent('widgets', array('codex_widgets' => &$plugins_widgets, 'owner_type' => $owner_type));
        
        if (is_array($plugins_widgets)) {
            $widgets = array_merge($widgets, $plugins_widgets);
        }
        return $widgets;
    }
    /* static */ function getExternalWidgets($owner_type) {
        $lm = new WidgetLayoutManager();
        switch ($owner_type) {
            case $lm->OWNER_TYPE_USER:
                $widgets = array('myrss'
                );
                break;
            case $lm->OWNER_TYPE_GROUP:
                $widgets = array('projectrss'
                );
                break;
            case $lm->OWNER_TYPE_HOME:
                $widgets = array();
                break;
            default:
                $widgets = array();
                break;
        }
        
        $plugins_widgets = array();
        $em =& EventManager::instance();
        $em->processEvent('widgets', array('external_widgets' => &$plugins_widgets, 'owner_type' => $owner_type));
        
        if (is_array($plugins_widgets)) {
            $widgets = array_merge($widgets, $plugins_widgets);
        }
        return $widgets;
    }
}
?>
