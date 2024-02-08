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

use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class CalendarEventConfigDaoTest extends TestIntegrationTestCase
{
    private const BUG_TRACKER_ID   = 1;
    private const TASK_TRACKER_ID  = 2;
    private const STORY_TRACKER_ID = 3;
    private CalendarEventConfigDao $dao;

    protected function setUp(): void
    {
        $this->dao = new CalendarEventConfigDao();
    }

    public function testShouldSendEventInNotification(): void
    {
        $this->dao->deactivateCalendarEvent(self::BUG_TRACKER_ID);
        $this->dao->activateCalendarEvent(self::TASK_TRACKER_ID);

        self::assertFalse($this->dao->shouldSendEventInNotification(self::BUG_TRACKER_ID));
        self::assertTrue($this->dao->shouldSendEventInNotification(self::TASK_TRACKER_ID));
        self::assertFalse($this->dao->shouldSendEventInNotification(self::STORY_TRACKER_ID));
    }

    public function testShouldActivateOrDeactivateAnExistingEntry(): void
    {
        $this->dao->activateCalendarEvent(self::TASK_TRACKER_ID);
        $this->dao->deactivateCalendarEvent(self::TASK_TRACKER_ID);
        $this->dao->activateCalendarEvent(self::TASK_TRACKER_ID);

        self::assertTrue($this->dao->shouldSendEventInNotification(self::TASK_TRACKER_ID));
    }
}
