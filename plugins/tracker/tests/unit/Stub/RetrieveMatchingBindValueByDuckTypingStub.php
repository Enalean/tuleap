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

use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingBindValueByDuckTyping;

final class RetrieveMatchingBindValueByDuckTypingStub implements RetrieveMatchingBindValueByDuckTyping
{
    private function __construct(private readonly ?\Tracker_FormElement_Field_List_BindValue $bind_value)
    {
    }

    public static function withMatchingBindValue(\Tracker_FormElement_Field_List_BindValue $bind_value): self
    {
        return new self($bind_value);
    }

    public static function withoutMatchingBindValue(): self
    {
        return new self(null);
    }

    public function getMatchingBindValueByDuckTyping(
        \Tracker_FormElement_Field_List_BindValue $source_value,
        \Tracker_FormElement_Field_List $destination_field,
    ): ?\Tracker_FormElement_Field_List_BindValue {
        return $this->bind_value;
    }
}
