<?php
/**
 * Copyright (c) Enalean, 2013 - 2017. All Rights Reserved.
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

use Tuleap\Widget\WidgetFactory;

/**
* WidgetLayoutManager
*
* Manage layouts for users, groups and homepage
*/

class WidgetLayoutManager
{
    const OWNER_TYPE_USER  = 'u';
    const OWNER_TYPE_GROUP = 'g';
    const OWNER_TYPE_HOME  = 'h';

    /**
     * @var WidgetFactory
     */
    private $widget_factory;

    /**
     * @var EventManager
     */
    private $event_manager;


    public function __construct()
    {
        $this->event_manager  = EventManager::instance();
        $this->widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            $this->event_manager
        );
    }

    /**
    * createDefaultLayoutForUser
    *
    * Create the first layout for the user and add some initial widgets:
    * - MyArtifacts
    * - MyProjects
    * - MyBookmarks
    * - MyMonitoredFP
    * - MyMonitoredForums
    * - and widgets of plugins if they want to listen to the event default_widgets_for_new_owner
    *
    * @param  owner_id The id of the newly created user
    */
    function createDefaultLayoutForUser($owner_id) {
        $service_manager = ServiceManager::instance();
        $owner_id   = db_ei($owner_id);
        $owner_type = db_es(self::OWNER_TYPE_USER);
        $sql = "INSERT INTO owner_layouts(layout_id, is_default, owner_id, owner_type) VALUES (1, 1, $owner_id, '$owner_type')";
        if (db_query($sql)) {

            $sql = "INSERT INTO layouts_contents(owner_id, owner_type, layout_id, column_id, name, rank) VALUES ";
            $sql .= "($owner_id, '$owner_type', 1, 1, 'myprojects', 0)";
            $sql .= ",($owner_id, '$owner_type', 1, 1, 'mybookmarks', 1)";
            if ($service_manager->isServiceAvailableAtSiteLevelByShortName(Service::FORUM)) {
                $sql .= ",($owner_id, '$owner_type', 1, 1, 'mymonitoredforums', 2)";
            }
            if ($service_manager->isServiceAvailableAtSiteLevelByShortName(Service::FILE)) {
                $sql .= ",($owner_id, '$owner_type', 1, 2, 'mymonitoredfp', 20)";
            }

            $widgets = array();
            $this->event_manager->processEvent('default_widgets_for_new_owner', array('widgets' => &$widgets, 'owner_type' => $owner_type));
            foreach($widgets as $widget) {
                $sql .= ",($owner_id, '$owner_type', 1, ". db_ei($widget['column']) .", '". db_es($widget['name']) ."', ". db_ei($widget['rank']) .")";
            }
            db_query($sql);
        }
        echo db_error();
    }

    /**
    * createDefaultLayoutForProject
    *
    * Create the first layout for a new project, based on its parent template.
    * Add some widgets based also on its parent configuration and on its service configuration.
    *
    * @param  group_id  the id of the newly created project
    * @param  template_id  the id of the project template
    */
    function createDefaultLayoutForProject($group_id, $template_id) {
        $pm = ProjectManager::instance();
        $pm->clear($group_id);
        $project     = $pm->getProject($group_id);
        $group_id    = db_ei($group_id);
        $template_id = db_ei($template_id);
        $sql = "INSERT INTO owner_layouts(layout_id, is_default, owner_id, owner_type)
        SELECT layout_id, is_default, $group_id, owner_type
        FROM owner_layouts
        WHERE owner_type = '". db_es(self::OWNER_TYPE_GROUP) ."'
          AND owner_id = $template_id
        ";
        if (db_query($sql)) {
            $sql = "SELECT layout_id, column_id, name, rank, is_minimized, is_removed, display_preferences, content_id
            FROM layouts_contents
            WHERE owner_type = '". db_es(self::OWNER_TYPE_GROUP) ."'
              AND owner_id = $template_id
            ";
            if ($req = db_query($sql)) {
                while($data = db_fetch_array($req)) {
                    $w = $this->widget_factory->getInstanceByWidgetName($data['name']);
                    if ($w) {
                        $w->setOwner($template_id, self::OWNER_TYPE_GROUP);
                        if ($w->canBeUsedByProject($project)) {
                            $content_id = $w->cloneContent($data['content_id'], $group_id, self::OWNER_TYPE_GROUP);
                            $sql = "INSERT INTO layouts_contents(owner_id, owner_type, content_id, layout_id, column_id, name, rank, is_minimized, is_removed, display_preferences)
                            VALUES (". $group_id .", '". db_es(self::OWNER_TYPE_GROUP) ."', ". db_ei($content_id) .", ". db_ei($data['layout_id']) .", ". db_ei($data['column_id']) .", '". db_es($data['name']) ."', ". db_ei($data['rank']) .", ". db_ei($data['is_minimized']) .", ". db_ei($data['is_removed']) .", ". db_ei($data['display_preferences']) .")
                            ";
                            db_query($sql);
                            echo db_error();
                        }
                    }
                }
            }
        }
        echo db_error();
    }
}
