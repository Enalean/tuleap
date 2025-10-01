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

namespace Tuleap\CrossTracker\Tests\Stub\Widget;

use Override;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\RetrieveCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\Option\Option;

final readonly class RetrieveCrossTrackerWidgetStub implements RetrieveCrossTrackerWidget
{
    private function __construct(public ProjectCrossTrackerWidget|UserCrossTrackerWidget|null $widget)
    {
    }

    public static function withWidget(ProjectCrossTrackerWidget|UserCrossTrackerWidget $widget): self
    {
        return new self($widget);
    }

    /**
     * @return Option<ProjectCrossTrackerWidget>|Option<UserCrossTrackerWidget>
     */
    #[Override]
    public function retrieveWidgetById(int $widget_id): Option
    {
        if (! $this->widget) {
            return Option::nothing(ProjectCrossTrackerWidget::class);
        }
        if ($this->widget instanceof ProjectCrossTrackerWidget) {
            return Option::fromValue($this->widget);
        }

        return Option::fromValue($this->widget);
    }
}
