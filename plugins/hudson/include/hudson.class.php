<?php

/*
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Marc Nazarian, 2008. Xerox Codendi Team.
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('common/mvc/Controler.class.php');
require_once('hudsonViews.class.php');
require_once('hudsonActions.class.php');
/**
 * hudson */
class hudson extends Controler {
    
    private $themePath;
    
    function hudson() {
        $p = PluginFactory::instance()->getPluginByName('hudson');
        $this->themePath = $p->getThemePath();
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
            $project = project_get_object($group_id);
            if ($project->usesService('hudson')) {
                
                switch($request->get('action')) {
                    case 'add_job':
                        if (user_ismember($group_id,'A')) {
                            if ( $request->exist('hudson_job_url') && trim($request->get('hudson_job_url') != '') ) {
                                $this->action = 'addJob';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_url_missing'));
                            }
                            $this->view = 'projectOverview';
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    case 'edit_job':
                        if (user_ismember($group_id,'A')) {
                            if ($request->exist('job_id')) {
                                $this->action = 'editJob';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_id_missing'));
                            }
                            $this->view = 'projectOverview';
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    case 'delete_job':
                        if (user_ismember($group_id,'A')) {
                            if ($request->exist('job_id')) {
                                $this->action = 'deleteJob';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','job_id_missing'));
                            }
                            $this->view = 'projectOverview';
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global','perm_denied'));
                            $this->view = 'projectOverview';
                        }
                        break;
                    default:
                        $this->view = 'projectOverview';
                        break;
                }
                
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','service_not_used'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson','group_id_missing'));
        }
    }
}

?>