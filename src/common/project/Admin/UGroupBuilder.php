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

namespace Tuleap\Project\Admin;

use Project;
use Tuleap\User\UserGroup\NameTranslator;
use UGroupManager;

class UGroupBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var NameTranslator
     */
    private $name_translator;

    public function __construct(UGroupManager $ugroup_manager, NameTranslator $name_translator)
    {
        $this->ugroup_manager  = $ugroup_manager;
        $this->name_translator = $name_translator;
    }

    public function getUGroupsThatCanBeUsedAsTemplate(Project $project)
    {
        $ugroups = array();

        $ugroups[] = array(
            'id'       => 'cx_empty',
            'name'     => _('Empty group'),
            'selected' => 'selected="selected"'
        );

        $ugroups[] = array(
            'id'       => 'cx_members',
            'name'     => $this->name_translator->getUserGroupDisplayName(NameTranslator::PROJECT_MEMBERS),
            'selected' => ''
        );

        $ugroups[] = array(
            'id'       => 'cx_admins',
            'name'     => $this->name_translator->getUserGroupDisplayName(NameTranslator::PROJECT_ADMINS),
            'selected' => ''
        );

        $ugroup_list = $this->ugroup_manager->getExistingUgroups($project->getID());
        foreach ($ugroup_list as $ugroup) {
            $ugroups[] = array(
                'id'       => $ugroup['ugroup_id'],
                'name'     => $this->name_translator->getUserGroupDisplayName($ugroup['name']),
                'selected' => ''
            );
        }

        return $ugroups;
    }
}
