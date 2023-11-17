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

namespace Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\CalendarEvent;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\BuildCalendarEventData;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\CalendarEventData;

final class BuildCalendarEventDataStub implements BuildCalendarEventData
{
    private function __construct(private readonly Ok|Err|null $result)
    {
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public static function withCalendarEventData(CalendarEventData $event_data): self
    {
        return new self(Result::ok($event_data));
    }

    public static function withError(string $message): self
    {
        return new self(Result::err($message));
    }

    /**
     * @return Ok<CalendarEventData>|Err<non-falsy-string>
     */
    public function getCalendarEventData(
        string $summary,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        \Psr\Log\LoggerInterface $logger,
        bool $should_check_permissions,
    ): Ok|Err {
        if ($this->result === null) {
            throw new \Exception('Should not have been called');
        }

        return $this->result;
    }
}
