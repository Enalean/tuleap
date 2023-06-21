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

use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingUserValue;

final class RetrieveMatchingUserValueStub implements RetrieveMatchingUserValue
{
    private function __construct(private readonly bool $does_user_match)
    {
    }

    public static function withMatchingUser(): self
    {
        return new self(true);
    }

    public static function withoutMatchingUser(): self
    {
        return new self(false);
    }

    public function isSourceUserValueMatchingATargetUserValue(Tracker_FormElement_Field_List $target_contributor_field, SimpleXMLElement $value): bool
    {
        return $this->does_user_match;
    }
}
