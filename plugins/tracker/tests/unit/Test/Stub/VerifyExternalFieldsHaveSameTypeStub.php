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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\Action\VerifyExternalFieldsHaveSameType;

final class VerifyExternalFieldsHaveSameTypeStub implements VerifyExternalFieldsHaveSameType
{
    private function __construct(private readonly bool $have_same_type)
    {
    }

    public static function withSameType(): self
    {
        return new self(true);
    }

    public static function withoutSameType(): self
    {
        return new self(false);
    }

    public function haveBothFieldsSameType(\Tracker_FormElement_Field $source_field, \Tracker_FormElement_Field $destination_field): bool
    {
        return $this->have_same_type;
    }
}
