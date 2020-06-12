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
 *
 */

namespace Tuleap\Trove;

use Tuleap\Layout\PaginationPresenter;

class SoftwareMapPresenter
{
    public $title;
    public $projects;
    public $browse_by;
    public $title_date;
    public $title_name;
    public $title_desc;
    public $title_cat;
    public $current_category;
    public $subcategories;
    public $root_categories;
    public $has_results;
    public $project_list;
    public $empty_state_project_list;
    public $pagination;
    public $projects_in_category;
    public $not_categorized;
    public $has_parent;
    public $parent_id;

    public function __construct($current_category_name, $parent_id, $sub_categories, $root_categories, $projects, PaginationPresenter $pagination)
    {
        $this->title                    = $GLOBALS['Language']->getOverridableText('softwaremap_trove_list', 'map');
        $this->projects                 = $GLOBALS['Language']->getText('softwaremap_trove_list', 'projs');
        $this->browse_by                = $GLOBALS['Language']->getText('softwaremap_trove_list', 'browse_by');
        $this->title_date               = $GLOBALS['Language']->getText('softwaremap_trove_list', 'register_date');
        $this->title_name               = _("Project name");
        $this->title_desc               = _("Short description");
        $this->title_cat                = _("Categories");
        $this->empty_state_project_list = _("There are no projects in this category");
        $this->projects_in_category     = _("Projects in category");
        $this->not_categorized          = _("Not categorized");
        $this->go_up                    = _("Go up");

        $this->current_category = $current_category_name;
        $this->parent_id        = $parent_id;
        $this->subcategories    = $sub_categories;
        $this->root_categories  = $root_categories;
        $this->project_list     = $projects;
        $this->pagination       = $pagination;

        $this->has_parent       = $parent_id !== null;
        $this->has_results      = count($this->project_list) > 0;
    }
}
