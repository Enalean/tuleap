<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\REST\v1\TimetrackingManagement;

use Tuleap\Dashboard\Widget\Add\WidgetAdder;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Timetracking\Widget\TimetrackingManagementWidget;
use Widget;

final readonly class PostWidgetHandler
{
    public function __construct(private WidgetAdder $widget_adder)
    {
    }

    /**
     * @return Ok<TimetrackingManagamentPostWidgetRepresentation>|Err<Fault>
     */
    public function handle(\PFUser $current_user, int $dashboard_id, string $dashboard_type): Ok|Err
    {
        return $this->widget_adder->add(
            $current_user,
            (int) $current_user->getId(),
            $dashboard_type,
            $dashboard_id,
            TimetrackingManagementWidget::NAME,
            new \Codendi_Request([]),
        )->andThen(function (Widget $widget) {
            return Result::ok(new TimetrackingManagamentPostWidgetRepresentation($widget->content_id));
        });
    }
}
