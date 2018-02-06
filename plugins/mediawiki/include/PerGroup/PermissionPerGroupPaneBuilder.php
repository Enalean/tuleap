<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Mediawiki\PerGroup;

use MediawikiManager;
use Project;
use ProjectUGroup;
use UGroupManager;

class PermissionPerGroupPaneBuilder
{
    /**
     * @var MediawikiManager
     */
    private $mediawiki_manager;
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(
        MediawikiManager $mediawiki_manager,
        UGroupManager $ugroup_manager
    ) {
        $this->mediawiki_manager = $mediawiki_manager;
        $this->ugroup_manager    = $ugroup_manager;
    }

    public function buildPresenter(Project $project)
    {
        $readers = $this->mediawiki_manager->getReadAccessControl($project);
        $writers = $this->mediawiki_manager->getWriteAccessControl($project);

        $permission = array();
        if ($readers) {
            $formatted_group = array();
            foreach ($readers as $reader) {
                $formatted_group[] = $this->formatGroup($project, $reader);
            }
            $permission[] = array('name' => dgettext('tuleap-mediawiki', 'Readers'), 'groups' => $formatted_group);
        }

        if ($writers) {
            $formatted_group = array();
            foreach ($writers as $writer) {
                $formatted_group[] = $this->formatGroup($project, $writer);
            }
            $permission[] = array('name' => dgettext('tuleap-mediawiki', 'Writers'), 'groups' => $formatted_group);
        }

        return new PermissionPerGroupPresenter($permission);
    }

    private function formatGroup(Project $project, $group)
    {
        $user_group = $this->ugroup_manager->getUGroup($project, $group);

        $formatted_group = array(
            'is_project_admin' => $this->isProjectAdmin($user_group),
            'is_static'        => $user_group->isStatic(),
            'is_custom'        => ! $this->isProjectAdmin($user_group) && ! $user_group->isStatic(),
            'name'             => $user_group->getTranslatedName()
        );

        return $formatted_group;
    }

    /**
     * @param $user_group
     *
     * @return bool
     */
    private function isProjectAdmin(ProjectUGroup $user_group)
    {
        return (int) $user_group->getId() === ProjectUGroup::PROJECT_ADMIN;
    }
}
