<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

use Tracker_FormElement_Field_Date;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class DateFieldBuilder
{
    use FieldBuilderWithPermissions;
    use FieldBuilderWithSpecificProperties;

    private string $name  = 'date';
    private string $label = 'label';
    /** @var list<\PFUser> */
    private array $user_with_read_permissions = [];
    /** @var array<int, bool> */
    private array $read_permissions = [];
    private \Tuleap\Tracker\Tracker $tracker;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();
        $this->withSpecificProperty('display_time', ['value' => false]);
    }

    public static function aDateField(int $id): self
    {
        return new self($id);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function withTime(): self
    {
        return $this->withSpecificProperty('display_time', ['value' => true]);
    }

    public function inTracker(\Tuleap\Tracker\Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function build(): Tracker_FormElement_Field_Date
    {
        $date_element = new Tracker_FormElement_Field_Date(
            $this->id,
            $this->tracker->getId(),
            15,
            $this->name,
            $this->label,
            '',
            true,
            '',
            false,
            false,
            10,
            null
        );

        $date_element->setTracker($this->tracker);

        $this->setSpecificProperties($date_element);
        $this->setPermissions($date_element);

        return $date_element;
    }
}
