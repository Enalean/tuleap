<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Admin;

class ProjectWidgetsConfigurationPresenter
{
    /**
     * @var ProjectCreationNavBarPresenter
     */
    public $navbar;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var ProjectWidgetsPresenter[]
     */
    public $project_widgets;

    public function __construct(
        ProjectCreationNavBarPresenter $menu_tabs,
        \CSRFSynchronizerToken $csrf_token,
        array $project_widgets
    ) {
        $this->navbar          = $menu_tabs;
        $this->csrf_token      = $csrf_token;
        $this->project_widgets = $project_widgets;
    }
}
