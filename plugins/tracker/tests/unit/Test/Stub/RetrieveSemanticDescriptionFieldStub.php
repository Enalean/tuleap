<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;
use Tuleap\Tracker\Tracker;

final class RetrieveSemanticDescriptionFieldStub implements RetrieveSemanticDescriptionField
{
    private int $call_count;

    private function __construct(private readonly ?\Tracker_FormElement_Field_Text $field_text)
    {
        $this->call_count = 0;
    }

    public static function withNoField(): self
    {
        return new self(null);
    }

    public static function withTextField(\Tracker_FormElement_Field_Text $field_text): self
    {
        return new self($field_text);
    }

    public function fromTracker(Tracker $tracker): ?\Tracker_FormElement_Field_Text
    {
        $this->call_count++;
        return $this->field_text;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
