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

final class RetrieveStatusFieldStub implements \Tuleap\Tracker\Semantic\Status\RetrieveStatusField
{
    /**
     * @param list<\Tracker_FormElement_Field_List> $return_values
     */
    private function __construct(private bool $return_null, private bool $always_return, private array $return_values)
    {
    }

    public static function withField(\Tracker_FormElement_Field_List $field): self
    {
        return new self(false, true, [$field]);
    }

    /**
     * @no-named-arguments
     */
    public static function withSuccessiveFields(
        \Tracker_FormElement_Field_List $field,
        \Tracker_FormElement_Field_List ...$other_fields,
    ): self {
        return new self(false, false, [$field, ...$other_fields]);
    }

    public static function withNoField(): self
    {
        return new self(true, false, []);
    }

    public function getStatusField(\Tuleap\Tracker\Tracker $tracker): ?\Tracker_FormElement_Field_List
    {
        if ($this->return_null) {
            return null;
        }
        if ($this->always_return) {
            return $this->return_values[0];
        }
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        throw new \LogicException('No status field configured');
    }
}
