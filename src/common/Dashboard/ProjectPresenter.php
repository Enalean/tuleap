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

namespace Tuleap\Dashboard;

use ForgeConfig;
use PFUser;
use Project;
use ProjectManager;
use Tuleap\Project\ProjectAccessPresenter;

class ProjectPresenter
{
    public $name;
    public $parent_name = '';
    public $has_parent  = false;
    public $access;
    public $nb_members;
    public $trove_cats;
    public $use_trove_cat;

    public function __construct(
        Project $project,
        ProjectManager $project_manager,
        array $trove_cats
    ) {
        $this->name          = $project->getUnconvertedPublicName();
        $this->access        = new ProjectAccessPresenter($project->getAccess());
        $this->use_trove_cat = ForgeConfig::get('sys_use_trove');
        $this->trove_cats    = implode(', ', $trove_cats);

        if (empty($this->trove_cats) && ForgeConfig::get('sys_trove_cat_mandatory')) {
            $this->trove_cats = _('This project is not categorized yet.');
        }

        $parent_project = $project_manager->getParentProject($project->getID());
        if ($parent_project) {
            $this->has_parent  = true;
            $this->parent_name = $parent_project->getUnconvertedPublicName();
        }

        $nb_members       = count($project->getMembers());
        $this->nb_members = sprintf(
            ngettext('%d member', '%d members', $nb_members),
            $nb_members
        );
    }
}
