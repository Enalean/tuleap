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

use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\ServerHostname;

final class EventDescriptionRetriever implements RetrieveEventDescription
{
    /**
     * @return Ok<CalendarEventData>
     */
    public function retrieveEventDescription(
        CalendarEventData $calendar_event_data,
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        LoggerInterface $logger,
        bool $should_check_permissions,
    ): Ok {
        $default = ServerHostname::HTTPSUrl() . $changeset->getArtifact()->getUri();

        $description_field = \Tracker_Semantic_Description::load($changeset->getTracker())->getField();
        if (! $description_field) {
            $logger->debug('No semantic description for this tracker');
            return Result::ok($calendar_event_data->withDescription($default));
        }

        if ($should_check_permissions && ! $description_field->userCanRead($recipient)) {
            $logger->debug('User cannot read description');
            return Result::ok($calendar_event_data->withDescription($default));
        }

        $description_field_value = $changeset->getValue($description_field);
        if (! $description_field_value instanceof \Tracker_Artifact_ChangesetValue_Text) {
            $logger->debug('No value for description');
            return Result::ok($calendar_event_data->withDescription($default));
        }

        $description = trim($description_field_value->getValue());
        return Result::ok($calendar_event_data->withDescription($default . PHP_EOL . $description));
    }
}
