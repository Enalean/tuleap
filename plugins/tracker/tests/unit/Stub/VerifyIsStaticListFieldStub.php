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

namespace Tuleap\Tracker\Test\Stub;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Action\VerifyIsStaticListField;

final class VerifyIsStaticListFieldStub implements VerifyIsStaticListField
{
    private function __construct(private readonly bool $is_single_static_list_field)
    {
    }

    public static function withSingleStaticListField(): self
    {
        return new self(true);
    }

    public static function withoutSingleStaticListField(): self
    {
        return new self(false);
    }

    public function isStaticListField(Tracker_FormElement_Field $field): bool
    {
        return $this->is_single_static_list_field;
    }
}
