<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Builders\Fields;

use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class StringFieldBuilder
{
    use FieldBuilderWithPermissions;

    private string $label     = 'Title';
    private string $name      = 'title';
    private bool $is_required = false;
    private bool $use_it      = true;
    private \Tuleap\Tracker\Tracker $tracker;
    /** @var array<string, mixed> */
    private array $specific_properties   = [];
    private ?StringField $original_field = null;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();
    }

    public static function aStringField(int $id): self
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

    public function inTracker(\Tuleap\Tracker\Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function thatIsRequired(): self
    {
        $this->is_required = true;
        return $this;
    }

    public function withSpecificProperty(string $key, mixed $value): self
    {
        $this->specific_properties[$key] = $value;
        return $this;
    }

    public function unused(): self
    {
        $this->use_it = false;

        return $this;
    }

    public function withOriginalField(StringField $field): self
    {
        $this->original_field = $field;
        return $this;
    }

    public function build(): StringField
    {
        $field = new StringField(
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
            $this->original_field,
        );
        $field->setTracker($this->tracker);
        $this->setPermissions($field);
        if ($this->specific_properties !== []) {
            $field->setCacheSpecificProperties($this->specific_properties);
        }

        return $field;
    }
}
