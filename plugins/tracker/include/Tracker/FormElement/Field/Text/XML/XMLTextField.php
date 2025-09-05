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

namespace Tuleap\Tracker\FormElement\Field\Text\XML;

use Tuleap\Tracker\FormElement\Field\XML\XMLField;

final class XMLTextField extends XMLField
{
    private int $rows = 10;
    private int $cols = 50;

    #[\Override]
    public static function getType(): string
    {
        return \Tracker_FormElementFactory::FIELD_TEXT_TYPE;
    }

    /**
     * @psalm-mutation-free
     */
    public function withRows(int $rows): self
    {
        $new       = clone $this;
        $new->rows = $rows;

        return $new;
    }

    /**
     * @psalm-mutation-free
     */
    public function withCols(int $cols): self
    {
        $new       = clone $this;
        $new->cols = $cols;

        return $new;
    }

    #[\Override]
    public function export(\SimpleXMLElement $form_elements): \SimpleXMLElement
    {
        $field = parent::export($form_elements);

        $properties = $field->addChild('properties');
        $properties->addAttribute('rows', (string) $this->rows);
        $properties->addAttribute('cols', (string) $this->cols);

        return $field;
    }
}
