<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement\XML;

use Tuleap\Tracker\FormElement\Container\XML\XMLContainer;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindStatic\XML\XMLBindStaticValue;
use Tuleap\Tracker\FormElement\Field\ListFields\XML\XMLSelectBoxField;

/**
 * @psalm-immutable
 */
final class XMLFormElementFlattenedCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<string, XMLFormElement>
     */
    private $form_elements;

    /**
     * @param array<string, XMLFormElement> $form_elements
     */
    public function __construct(array $form_elements)
    {
        $this->form_elements = $form_elements;
    }

    public static function buildFromFormElements(XMLFormElement ...$form_elements): self
    {
        $indexed_form_elements = [];
        foreach ($form_elements as $form_element) {
            self::add($indexed_form_elements, $form_element);
        }
        return new self($indexed_form_elements);
    }

    private static function add(array &$form_elements, XMLFormElement $form_element): void
    {
        if (isset($form_elements[$form_element->name])) {
            throw new \LogicException(self::class . ' cannot store the same FormElement by name twice: ' . $form_element->name);
        }

        $form_elements[$form_element->name] = $form_element;

        if (! $form_element instanceof XMLContainer) {
            return;
        }

        foreach ($form_element->form_elements as $sub_element) {
            self::add($form_elements, $sub_element);
        }
    }

    /**
     * @throw \LogicException
     */
    public function getByName(string $name): XMLFormElement
    {
        if (! isset($this->form_elements[$name])) {
            throw new \LogicException(sprintf('Field `%s` does not exist in form_elements collection', $name));
        }
        return $this->form_elements[$name];
    }

    public function getBindValueByLabel(string $field_name, string $label): XMLBindStaticValue
    {
        $field = $this->getByName($field_name);
        if (! $field instanceof XMLSelectBoxField) {
            throw new \LogicException(sprintf('Given field_name `%s` does not have static bind values', $field_name));
        }

        foreach ($field->bind_values as $bind_value) {
            if ($bind_value instanceof XMLBindStaticValue && $bind_value->label === $label) {
                return $bind_value;
            }
        }

        throw new \LogicException(sprintf('%s field does not have an %s value with label %s', $field_name, XMLBindStaticValue::class, $label));
    }

    #[\Override]
    public function count(): int
    {
        return count($this->form_elements);
    }

    #[\Override]
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->form_elements);
    }
}
