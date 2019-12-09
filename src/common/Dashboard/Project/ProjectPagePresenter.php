<?php
/**
 * Copyright (c) Enalean, 2017-present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\Dashboard\Project;

use CSRFSynchronizerToken;
use Tuleap\Dashboard\PagePresenter;

class ProjectPagePresenter extends PagePresenter
{
    /**
     * @var ProjectPresenter
     */
    public $project_presenter;
    /**
     * @var ProjectDashboardPresenter[]
     */
    public $dashboards;
    public $has_dashboard;
    /**
     * @var bool
     */
    public $should_display_project_created_modal;

    public function __construct(
        CSRFSynchronizerToken $csrf,
        $url,
        ProjectPresenter $project_presenter,
        array $dashboards,
        $can_update_dashboards,
        bool $should_display_project_created_modal
    ) {
        parent::__construct($csrf, $url);

        $this->project_presenter                    = $project_presenter;
        $this->dashboards                           = $dashboards;
        $this->has_dashboard                        = count($dashboards) > 0;
        $this->can_update_dashboards                = $can_update_dashboards;
        $this->should_display_project_created_modal = $should_display_project_created_modal;
    }
}
