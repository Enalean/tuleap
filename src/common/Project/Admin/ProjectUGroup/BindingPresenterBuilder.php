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

namespace Tuleap\Project\Admin\ProjectUGroup;

use CSRFSynchronizerToken;
use EventManager;
use Project;
use ProjectManager;
use ProjectUGroup;
use UGroupBinding;
use UserManager;

class BindingPresenterBuilder
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventManager
     */
    private $event_manager;
    /**
     * @var UGroupBinding
     */
    private $ugroup_binding;
    /**
     * @var ProjectManager
     */
    private $project_manager;

    public function __construct(
        UGroupBinding $ugroup_binding,
        ProjectManager $project_manager,
        UserManager $user_manager,
        EventManager $event_manager
    ) {
        $this->ugroup_binding  = $ugroup_binding;
        $this->project_manager = $project_manager;
        $this->user_manager    = $user_manager;
        $this->event_manager   = $event_manager;
    }

    public function build(ProjectUGroup $ugroup, CSRFSynchronizerToken $csrf)
    {
        $collection = new BindingAdditionalModalPresenterCollection($ugroup, $csrf);
        $this->event_manager->processEvent($collection);

        return new BindingPresenter(
            $this->getAddBinding($ugroup),
            $this->getCurrentBinding($ugroup),
            $collection->getModals(),
            $this->getClones($ugroup)
        );
    }

    /**
     * Get the HTML output for ugroups bound to the current one
     */
    private function getClones(ProjectUGroup $ugroup)
    {
        $ugroups        = array();
        $nb_not_visible = 0;
        foreach ($this->ugroup_binding->getUGroupsByBindingSource($ugroup->getId()) as $id => $clone) {
            $project = $this->project_manager->getProject($clone['group_id']);
            if ($project->userIsAdmin()) {
                $ugroups[] = $this->getUgroupBindingPresenter($project, $id, $clone['cloneName']);
            } else {
                $nb_not_visible ++;
            }
        }

        return array(
            'ugroups'        => $ugroups,
            'has_ugroups'    => count($ugroups) > 0,
            'nb_not_visible' => $nb_not_visible
        );
    }

    private function getCurrentBinding(ProjectUGroup $ugroup)
    {
        $source = $ugroup->getSourceGroup();
        if (! $source) {
            return array();
        }

        $project = $source->getProject();
        if (! $project->userIsAdmin()) {
            return $this->getEmptyUgroupBindingPresenter();
        }

        return $this->getUgroupBindingPresenter($project, $source->getId(), $source->getName());
    }

    private function getUgroupBindingPresenter(Project $project, $id, $name)
    {
        return array(
            'project_url'  => '/projects/' . $project->getUnixName(),
            'project_name' => $project->getPublicName(),
            'ugroup_url'   => '/project/admin/editugroup.php?' . http_build_query(
                array(
                    'group_id'  => $project->getID(),
                    'ugroup_id' => $id,
                )
            ),
            'ugroup_name'  => $name,
        );
    }

    private function getEmptyUgroupBindingPresenter()
    {
        return array(
            'project_url'  => '',
            'project_name' => '',
            'ugroup_url'   => '',
            'ugroup_name'  => '',
        );
    }

    private function getAddBinding(ProjectUGroup $ugroup)
    {
        return array(
            'projects' => $this->getProjectsPresentersForBinding($ugroup),
        );
    }

    private function getProjectsPresentersForBinding(ProjectUGroup $ugroup)
    {
        $current_user       = $this->user_manager->getCurrentUser();
        $projects           = array();
        $current_project_id = $ugroup->getProjectId();
        $projects_of_user   = $current_user->getProjects(true);
        foreach ($projects_of_user as $project_as_row) {
            if ($current_project_id == $project_as_row['group_id']) {
                continue;
            }

            $project = $this->project_manager->getProject($project_as_row['group_id']);
            if (! $current_user->isAdmin($project->getID())) {
                continue;
            }

            $ugroup_list = $this->getUgroupPresenterList($project->getID());
            if (empty($ugroup_list)) {
                continue;
            }

            $projects[] = array(
                'id'                   => $project->getID(),
                'name'                 => $project->getPublicName(),
                'json_encoded_ugroups' => json_encode($ugroup_list)
            );
        }

        return $projects;
    }

    private function getUgroupPresenterList($project_id)
    {
        $ugroupList = array();
        $ugroups    = ugroup_db_get_existing_ugroups($project_id);
        while ($ugroup_row = db_fetch_array($ugroups)) {
            $user_group = new ProjectUGroup(array('ugroup_id' => $ugroup_row['ugroup_id']));
            if (! $user_group->isBound()) {
                $ugroupList[] = array('id' => $ugroup_row['ugroup_id'], 'name' => $ugroup_row['name']);
            }
        }

        return $ugroupList;
    }
}
