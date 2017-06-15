<?php
/**
 * Copyright (c) Enalean, 2011 - 2017. All Rights Reserved.
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
    public function __construct($id) {
        $this->id         = $id;
        $this->content_id = 0;
    }

    public function getId()
    {
        return $this->id;
    }

    function display($layout_id, $column_id, $readonly, $is_minimized, $owner_id, $owner_type) {
        $GLOBALS['HTML']->widget($this, $layout_id, $readonly, $column_id, $is_minimized, $owner_id, $owner_type);
    }
    function getTitle() {
        return '';
    }
    function getContent() {
        return '';
    }

    function isInstallAllowed() {
        return true;
    }
    function getInstallNotAllowedMessage() {
        return '';
    }
    function getInstallPreferences() {
        return '';
    }
    function getPreferences() {
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
            return '/widgets/?'. http_build_query(
                array(
                    'owner'  => $owner_type . $owner_id,
                    'action' => 'rss',
                    'name'   => array(
                        $this->id => $this->getInstanceId()
                    )
                )
            );
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

    public static function getWidgetsForOwnerType($owner_type) {
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

    public function getAjaxUrl($owner_id, $owner_type, $dashboard_id)
    {
        $request = HTTPRequest::instance();

        return $request->getServerUrl(). '/widgets/?'.http_build_query(
            array(
                'dashboard_id' => $dashboard_id,
                'owner'        => $owner_type.$owner_id,
                'action'       => 'ajax',
                'name'         => array(
                    $this->id => $this->getInstanceId()
                )
            )
        );
    }

    public function getContentForBurningParrot()
    {
        return $this->getContent();
    }

    public function getPreferencesForBurningParrot($widget_id)
    {
        return '';
    }

    public function getInstallPreferencesForBurningParrot()
    {
        return '';
    }

    public function displayRss()
    {
        return '';
    }
}
