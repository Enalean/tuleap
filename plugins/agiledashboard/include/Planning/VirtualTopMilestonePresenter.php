<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Planning;

use Tuleap\AgileDashboard\Milestone\Pane\Planning\PlanningV2Presenter;
use Tuleap\AgileDashboard\ServiceAdministration\CreateBacklogURI;

final class VirtualTopMilestonePresenter
{
    public readonly string $create_backlog_uri;

    public function __construct(
        public readonly ?PlanningV2Presenter $planning_presenter,
        public readonly bool $is_admin,
        public readonly string $backlog_title,
        CreateBacklogURI $create_backlog_uri,
    ) {
        $this->create_backlog_uri = (string) $create_backlog_uri;
    }
}