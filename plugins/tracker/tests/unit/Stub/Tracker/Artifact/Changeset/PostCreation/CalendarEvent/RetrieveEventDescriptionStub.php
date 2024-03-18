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

use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\CalendarEventData;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\CalendarEvent\RetrieveEventDescription;

final class RetrieveEventDescriptionStub implements RetrieveEventDescription
{
    private function __construct(private readonly ?string $description)
    {
    }

    public static function shouldNotBeCalled(): self
    {
        return new self(null);
    }

    public static function withDescription(string $description): self
    {
        return new self($description);
    }

    public function retrieveEventDescription(
        CalendarEventData $calendar_event_data,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        LoggerInterface $logger,
        bool $should_check_permissions,
    ): Ok {
        if ($this->description === null) {
            throw new \Exception('Should not have been called');
        }

        return Result::ok($calendar_event_data->withDescription($this->description));
    }
}
