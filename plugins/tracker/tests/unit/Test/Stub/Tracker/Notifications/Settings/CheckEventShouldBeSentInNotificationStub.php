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

namespace Tuleap\Tracker\Test\Stub\Tracker\Notifications\Settings;

use Tuleap\Tracker\Notifications\Settings\CheckEventShouldBeSentInNotification;

final class CheckEventShouldBeSentInNotificationStub implements CheckEventShouldBeSentInNotification
{
    private function __construct(private readonly bool $should_send_event_in_notification)
    {
    }

    public static function withEventInNotification(): self
    {
        return new self(true);
    }

    public static function withoutEventInNotification(): self
    {
        return new self(false);
    }

    public function shouldSendEventInNotification(int $tracker_id): bool
    {
        return $this->should_send_event_in_notification;
    }
}
