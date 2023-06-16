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

namespace Tuleap\Tracker\Test\Builders;


final class TrackerFormElementListStaticBindBuilder
{
    /**
     * @var \Tracker_FormElement_Field_List_Bind_StaticValue[]
     */
    private array $bind_values;
    private int $field_id           = 123;
    private string $name            = "A field";
    private bool $is_field_multiple = false;

    private function __construct()
    {
    }

    public static function aBind(): self
    {
        return new self();
    }

    public function withFieldId(int $field_id): self
    {
        $this->field_id = $field_id;
        return $this;
    }

    public function withFieldName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function withMultipleField(): self
    {
        $this->is_field_multiple = true;
        return $this;
    }

    /**
     * @psalm-param array{id: number, label: string} $values_labels
     */
    public function withStaticValues(array $values_labels): self
    {
        foreach ($values_labels as $id => $label) {
            $bind_value = new \Tracker_FormElement_Field_List_Bind_StaticValue(
                $id,
                $label,
                "A static bind value",
                $id,
                false,
            );

            $this->bind_values[$id] = $bind_value;
        }

        return $this;
    }

    public function build(): \Tracker_FormElement_Field_List_Bind_Static
    {
        $field = TrackerFormElementListFieldBuilder::aListField($this->field_id)->withName($this->name)->withMultipleField($this->is_field_multiple)->build();
        $bind  = new \Tracker_FormElement_Field_List_Bind_Static(
            $field,
            false,
            $this->bind_values,
            [],
            []
        );
        $field->setBind($bind);

        return $bind;
    }
}
