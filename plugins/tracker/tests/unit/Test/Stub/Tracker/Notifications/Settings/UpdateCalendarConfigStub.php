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

use Tuleap\Tracker\Notifications\Settings\UpdateCalendarConfig;

final class UpdateCalendarConfigStub implements UpdateCalendarConfig
{
    private bool $has_deactivate_been_called = false;
    private bool $has_activate_been_called   = false;

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function deactivateCalendarEvent(int $tracker_id): void
    {
        $this->has_deactivate_been_called = true;
    }

    public function activateCalendarEvent(int $tracker_id): void
    {
        $this->has_activate_been_called = true;
    }

    public function hasDeactivateBeenCalled(): bool
    {
        return $this->has_deactivate_been_called;
    }

    public function hasActivateBeenCalled(): bool
    {
        return $this->has_activate_been_called;
    }
}
