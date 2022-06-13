<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Admin\ProjectCreationNavBarPresenter;

class TemplateListPresenter
{
    public $title;
    public $project_name_header;
    public $unix_group_name_header;
    public $services_button_label;
    public $templates;
    public $navbar;
    public string $project_status_label;

    public function __construct(
        ProjectCreationNavBarPresenter $navbar,
        $title,
        array $templates_presenters,
    ) {
        $this->navbar    = $navbar;
        $this->title     = $title;
        $this->templates = $templates_presenters;

        $this->project_name_header    = _('Project name');
        $this->project_status_label   = _('Project visibility');
        $this->unix_group_name_header = _('Unix name');
        $this->services_button_label  = _('Configure services');
    }
}
