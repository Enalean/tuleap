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

namespace Tuleap\Project\Admin\ProjectDetails;

use Codendi_HTMLPurifier;

class ProjectHierarchyPresenter
{
    public $parent_project_info;
    public $delete_button;
    public $purified_project_children;
    public $child_projects_label;
    public $add_parent_button;
    public $update_parent_button;
    public $empty_children_label;
    public $project_name_label;
    public $project_hierarchy_title;
    public $purified_project_hierarchy_desc;
    public $is_hierarchy_shown;

    public function __construct(
        array $parent_project_info,
        $purified_project_children,
        $is_hierarchy_shown
    ) {
        $this->parent_project_info       = $parent_project_info;
        $this->purified_project_children = $purified_project_children;
        $this->is_hierarchy_shown        = $is_hierarchy_shown;

        $this->project_name_label      = _('Project name');
        $this->empty_children_label    = _('No children projects');
        $this->delete_button           = _('Delete');
        $this->child_projects_label    = _('Child projects');
        $this->add_parent_button       = _('Add parent project');
        $this->update_parent_button    = _('Update parent project');
        $this->project_hierarchy_title = _('Project hierarchy');

        $this->purified_project_hierarchy_desc = Codendi_HTMLPurifier::instance()->purify(
            _(
                "<p>This feature aims to provide a way to structure projects organizations from permissions, user groups and control point of view.</p>
                <p>As of today, it's only used by Git/ Gerrit for umbrella projects (if relevant to your platform/ project).</p>
                <p><strong>However, setting a hierarchy between projects might have future impacts (e.g. members of the parent project could possibly have access to all sub-projects even without explicit permissions granted).</strong></p>"
            ),
            CODENDI_PURIFIER_LIGHT
        );
    }
}
