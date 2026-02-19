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

use Tracker_FormElement_Field_List_BindDecorator;
use Tracker_FormElement_Field_List_OpenValue;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBindValue;
use Tuleap\Tracker\FormElement\Field\List\ListField;

final class ListStaticBindBuilder
{
    /**
     * @var ListFieldStaticBindValue[]|Tracker_FormElement_Field_List_OpenValue[]
     */
    private array $bind_values = [];
    /**
     * @var Tracker_FormElement_Field_List_BindDecorator[]
     */
    private array $decorators = [];

    private function __construct(private readonly ListField $field)
    {
    }

    public static function aStaticBind(ListField $field): self
    {
        return new self($field);
    }

    /**
     * @psalm-param array<int, string> $values_labels
     * @psalm-param array<int, bool> $hidden_values
     */
    public function withStaticValues(array $values_labels, array $hidden_values = []): self
    {
        foreach ($values_labels as $id => $label) {
            $bind_value = new ListFieldStaticBindValue(
                $id,
                $label,
                'A static bind value',
                $id,
                isset($hidden_values[$id]),
            );

            $this->bind_values[$id] = $bind_value;
        }

        return $this;
    }

    /**
     * @param list<ListFieldStaticBindValue|Tracker_FormElement_Field_List_OpenValue> $values
     */
    public function withBuildStaticValues(array $values): self
    {
        foreach ($values as $value) {
            $this->bind_values[$value->getId()] = $value;
        }

        return $this;
    }

    /**
     * @param Tracker_FormElement_Field_List_BindDecorator[] $decorators
     */
    public function withDecorators(array $decorators): self
    {
        foreach ($decorators as $decorator) {
            $this->decorators[$decorator->value_id] = $decorator;
        }

        return $this;
    }

    public function build(): ListFieldStaticBind
    {
        $bind = new class (
            $this->field,
            false,
            $this->bind_values,
            [],
            $this->decorators,
        ) extends ListFieldStaticBind {
            #[\Override]
            public function getQuerySelect(): string
            {
                return 'Static_Bind' . $this->field->getId();
            }
        };
        $this->field->setBind($bind);

        return $bind;
    }
}
