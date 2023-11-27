<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent;


final class CalendarEventData
{
    /**
     * @param non-empty-string $summary
     */
    public function __construct(
        public readonly string $summary,
        public readonly string $description,
        public readonly int $start,
        public readonly int $end,
        public readonly bool $is_time_displayed,
        public readonly ?EventOrganizer $organizer,
    ) {
    }

    /**
     * @param non-empty-string $summary
     */
    public static function fromSummary(string $summary): self
    {
        return new self($summary, '', 0, 0, false, null);
    }

    /**
     * @param non-empty-string $summary
     */
    public function withSummary(string $summary): self
    {
        return new self(
            $summary,
            $this->description,
            $this->start,
            $this->end,
            $this->is_time_displayed,
            $this->organizer,
        );
    }

    public function withDescription(string $description): self
    {
        return new self(
            $this->summary,
            $description,
            $this->start,
            $this->end,
            $this->is_time_displayed,
            $this->organizer,
        );
    }

    public function withDates(int $start, int $end): self
    {
        return new self(
            $this->summary,
            $this->description,
            $start,
            $end,
            $this->is_time_displayed,
            $this->organizer,
        );
    }

    public function withOrganizer(EventOrganizer $organizer): self
    {
        return new self(
            $this->summary,
            $this->description,
            $this->start,
            $this->end,
            $this->is_time_displayed,
            $organizer,
        );
    }

    public function withTimeDisplayed(bool $is_time_displayed): self
    {
        return new self(
            $this->summary,
            $this->description,
            $this->start,
            $this->end,
            $is_time_displayed,
            $this->organizer,
        );
    }
}
