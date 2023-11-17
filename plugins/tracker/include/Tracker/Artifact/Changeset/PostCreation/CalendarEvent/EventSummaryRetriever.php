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

use Tracker_Semantic_Title;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;

final class EventSummaryRetriever implements RetrieveEventSummary
{
    /**
     * @return Ok<non-falsy-string>|Err<non-empty-string>
     */
    public function getEventSummary(
        \Tracker_Artifact_Changeset $changeset,
        \PFUser $recipient,
        bool $should_check_permissions,
    ): Ok|Err {
        $title_field = Tracker_Semantic_Title::load($changeset->getTracker())->getField();
        if (! $title_field) {
            return Result::err('The tracker does not have title semantic, we cannot build calendar event');
        }

        if ($should_check_permissions && ! $title_field->userCanRead($recipient)) {
            return Result::err(
                sprintf(
                    'The user #%s (%s) cannot read the title, we cannot build calendar event',
                    $recipient->getId(),
                    $recipient->getEmail(),
                )
            );
        }

        $title_field_value = $changeset->getValue($title_field);
        if (! $title_field_value instanceof \Tracker_Artifact_ChangesetValue_Text) {
            return Result::err('Title has no value, we cannot build calendar event');
        }

        $title = trim($title_field_value->getContentAsText());
        if (! $title) {
            return Result::err('Title is empty, we cannot build calendar event');
        }

        return Result::ok($title);
    }
}
