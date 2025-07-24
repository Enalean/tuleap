<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders\Fields;

use Tracker_FormElement_Field_Priority;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

final class PriorityFieldBuilder
{
    use FieldBuilderWithPermissions;

    private Tracker $tracker;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(19)->build();
    }

    public static function aPriorityField(int $id): self
    {
        return new self($id);
    }

    public function inTracker(Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_Priority
    {
        $field = new Tracker_FormElement_Field_Priority(
            $this->id,
            $this->tracker->getId(),
            15,
            'priority',
            'Priority',
            '',
            true,
            '',
            false,
            false,
            10,
            null
        );
        $field->setTracker($this->tracker);
        $this->setPermissions($field);

        return $field;
    }
}
