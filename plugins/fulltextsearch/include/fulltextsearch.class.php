<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/mvc/Controler.class.php');
require_once('fulltextsearchViews.class.php');
require_once('fulltextsearchActions.class.php');
/**
 * fulltextsearch */
class fulltextsearch extends Controler {
    
    private $themePath;
    private $plugin;
    
    function fulltextsearch() {
        $p = PluginFactory::instance()->getPluginByName('fulltextsearch');
        $this->plugin = $p;
        $this->themePath = $p->getThemePath();
    }
    
    function getPlugin() {
        return $this->plugin;
    }
    function getThemePath() {
        return $this->themePath;
    }
    function getIconsPath() {
        return $this->themePath . "/images/ic/";
    }
    
    function request() {
        $request =& HTTPRequest::instance();
        $vgi = new Valid_GroupId();
        $vgi->required();
        if ($request->valid($vgi)) {
            $group_id = $request->get('group_id');
            $pm = ProjectManager::instance();
            $project = $pm->getProject($group_id);
            if ($project->usesService('fulltextsearch')) {
                $user = UserManager::instance()->getCurrentUser();
                if ($user->isMember($group_id)) {
                    switch($request->get('action')) {
                        case 'projectsearch':
                            $this->view = 'projectSearch';
                            break;
                        default:
                            $this->view = 'projectGlobalSearch';
                            break;
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                }
                
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_fulltextsearch','service_not_used'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_fulltextsearch','group_id_missing'));
        }
    }
}

?>