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

namespace Tuleap\Tracker\Test\Builders\Fields\List;

final class ListStaticBindBuilder
{
    /**
     * @var \Tracker_FormElement_Field_List_Bind_StaticValue[]
     */
    private array $bind_values = [];

    private function __construct(private readonly \Tracker_FormElement_Field_List $field)
    {
    }

    public static function aStaticBind(\Tracker_FormElement_Field_List $field): self
    {
        return new self($field);
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
        $bind = new \Tracker_FormElement_Field_List_Bind_Static(
            $this->field,
            false,
            $this->bind_values,
            [],
            []
        );
        $this->field->setBind($bind);

        return $bind;
    }
}
