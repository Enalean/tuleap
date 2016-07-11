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

class UgroupDuplicator
{

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
        UGroupUserDao $ugroup_user_dao
    ) {
        $this->dao             = $dao;
        $this->manager         = $manager;
        $this->binding         = $binding;
        $this->ugroup_user_dao = $ugroup_user_dao;
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
        $ugroup_id = $ugroup->getId();

        if ($ugroup->isBound()) {
            $new_ugroup_id = $this->dao->createUgroupFromSourceUgroup($ugroup_id, $new_project_id);
            $this->binding->addBinding($new_ugroup_id, $ugroup->getSourceGroup()->getId());
        } else {
            $new_ugroup_id = $this->dao->duplicate($ugroup_id, $new_project_id);
            $this->ugroup_user_dao->cloneUgroup($ugroup_id, $new_ugroup_id);
        }

        if ($new_ugroup_id) {
            $ugroup_mapping[$ugroup_id] = $new_ugroup_id;
        }
    }
}
