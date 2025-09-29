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

namespace Tuleap\Tracker\FormElement\Field\Date\XML;

use Tuleap\Tracker\FormElement\Field\XML\XMLField;

final class XMLDateField extends XMLField
{
    private bool $display_datetime = false;

    #[\Override]
    public static function getType(): string
    {
        return \Tracker_FormElementFactory::FIELD_DATE_TYPE;
    }

    /**
     * @psalm-mutation-free
     * @return $this
     */
    public function withDateTime(): self
    {
        $new                   = clone $this;
        $new->display_datetime = true;
        return $new;
    }

    #[\Override]
    public function export(\SimpleXMLElement $form_elements): \SimpleXMLElement
    {
        $field = parent::export($form_elements);
        if ($this->display_datetime) {
            $properties = $field->addChild('properties');
            $properties->addAttribute('display_time', '1');
        }
        return $field;
    }
}
