<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

use Tuleap\Tracker\FormElement\Field\Integer\IntegerField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

final class IntegerFieldBuilder
{
    use FieldBuilderWithSpecificProperties;
    use FieldBuilderWithPermissions;

    private string $name  = 'initial_effort';
    private string $label = 'Initial effort';
    private bool $use_it  = true;
    private Tracker $tracker;
    private bool $is_required = false;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();
    }

    public static function anIntField(int $id): self
    {
        return new self($id);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function unused(): self
    {
        $this->use_it = false;

        return $this;
    }

    public function inTracker(Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function withLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function thatIsRequired(): self
    {
        $this->is_required = true;
        return $this;
    }

    public function build(): IntegerField
    {
        $field = new IntegerField(
            $this->id,
            $this->tracker->getId(),
            15,
            $this->name,
            $this->label,
            '',
            $this->use_it,
            'P',
            $this->is_required,
            '',
            10,
            null
        );
        $field->setTracker($this->tracker);
        $this->setPermissions($field);
        $this->setSpecificProperties($field);
        return $field;
    }
}
