<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement;

/**
 * @psalm-immutable
 */
final class TestmanagementConfigTracker
{
    /**
     * @var string
     */
    private $tracker_name;
    /**
     * @var string
     */
    private $tracker_shortname;
    /**
     * @var int
     */
    private $tracker_id;

    public function __construct(string $tracker_name, string $tracker_shortname, int $tracker_id)
    {
        $this->tracker_name      = $tracker_name;
        $this->tracker_shortname = $tracker_shortname;
        $this->tracker_id        = $tracker_id;
    }

    public function getTrackerName(): string
    {
        return $this->tracker_name;
    }

    public function getTrackerShortname(): string
    {
        return $this->tracker_shortname;
    }

    public function getTrackerId(): int
    {
        return $this->tracker_id;
    }
}
