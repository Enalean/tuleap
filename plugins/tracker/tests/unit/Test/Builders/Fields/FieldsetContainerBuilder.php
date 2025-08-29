<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Tracker_FormElement;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Tracker;

final class FieldsetContainerBuilder
{
    /** @var Tracker_FormElement[]|null */
    private ?array $form_elements = null;
    private string $name          = 'Fieldset';
    private string $label         = 'label';
    private string $description   = '';
    private bool $required        = false;
    private Tracker $tracker;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->build();
    }

    public static function aFieldset(int $id): self
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

    public function withDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function required(): self
    {
        $this->required = true;
        return $this;
    }

    public function inTracker(Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function containsFormElements(Tracker_FormElement ...$elements): self
    {
        $this->form_elements = $elements;

        return $this;
    }

    public function build(): FieldsetContainer
    {
        $fieldset = new FieldsetContainer(
            $this->id,
            $this->tracker->getId(),
            0,
            $this->name,
            $this->label,
            $this->description,
            true,
            'P',
            $this->required,
            true,
            20,
            null
        );
        $fieldset->setTracker($this->tracker);

        $fieldset->formElements = $this->form_elements;

        return $fieldset;
    }
}
