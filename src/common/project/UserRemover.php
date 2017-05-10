<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Project;

use ProjectManager;
use EventManager;
use ArtifactTypeFactory;
use Feedback;
use UserManager;
use ProjectHistoryDao;
use PFUser;
use Project;
use UGroupManager;

class UserRemover
{
    /**
     * @var ProjectManager
     */
    private $project_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var ArtifactTypeFactory
     */
    private $tv3_tracker_factory;

    /**
     * @var UserRemoverDao
     */
    private $dao;

    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var ProjectHistoryDao
     */
    private $project_history_dao;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        ProjectManager $project_manager,
        EventManager $event_manager,
        ArtifactTypeFactory $tv3_tracker_factory,
        UserRemoverDao $dao,
        UserManager $user_manager,
        ProjectHistoryDao $project_history_dao,
        UGroupManager $ugroup_manager
    ) {
        $this->project_manager     = $project_manager;
        $this->event_manager       = $event_manager;
        $this->tv3_tracker_factory = $tv3_tracker_factory;
        $this->dao                 = $dao;
        $this->user_manager        = $user_manager;
        $this->project_history_dao = $project_history_dao;
        $this->ugroup_manager      = $ugroup_manager;
    }

    public function removeUserFromProject($project_id, $user_id, $admin_action = true)
    {
        if (! $this->dao->removeUserFromProject($project_id, $user_id)) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('project_admin_index', 'user_not_removed')
            );

            return false;
        }

        $this->event_manager->processEvent('project_admin_remove_user', array(
            'group_id' => $project_id,
            'user_id'  => $user_id
        ));

        $project = $this->project_manager->getProject($project_id);
        if (! $project || ! is_object($project) || $project->isError()) {
            exit_no_group();
        }

        $tv3_trackers = $this->tv3_tracker_factory->getArtifactTypesFromId($project_id);

        if ($tv3_trackers && count($tv3_trackers) > 0) {
            for ($j = 0; $j < count($tv3_trackers); $j++) {
                if (! $tv3_trackers[$j]->deleteUser($user_id)) {
                    $GLOBALS['Response']->addFeedback(
                        'error',
                        $GLOBALS['Language']->getText('project_admin_index', 'del_tracker_perm_fail', $tv3_trackers[$j]->getName())
                    );
                }
            }
        }

        if (! $this->removeUserFromProjectUgroups($project, $user_id)) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('project_admin_index', 'del_user_from_ug_fail'));
        }

        $user = $this->user_manager->getUserById($user_id);

        if (! $user) {
            $user_name = $GLOBALS['Language']->getText('include_user', 'invalid_u_id');
        } else {
            $user_name = $user->getUserName();
        }

        if ($admin_action) {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_index', 'user_removed').' ('.$user_name.')');
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_index', 'self_user_remove').' ('.$project->getPublicName().')');
        }

        $this->project_history_dao->groupAddHistory(
            'removed_user',
            $user_name." ($user_id)",
            $project_id
        );

        return true;
    }

    private function removeUserFromProjectUgroups(Project $project, $user_id)
    {
        $project_id = $project->getID();
        $ugroup_ids = array();

        foreach ($this->ugroup_manager->getStaticUGroups($project) as $ugroup) {
            $ugroup_ids[] = $ugroup->getId();
        }

        if (! $this->dao->removeUserFromProjectUgroups($project_id, $user_id)) {
            return false;
        }

        $this->event_manager->processEvent('project_admin_remove_user_from_project_ugroups', array(
            'group_id' => $project_id,
            'user_id'  => $user_id,
            'ugroups'  => $ugroup_ids
        ));

        return true;
    }
}
