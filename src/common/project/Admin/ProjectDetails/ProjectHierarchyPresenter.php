<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

class ProjectHierarchyPresenter
{
    public $parent_project_info;
    public $delete_button;
    public $purified_project_children;
    public $parent_project_label;
    public $child_projects_label;
    public $add_parent_button;
    public $empty_children_label;
    public $project_name_label;
    public $project_hierarchy_title;
    public $project_hierarchy_desc;
    public $is_hierarchy_shown;
    public $delete_modal_title;
    public $delete_modal_desc;
    public $cancel_button;
    /**
     * @var bool
     */
    public $is_active = false;
    public $parent_status_class;
    public $parent_status_label;

    public function __construct(
        array $parent_project_info,
        $purified_project_children,
        $is_hierarchy_shown
    ) {
        $this->parent_project_info       = $parent_project_info;
        $this->purified_project_children = $purified_project_children;
        $this->is_hierarchy_shown        = $is_hierarchy_shown;

        if (isset($parent_project_info['is_active']) && $parent_project_info['is_active'] === false) {
            $this->is_active           = $parent_project_info['is_active'];
            $this->parent_status_class = $parent_project_info['status_class'];
            $this->parent_status_label = $parent_project_info['status_label'];
        }

        $this->project_name_label      = _('Project name');
        $this->empty_children_label    = _('No children projects');
        $this->delete_button           = _('Delete');
        $this->delete_modal_title      = _('Delete parent');
        $this->delete_modal_desc       = _('Wow, wait a minute. You are about to remove the parent. Please confirm your action.');
        $this->cancel_button           = _('Cancel');
        $this->parent_project_label    = _('Parent project');
        $this->child_projects_label    = _('Child projects');
        $this->add_parent_button       = _('Add parent project');
        $this->project_hierarchy_title = _('Project hierarchy');
        $this->project_hierarchy_desc  = _("This feature aims to provide a way to structure projects organizations from
            permissions, user groups and control point of view. As of today, it's only used by Git/Gerrit for umbrella
            projects (if relevant to your platform/project). However, setting a hierarchy between projects might have
            future impacts (e.g. members of the parent project could possibly have access to all sub-projects even
            without explicit permissions granted).");
    }
}
