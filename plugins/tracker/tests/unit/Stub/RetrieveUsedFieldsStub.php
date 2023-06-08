<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub;

use Tracker_FormElement_Field;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;

final class RetrieveUsedFieldsStub implements RetrieveUsedFields
{
    /**
     * @param \Tracker_FormElement_Field[] $fields
     */
    private function __construct(private readonly array $fields)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withFields(
        \Tracker_FormElement_Field $first_field,
        \Tracker_FormElement_Field ...$other_fields,
    ): self {
        return new self([$first_field, ...$other_fields]);
    }

    public static function withNoFields(): self
    {
        return new self([]);
    }

    public function getUsedFields(\Tracker $tracker): array
    {
        return $this->fields;
    }

    public function getUsedFormElementFieldById(int $id): ?Tracker_FormElement_Field
    {
        foreach ($this->fields as $field) {
            if ($field->getId() === $id) {
                return $field;
            }
        }

        return null;
    }

    public function getUsedFieldByName(int $tracker_id, string $field_name): ?Tracker_FormElement_Field
    {
        foreach ($this->fields as $field) {
            if ($field->getName() === $field_name) {
                return $field;
            }
        }

        return null;
    }
}
