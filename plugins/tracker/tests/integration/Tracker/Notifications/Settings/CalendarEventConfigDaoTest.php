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

namespace Notifications\Settings;

use Tuleap\DB\DBFactory;
use Tuleap\Tracker\Notifications\Settings\CalendarEventConfigDao;
use Tuleap\Test\PHPUnit\TestCase;

final class CalendarEventConfigDaoTest extends TestCase
{
    private const BUG_TRACKER_ID   = 1;
    private const TASK_TRACKER_ID  = 2;
    private const STORY_TRACKER_ID = 3;

    protected function setUp(): void
    {
        $this->dao = new CalendarEventConfigDao();
    }

    public function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->query('TRUNCATE TABLE plugin_tracker_calendar_event_config');
    }

    public function testShouldSendEventInNotification(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        $db->run(
            <<<EOS
            INSERT INTO plugin_tracker_calendar_event_config (tracker_id, should_send_event_in_notification)
            VALUES (?, 0),
                   (?, 1)
            EOS,
            self::BUG_TRACKER_ID,
            self::TASK_TRACKER_ID,
        );

        self::assertFalse($this->dao->shouldSendEventInNotification(self::BUG_TRACKER_ID));
        self::assertTrue($this->dao->shouldSendEventInNotification(self::TASK_TRACKER_ID));
        self::assertFalse($this->dao->shouldSendEventInNotification(self::STORY_TRACKER_ID));
    }
}
