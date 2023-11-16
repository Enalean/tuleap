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

namespace Tuleap\Tracker\Notifications\Settings;

use Tuleap\DB\DataAccessObject;

final class CalendarEventConfigDao extends DataAccessObject implements CheckEventShouldBeSentInNotification
{
    public function duplicate(int $source_tracker_id, int $target_tracker_id): void
    {
        $sql = <<<EOSQL
        INSERT INTO plugin_tracker_calendar_event_config(tracker_id, should_send_event_in_notification)
        SELECT ?, should_send_event_in_notification
        FROM plugin_tracker_calendar_event_config
        WHERE tracker_id = ?
        EOSQL;
        $this->getDB()->run($sql, $target_tracker_id, $source_tracker_id);
    }

    public function shouldSendEventInNotification(int $tracker_id): bool
    {
        return $this->getDB()
            ->cell(
                <<<EOSQL
                SELECT should_send_event_in_notification
                FROM plugin_tracker_calendar_event_config
                WHERE tracker_id = ?
                EOSQL,
                $tracker_id
            ) === 1;
    }

    public function activateCalendarEvent(int $tracker_id): void
    {
        $this->getDB()
            ->run(
                <<<EOSQL
                INSERT INTO plugin_tracker_calendar_event_config(tracker_id, should_send_event_in_notification)
                SELECT ?, 1
                EOSQL,
                $tracker_id
            );
    }

    public function deactivateCalendarEvent(int $tracker_id): void
    {
        $this->getDB()
            ->run(
                <<<EOSQL
                INSERT INTO plugin_tracker_calendar_event_config(tracker_id, should_send_event_in_notification)
                SELECT ?, 0
                EOSQL,
                $tracker_id
            );
    }
}
