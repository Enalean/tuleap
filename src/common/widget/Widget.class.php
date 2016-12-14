<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

/**
* Widget
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
    function getPreferencesForm($layout_id, $owner_id, $owner_type, CSRFSynchronizerToken $csrf_token) {
        $prefs  = '';
        $prefs .= '<form method="POST" action="/widgets/widget.php?owner='. $owner_type.$owner_id .'&amp;action=update&amp;name['. $this->id .']='. $this->getInstanceId() .'&amp;content_id='. $this->getInstanceId() .'&amp;layout_id='. $layout_id .'">';
        $prefs .= '<fieldset><legend>'. $GLOBALS['Language']->getText('widget', 'preferences_title') .'</legend>';
        $prefs .= $this->getPreferences($owner_id);
        $prefs .= '<br />';
        $prefs .= '<input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'" />&nbsp;';
        $prefs .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $prefs .= '</fieldset>';
        $prefs .= $csrf_token->fetchHTMLInput();
        $prefs .= '</form>';
        return $prefs;
    }
    function isInstallAllowed() {
        return true;
    }
    function getInstallNotAllowedMessage() {
        return '';
    }
    function getInstallPreferences($owner_id) {
        return '';
    }
    function getPreferences($owner_id) {
        return '';
    }
    function hasPreferences() {
        return false;
    }
    function updatePreferences(&$request) {
        return true;
    }
    function hasRss() {
        return false;
    }
    function getRssUrl($owner_id, $owner_type) {
        if ($this->hasRss()) {
            return '/widgets/widget.php?owner='.$owner_type.$owner_id.'&amp;action=rss&amp;name['. $this->id .']='. $this->getInstanceId();
        } else {
            return false;
        }
    }
    function isUnique() {
        return true;
    }
    function isAvailable() {
        return true;
    }
    function isAjax() {
        return false;
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

    /**
     * @return Widget
     */
    public static function getInstance($widget_name) {
        $o = null;
        switch($widget_name) {
            case 'myprojects':
                $o = new Widget_MyProjects();
                break;
            case 'mybookmarks':
                $o = new Widget_MyBookmarks();
                break;
            case 'mymonitoredforums':
                $o = new Widget_MyMonitoredForums();
                break;
            case 'mymonitoredfp':
                $o = new Widget_MyMonitoredFp();
                break;
            case 'mylatestsvncommits':
                $o = new Widget_MyLatestSvnCommits();
                break;  
            case 'myartifacts':
                $o = new Widget_MyArtifacts();
                break;
            case 'myrss':
                $o = new Widget_MyRss();
                break;
            //case 'mywikipage':                   //not yet
            //    $o = new Widget_MyWikiPage();
            //    break;
            case 'myimageviewer':
                $o = new Widget_MyImageViewer();
                break;
            case 'myadmin':
                $user_is_super_user = user_is_super_user();

                if (! $user_is_super_user) {
                    $user = UserManager::instance()->getCurrentUser();
                    $forge_ugroup_permissions_manager = new User_ForgeUserGroupPermissionsManager(
                        new User_ForgeUserGroupPermissionsDao()
                    );
                    $can_access = $forge_ugroup_permissions_manager->doesUserHavePermission(
                        $user, new User_ForgeUserGroupPermission_ProjectApproval()
                    );
                }

                if ($user_is_super_user || $can_access) { //This widget is mainly for super admin
                    $o = new Widget_MyAdmin($user_is_super_user);
                }
                break;
            case 'mysystemevent':
                if (user_is_super_user()) { //This widget is only for super admin
                    $o = new Widget_MySystemEvent();
                }
                break;
            case 'projectdescription':
                $o = new Widget_ProjectDescription();
                break;
            case 'projectclassification':
                $o = new Widget_ProjectClassification();
                break;
            case 'projectmembers':
                $o = new Widget_ProjectMembers();
                break;
            case 'projectlatestfilereleases':
                $o = new Widget_ProjectLatestFileReleases();
                break;
            case 'projectlatestnews':
                $o = new Widget_ProjectLatestNews();
                break;
            case 'projectpublicareas':
                $o = new Widget_ProjectPublicAreas();
                break;
            case 'projectrss':
                $o = new Widget_ProjectRss();
                break;
            case 'projectsvnstats':
                $o = new Widget_ProjectSvnStats();
                break;
            //case 'projectwikipage':                    //not yet
            //    $o = new Widget_ProjectWikiPage();
            //    break;
            case 'projectlatestsvncommits':
                $o = new Widget_ProjectLatestSvnCommits();
                break;
            case 'projectlatestcvscommits':
                $o = new Widget_ProjectLatestCvsCommits();
                break;
            case 'projectimageviewer':
                $o = new Widget_ProjectImageViewer();
                break;
            case 'projectcontacts' :
                $o = new Widget_Contacts();
                break;
            default:
                $em = EventManager::instance();
                $em->processEvent('widget_instance', array('widget' => $widget_name, 'instance' => &$o));
                break;
        }
        if (!$o || !is_a($o, 'Widget')) {
            $o = null;
        }
        return $o;
    }
    /* static */ function getCodendiWidgets($owner_type) {
        switch ($owner_type) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $widgets = array('myadmin', 'myprojects', 'mybookmarks',
                    'mymonitoredforums', 'mymonitoredfp', 'myartifacts', 'mybugs', //'mywikipage' //not yet
                    'mytasks', 'mysrs', 'myimageviewer', 
                    'mylatestsvncommits', 'mysystemevent', 'myrss',
                );
                break;
            case WidgetLayoutManager::OWNER_TYPE_GROUP:
                $widgets = array('projectdescription', 'projectmembers', 
                    'projectlatestfilereleases', 'projectlatestnews', 'projectpublicareas', //'projectwikipage' //not yet
                    'projectlatestsvncommits', 'projectlatestcvscommits', 'projectsvnstats',
                    'projectrss', 'projectimageviewer', 'projectcontacts'
                );
                if ($GLOBALS['sys_use_trove'] != 0) {
                    $widgets[] = 'projectclassification';
                }
                break;
            case WidgetLayoutManager::OWNER_TYPE_HOME:
                $widgets = array();
                break;
            default:
                $widgets = array();
                break;
        }
        
        $plugins_widgets = array();
        $em = EventManager::instance();
        $em->processEvent('widgets', array('codendi_widgets' => &$plugins_widgets, 'owner_type' => $owner_type));
        
        if (is_array($plugins_widgets)) {
            $widgets = array_merge($widgets, $plugins_widgets);
        }
        return $widgets;
    }
    /* static */ function getExternalWidgets($owner_type) {
        switch ($owner_type) {
            case WidgetLayoutManager::OWNER_TYPE_USER:
                $widgets = array(
                );
                break;
            case WidgetLayoutManager::OWNER_TYPE_GROUP:
                $widgets = array(
                );
                break;
            case WidgetLayoutManager::OWNER_TYPE_HOME:
                $widgets = array();
                break;
            default:
                $widgets = array();
                break;
        }
        
        $plugins_widgets = array();
        $em = EventManager::instance();
        $em->processEvent('widgets', array('external_widgets' => &$plugins_widgets, 'owner_type' => $owner_type));
        
        if (is_array($plugins_widgets)) {
            $widgets = array_merge($widgets, $plugins_widgets);
        }
        return $widgets;
    }
    
    function getCategory() {
        return 'general';
    }
    function getDescription() {
        return '';
    }
    function getPreviewCssClass() {
        $locale = $this->getCurrentUser()->getLocale();
        return 'widget-preview-'.($this->id).'-'.$locale;
    }

    /**
     * @return PFUser
     */
    function getCurrentUser() {
        return UserManager::instance()->getCurrentUser();
    }

    function getAjaxUrl($owner_id, $owner_type) {
        return '/widgets/widget.php?owner='. $owner_type.$owner_id .'&action=ajax&name['. $this->id .']='. $this->getInstanceId();
    }
    function getIframeUrl($owner_id, $owner_type) {
        return '/widgets/widget.php?owner='. $owner_type.$owner_id .'&action=iframe&name['. $this->id .']='. $this->getInstanceId();
    }
}
