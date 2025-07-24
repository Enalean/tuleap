<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerFunctions\Stubs\Logs;

use Tuleap\TrackerFunctions\Logs\FunctionLogLineWithArtifact;
use Tuleap\TrackerFunctions\Logs\RetrieveLogsForTracker;

final class RetrieveLogsForTrackerStub implements RetrieveLogsForTracker
{
    /**
     * @param FunctionLogLineWithArtifact[] $logs
     */
    private function __construct(private readonly array $logs)
    {
    }

    public static function withoutLogs(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchLogsByTrackerId(int $tracker_id): array
    {
        return $this->logs;
    }
}
