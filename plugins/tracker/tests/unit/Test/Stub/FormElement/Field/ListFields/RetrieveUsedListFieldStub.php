<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\FormElement\Field\ListFields;

use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveUsedListField;

final class RetrieveUsedListFieldStub implements RetrieveUsedListField
{
    /**
     * @param array<int, \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_OpenList> $fields
     */
    private function __construct(private array $fields)
    {
    }

    public static function withField(
        \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_OpenList $field,
    ): self {
        return new self([$field]);
    }

    public static function withFields(
        \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_OpenList $field,
        \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_OpenList ...$other_fields,
    ): self {
        return new self([$field, ...$other_fields]);
    }

    public static function withNoField(): self
    {
        return new self([]);
    }

    #[\Override]
    public function getUsedListFieldById(
        \Tuleap\Tracker\Tracker $tracker,
        int $field_id,
    ): \Tracker_FormElement_Field_Selectbox|\Tracker_FormElement_Field_OpenList|null {
        foreach ($this->fields as $field) {
            if ($field_id === $field->getId()) {
                return $field;
            }
        }
        return null;
    }
}
