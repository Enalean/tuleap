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

namespace Tuleap\Tracker\FormElement\Container\XML;

use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\FormElement\XML\XMLFormElement;
use Tuleap\Tracker\XML\IDGenerator;
use Tuleap\Tracker\XML\XMLTracker;

abstract class XMLContainer extends XMLFormElement
{
    /**
     * @var XMLFormElement[]
     * @readonly
     */
    public array $form_elements = [];

    final public function __construct(string|IDGenerator $id, string $name)
    {
        parent::__construct($id, static::getType(), $name);
    }

    abstract public static function getType(): string;

    public static function fromTrackerAndName(XMLTracker $tracker, string $name): static
    {
        return new static(FieldNameFormatter::getFormattedName($tracker->item_name . '_' . $name), $name);
    }

    #[\Override]
    public function export(\SimpleXMLElement $form_elements): \SimpleXMLElement
    {
        $node = parent::export($form_elements);
        $node->addChild('formElements');
        foreach ($this->form_elements as $form_element) {
            $form_element->export($node->formElements);
        }
        return $node;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function withFormElements(XMLFormElement ...$form_elements): self
    {
        $new                = clone $this;
        $new->form_elements = array_merge($new->form_elements, $form_elements);
        return $new;
    }

    /**
     * @psalm-mutation-free
     * @return static
     */
    public function appendFormElements(string $name, XMLFormElement $form_element): self
    {
        if ($this->name === $name) {
            return $this->withFormElements($form_element);
        }
        $new                = clone $this;
        $new->form_elements = [];
        foreach ($this->form_elements as $parent) {
            if ($parent instanceof XMLContainer) {
                $new->form_elements[] = $parent->appendFormElements($name, $form_element);
            } else {
                $new->form_elements[] = $parent;
            }
        }
        return $new;
    }

    #[\Override]
    public function exportPermissions(\SimpleXMLElement $form_elements): void
    {
    }
}
