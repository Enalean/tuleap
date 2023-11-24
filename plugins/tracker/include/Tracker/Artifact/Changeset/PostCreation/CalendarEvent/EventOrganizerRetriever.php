<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
use Tuleap\Config\ConfigurationVariables;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class EventOrganizerRetriever implements RetrieveEventOrganizer
{
    /**
     * @return Ok<CalendarEventData>
     */
    public function retrieveEventOrganizer(CalendarEventData $calendar_event_data, \Tracker_Artifact_Changeset $changeset, \PFUser $recipient, LoggerInterface $logger, bool $should_check_permissions,): Ok
    {
        $parse_result = mailparse_rfc822_parse_addresses(\ForgeConfig::get(ConfigurationVariables::NOREPLY));
        if (empty($parse_result)) {
            $logger->debug("EventOrganizerRetriever::retrieveEventOrganizer -> has not found noreply address");
            return Result::ok($calendar_event_data);
        }

        $noreply_address = $parse_result[0]['address'];
        $project_name    = $changeset->getTracker()->getProject()->getPublicName();
        $organizer_name  = \ForgeConfig::get(ConfigurationVariables::NAME) . ' - ' . $project_name;

        return Result::ok($calendar_event_data->withOrganizer(new EventOrganizer($organizer_name, $noreply_address)));
    }
}
