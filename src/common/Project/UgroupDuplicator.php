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

use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use UGroupDao;
use UGroupManager;
use ProjectUGroup;
use Project;
use UGroupBinding;
use EventManager;
use Event;

class UgroupDuplicator
{

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var MemberAdder
     */
    private $member_adder;

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
        MemberAdder $member_adder,
        EventManager $event_manager
    ) {
        $this->dao           = $dao;
        $this->manager       = $manager;
        $this->binding       = $binding;
        $this->member_adder  = $member_adder;
        $this->event_manager = $event_manager;
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

    private function duplicate(ProjectUGroup $source_ugroup, $new_project_id, array &$ugroup_mapping)
    {
        $ugroup_id     = $source_ugroup->getId();
        $new_ugroup_id = $this->dao->createUgroupFromSourceUgroup($ugroup_id, $new_project_id);

        if (! $new_ugroup_id) {
            return false;
        }

        $new_ugroup = $this->manager->getById($new_ugroup_id);

        $this->pluginDuplicatesUgroup($source_ugroup, $new_ugroup);
        $this->duplicateUgroupUsersAndBinding($source_ugroup, $new_ugroup, $new_project_id);

        $ugroup_mapping[$ugroup_id] = $new_ugroup_id;
    }

    private function pluginDuplicatesUgroup(ProjectUGroup $source_ugroup, ProjectUGroup $new_ugroup)
    {
        $params = array(
            'source_ugroup'  => $source_ugroup,
            'new_ugroup_id'  => $new_ugroup->getId(),
        );

        $this->event_manager->processEvent(
            Event::UGROUP_DUPLICATION,
            $params
        );
    }

    private function duplicateUgroupUsersAndBinding(ProjectUGroup $source_ugroup, ProjectUGroup $new_ugroup, $new_project_id)
    {
        $this->dao->createBinding($new_project_id, $source_ugroup->getId(), $new_ugroup->getId());

        if ($source_ugroup->isBound()) {
            $this->binding->addBinding($new_ugroup->getId(), $source_ugroup->getSourceGroup()->getId());
        } else {
            foreach ($source_ugroup->getMembers() as $member) {
                try {
                    $this->member_adder->addMember($member, $new_ugroup);
                } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted $exception) {
                    // do nothing
                }
            }
        }
    }
}
