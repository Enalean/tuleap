<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Widget;

use Psr\Log\LoggerInterface;

final readonly class WidgetInheritanceHandler
{
    public function __construct(
        private SearchCrossTrackerWidget $widget_dao,
        private CloneWidget $widget_cloner,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(int $template_widget_id): int
    {
        if (! $this->widget_dao->searchWidgetExistence($template_widget_id)) {
            $this->logger->error(
                sprintf('Could not find widget #%d while duplicating Cross-Tracker Search widget', $template_widget_id)
            );
            return 0;
        }

        return $this->widget_cloner->cloneWidget($template_widget_id);
    }
}
