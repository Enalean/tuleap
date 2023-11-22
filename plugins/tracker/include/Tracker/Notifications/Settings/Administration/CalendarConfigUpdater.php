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

namespace Tuleap\Tracker\Notifications\Settings\Administration;

use HTTPRequest;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;
use Tuleap\Tracker\Notifications\Settings\UpdateCalendarConfig;
use Tuleap\Tracker\Semantic\Timeframe\BuildSemanticTimeframe;

final class CalendarConfigUpdater
{
    private const ENABLE_CALENDAR_EVENTS = 'enable-calendar-events';

    public function __construct(
        private readonly CheckEventShouldBeSentInNotification $current_config,
        private readonly UpdateCalendarConfig $update_config,
        private readonly BuildSemanticTimeframe $semantic_timeframe_builder,
    ) {
    }

    /**
     * @return Ok<bool>|Err<string>
     */
    public function updateConfigAccordingToRequest(\Tracker $tracker, HTTPRequest $request): Ok|Err
    {
        if (! $request->exist(self::ENABLE_CALENDAR_EVENTS)) {
            return Result::ok(false);
        }

        if (! $request->get(self::ENABLE_CALENDAR_EVENTS)) {
            return $this->deactivate($tracker);
        } else {
            return $this->activate($tracker);
        }
    }

    /**
     * @return Ok<bool>
     */
    private function deactivate(\Tracker $tracker): Ok
    {
        $events_are_sent = $this->current_config->shouldSendEventInNotification($tracker->getId());

        if ($events_are_sent) {
            $this->update_config->deactivateCalendarEvent($tracker->getId());

            return Result::ok(true);
        }

        return Result::ok(false);
    }

    /**
     * @return Ok<bool>|Err<string>
     */
    private function activate(\Tracker $tracker): Ok|Err
    {
        $events_are_already_sent = $this->current_config->shouldSendEventInNotification($tracker->getId());

        if ($events_are_already_sent) {
            return Result::ok(false);
        }

        if (\Tracker_Semantic_Title::load($tracker)->getField() === null) {
            return Result::err(dgettext('tuleap-tracker', 'Semantic title is required for calendar events'));
        }

        if (! $this->semantic_timeframe_builder->getSemantic($tracker)->isDefined()) {
            return Result::err(dgettext('tuleap-tracker', 'Semantic timeframe is required for calendar events'));
        }

        $this->update_config->activateCalendarEvent($tracker->getId());

        return Result::ok(true);
    }
}
