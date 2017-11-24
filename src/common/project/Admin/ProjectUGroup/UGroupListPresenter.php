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

namespace Tuleap\Project\Admin\ProjectUGroup;

use CSRFSynchronizerToken;
use Project;

class UGroupListPresenter
{

    public $ugroups;
    public $template_ugroups;
    public $project_id;
    public $csrf;

    public function __construct(Project $project, array $ugroups, array $template_ugroups, CSRFSynchronizerToken $csrf)
    {
        $this->ugroups          = $ugroups;
        $this->template_ugroups = $template_ugroups;
        $this->project_id       = $project->getID();
        $this->csrf             = $csrf;
    }
}
