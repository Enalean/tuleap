<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use Project;

class AdminChartsPresenter
{
    /**
     * @var int
     */
    public $project_id;

    /**
     * @var \CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var bool
     */
    public $is_burnup_count_mode_activated;

    public function __construct(
        Project $project,
        \CSRFSynchronizerToken $token,
        bool $is_burnup_count_mode_activated,
        public bool $is_using_kanban_service,
    ) {
        $this->project_id                     = $project->getID();
        $this->csrf_token                     = $token;
        $this->is_burnup_count_mode_activated = $is_burnup_count_mode_activated;
    }
}
