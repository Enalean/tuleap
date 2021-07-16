<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain;

/**
 * I am a reference to a Tracker. Contrary to ProgramTracker, I contain only a tracker ID and its name (label).
 * I should not contain a full tracker object, see ProgramTracker for this usage.
 * @psalm-immutable
 * @see ProgramTracker
 */
final class TrackerReference
{
    public int $id;
    public string $label;

    private function __construct(int $id, string $label)
    {
        $this->id    = $id;
        $this->label = $label;
    }

    public static function fromTracker(\Tracker $tracker): self
    {
        return new self($tracker->getId(), $tracker->getName());
    }
}
