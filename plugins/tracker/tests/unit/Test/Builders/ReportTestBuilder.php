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

namespace Tuleap\Tracker\Test\Builders;

use Tuleap\Test\Builders\UserTestBuilder;

final class ReportTestBuilder
{
    private \Tracker $tracker;
    private int|string $id = 101;

    private function __construct(private readonly bool $is_public)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->build();
    }

    public static function aPublicReport(): self
    {
        return new self(true);
    }

    public static function aPrivateReport(): self
    {
        return new self(false);
    }

    public function inTracker(\Tracker $tracker): self
    {
        $this->tracker = $tracker;

        return $this;
    }

    public function withId(int|string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function build(): \Tracker_Report
    {
        $tracker_report = new \Tracker_Report(
            $this->id,
            'My bugs',
            'Description',
            0,
            0,
            $this->is_public ? null : (int) UserTestBuilder::buildWithDefaults()->getId(),
            false,
            $this->tracker->getId(),
            true,
            false,
            '',
            null,
            0
        );

        $tracker_report->setTracker($this->tracker);

        return $tracker_report;
    }
}
