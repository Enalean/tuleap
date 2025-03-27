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
use Tracker_FormElement_Container_Column;

final class ColumnContainerBuilder
{
    /** @var Tracker_FormElement[] */
    private array $form_elements = [];

    private function __construct(private readonly int $id)
    {
    }

    public static function aColumn(int $id): self
    {
        return new self($id);
    }

    public function containsFormElements(Tracker_FormElement ...$elements): self
    {
        $this->form_elements = $elements;

        return $this;
    }

    public function build(): Tracker_FormElement_Container_Column
    {
        $column = new Tracker_FormElement_Container_Column(
            $this->id,
            51,
            15,
            'Column',
            'label',
            '',
            true,
            '',
            false,
            false,
            10,
            null
        );

        $column->formElements = $this->form_elements;

        return $column;
    }
}
