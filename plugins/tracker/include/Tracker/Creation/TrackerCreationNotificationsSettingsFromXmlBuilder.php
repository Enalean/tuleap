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

namespace Tuleap\Tracker\Creation;

use Tracker_Semantic_Title;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\TimeframeImpliedFromAnotherTracker;
use Tuleap\XML\PHPCast;

final class TrackerCreationNotificationsSettingsFromXmlBuilder
{
    /**
     * @return Ok<TrackerCreationNotificationsSettings>|Err<string>
     */
    public function getCreationNotificationsSettings(
        ?\SimpleXMLElement $attributes,
        \Tracker $tracker,
    ): Ok|Err {
        $should_send_event_in_notification = isset($attributes['should_send_event_in_notification']) && $attributes['should_send_event_in_notification'] !== null
            ? PHPCast::toBoolean($attributes['should_send_event_in_notification'])
            : false;

        if ($should_send_event_in_notification) {
            $semantic_title_found     = false;
            $semantic_timeframe_found = false;
            foreach ($tracker->semantics as $semantic) {
                if ($semantic instanceof Tracker_Semantic_Title) {
                    $semantic_title_found = $semantic->getField() !== null;
                    continue;
                }

                if ($semantic instanceof SemanticTimeframe) {
                    if ($semantic->getTimeframeCalculator() instanceof TimeframeImpliedFromAnotherTracker) {
                        return Result::err(
                            dgettext('tuleap-tracker', 'Cannot activate calendar event for tracker with timeframe semantic inherited from another tracker')
                        );
                    }

                    $semantic_timeframe_found = $semantic->isDefined();
                    continue;
                }

                if ($semantic_title_found && $semantic_timeframe_found) {
                    break;
                }
            }

            if (! $semantic_title_found) {
                return Result::err(
                    dgettext('tuleap-tracker', 'Cannot activate calendar event for tracker without title semantic')
                );
            }

            if (! $semantic_timeframe_found) {
                return Result::err(
                    dgettext('tuleap-tracker', 'Cannot activate calendar event for tracker without timeframe semantic')
                );
            }
        }

        return Result::ok(new TrackerCreationNotificationsSettings($should_send_event_in_notification));
    }
}
