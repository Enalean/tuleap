<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
use ProjectTruncatedEmailsPresenter;
use ProjectVisibilityPresenter;

class ProjectGlobalVisibilityPresenter
{
    /**
     * @var ProjectVisibilityPresenter
     */
    public $project_visibility_presenter;

    /**
     * @var ProjectTruncatedEmailsPresenter
     */
    public $project_truncated_presenter;

    public $label_submit;

    public function __construct(
        Project $project,
        ProjectVisibilityPresenter $project_visibility_presenter,
        ProjectTruncatedEmailsPresenter $project_truncated_presenter
    ) {
        $this->project_visibility_presenter = $project_visibility_presenter;
        $this->project_truncated_presenter  = $project_truncated_presenter;
        $this->group_id                     = $project->getGroupId();
        $this->label_submit                 = _('Update');
    }
}
