<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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
use Tuleap\Project\ProjectAccessPresenter;

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

    public $can_configure_visibility;

    /**
     * @var ProjectAccessPresenter
     */
    public $project_access_presenter;

    public $section_title;

    public function __construct(
        Project $project,
        ProjectVisibilityPresenter $project_visibility_presenter,
        ProjectTruncatedEmailsPresenter $project_truncated_presenter,
        ProjectAccessPresenter $project_access_presenter,
        $can_configure_visibility,
    ) {
        $this->project_visibility_presenter = $project_visibility_presenter;
        $this->project_truncated_presenter  = $project_truncated_presenter;
        $this->group_id                     = $project->getGroupId();
        $this->label_submit                 = _('Update');
        $this->section_title                = _('Access');
        $this->can_configure_visibility     = $can_configure_visibility;
        $this->project_access_presenter     = $project_access_presenter;
    }
}
