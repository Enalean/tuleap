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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

final class SwitchMediawikiServiceStub implements SwitchMediawikiService
{
    private bool $is_switched = false;

    private function __construct()
    {
    }

    public static function buildSelf(): self
    {
        return new self();
    }

    #[\Override]
    public function switchToStandalone(\Project $project): void
    {
        $this->is_switched = true;
    }

    public function isSwitchedToStandalone(): bool
    {
        return $this->is_switched;
    }
}
