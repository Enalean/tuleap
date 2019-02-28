<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use UGroupDao;
use UGroupManager;
use ProjectUGroup;
use Project;
use UGroupBinding;
use UGroupUserDao;
use EventManager;
use Event;

class UgroupDuplicator
{

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var UGroupUserDao
     */
    private $ugroup_user_dao;

    /**
     * @var UGroupBinding
     */
    private $binding;

    /**
     * @var UGroupManager
     */
    private $manager;

    /**
     * @var UGroupDao
     */
    private $dao;

    public function __construct(
        UGroupDao $dao,
        UGroupManager $manager,
        UGroupBinding $binding,
        UGroupUserDao $ugroup_user_dao,
        EventManager $event_manager
    ) {
        $this->dao             = $dao;
        $this->manager         = $manager;
        $this->binding         = $binding;
        $this->ugroup_user_dao = $ugroup_user_dao;
        $this->event_manager   = $event_manager;
    }

    public function duplicateOnProjectCreation(Project $template, $new_project_id, array &$ugroup_mapping)
    {
        foreach ($this->manager->getStaticUGroups($template) as $ugroup) {
            if (! $ugroup->isStatic()) {
                continue;
            }

            $this->duplicate($ugroup, $new_project_id, $ugroup_mapping);
        }
    }

    private function duplicate(ProjectUGroup $ugroup, $new_project_id, array &$ugroup_mapping)
    {
        $ugroup_id     = $ugroup->getId();
        $new_ugroup_id = $this->dao->createUgroupFromSourceUgroup($ugroup_id, $new_project_id);

        if (! $new_ugroup_id) {
            return false;
        }

        $this->pluginDuplicatesUgroup($ugroup, $new_ugroup_id);
        $this->duplicateUgroupUsersAndBinding($ugroup, $new_ugroup_id, $new_project_id);

        $ugroup_mapping[$ugroup_id] = $new_ugroup_id;
    }

    private function pluginDuplicatesUgroup(ProjectUGroup $ugroup, $new_ugroup_id)
    {
        $params = array(
            'source_ugroup'  => $ugroup,
            'new_ugroup_id'  => $new_ugroup_id,
        );

        $this->event_manager->processEvent(
            Event::UGROUP_DUPLICATION,
            $params
        );
    }

    private function duplicateUgroupUsersAndBinding(ProjectUGroup $ugroup, $new_ugroup_id, $new_project_id)
    {

        $this->dao->createBinding($new_project_id, $ugroup->getId(), $new_ugroup_id);

        if ($ugroup->isBound()) {
            $this->binding->addBinding($new_ugroup_id, $ugroup->getSourceGroup()->getId());
        } else {
            $this->ugroup_user_dao->cloneUgroup($ugroup->getId(), $new_ugroup_id);
        }
    }
}
