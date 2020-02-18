<?php
/**
 * Copyright (c) Enalean, 2013 - 2018. All Rights Reserved.
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
 * hudson */
class hudson extends Controler
{

    public function request()
    {
        $request = HTTPRequest::instance();
        $vgi = new Valid_GroupId();
        $vgi->required();
        if ($request->valid($vgi)) {
            $group_id = $request->get('group_id');
            $pm = ProjectManager::instance();
            $project = $pm->getProject($group_id);
            if ($project->usesService('hudson')) {
                $user = UserManager::instance()->getCurrentUser();
                if ($user->isMember($group_id)) {
                    switch ($request->get('action')) {
                        case 'add_job':
                            if ($user->isMember($group_id, 'A')) {
                                if ($request->exist('hudson_job_url') && trim($request->get('hudson_job_url') != '')) {
                                    $this->action = 'addJob';
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'job_url_missing'));
                                }
                                $this->view = 'projectOverview';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                                $this->view = 'projectOverview';
                            }
                            break;
                        case 'edit_job':
                            if ($user->isMember($group_id, 'A')) {
                                if ($request->exist('job_id')) {
                                    $this->view = 'editJob';
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'job_id_missing'));
                                }
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                                $this->view = 'projectOverview';
                            }
                            break;
                        case 'update_job':
                            if ($user->isMember($group_id, 'A')) {
                                if ($request->exist('job_id')) {
                                    if ($request->exist('hudson_job_url') && $request->get('hudson_job_url') != '') {
                                        $this->action = 'updateJob';
                                    } else {
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'job_url_missing'));
                                    }
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'job_id_missing'));
                                }
                                $this->view = 'projectOverview';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                                $this->view = 'projectOverview';
                            }
                            break;
                        case 'delete_job':
                            if ($user->isMember($group_id, 'A')) {
                                if ($request->exist('job_id')) {
                                    $this->action = 'deleteJob';
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'job_id_missing'));
                                }
                                $this->view = 'projectOverview';
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                                $this->view = 'projectOverview';
                            }
                            break;
                        case "view_job":
                            $this->view = 'job_details';
                            break;
                        case "view_build":
                            $this->view = 'build_number';
                            break;
                        case "view_last_test_result":
                            $this->view = 'last_test_result';
                            break;
                        case "view_test_trend":
                            $this->view = 'test_trend';
                            break;
                        default:
                            $this->view = 'projectOverview';
                            break;
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'perm_denied'));
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'service_not_used'));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_hudson', 'group_id_missing'));
        }
    }
}
