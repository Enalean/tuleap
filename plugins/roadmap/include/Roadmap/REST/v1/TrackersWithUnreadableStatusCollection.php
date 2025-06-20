<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Roadmap\REST\v1;

use Psr\Log\LoggerInterface;

final class TrackersWithUnreadableStatusCollection
{
    /**
     * @var \Tuleap\Tracker\Tracker[]
     */
    private array $trackers = [];
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function add(\Tuleap\Tracker\Tracker $tracker): void
    {
        $this->trackers[$tracker->getId()] = $tracker;
    }

    public function informLoggerIfWeHaveTrackersWithUnreadableStatus(): void
    {
        if (empty($this->trackers)) {
            return;
        }

        if (count($this->trackers) === 1) {
            $message = sprintf(
                '[Roadmap widget] User cannot read status of tracker #%s. Hence, its artifacts won\'t be displayed.',
                array_values($this->trackers)[0]->getId(),
            );
        } else {
            $message = sprintf(
                '[Roadmap widget] User cannot read status of trackers %s. Hence, their artifacts won\'t be displayed.',
                implode(', ', array_map(
                    static fn(\Tuleap\Tracker\Tracker $tracker): string => '#' . $tracker->getId(),
                    $this->trackers,
                ))
            );
        }

        $this->logger->info($message);
    }
}
