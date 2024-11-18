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

use Tracker_FormElement_Field_List;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveMatchingValueByDuckTyping;

final class RetrieveMatchingValueByDuckTypingStub implements RetrieveMatchingValueByDuckTyping
{
    private function __construct(private readonly array $values)
    {
    }

    /**
     * @psalm-param array{source_value_id: int, destination_value_id: int} $values
     */
    public static function withMatchingValues(array $values): self
    {
        return new self($values);
    }

    public static function withoutAnyMatchingValue(): self
    {
        return new self([]);
    }

    public function getMatchingValueByDuckTyping(
        Tracker_FormElement_Field_List $source_field,
        Tracker_FormElement_Field_List $destination_field,
        int $source_value_id,
    ): ?int {
        return $this->values[$source_value_id] ?? null;
    }
}
