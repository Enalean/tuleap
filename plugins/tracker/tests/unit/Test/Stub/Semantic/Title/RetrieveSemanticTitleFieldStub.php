<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Stub\Semantic\Title;

use Tracker_FormElement_Field_Text;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;
use Tuleap\Tracker\Tracker;

final class RetrieveSemanticTitleFieldStub implements RetrieveSemanticTitleField
{
    private int $call_count;
    /** @var array<int, Tracker_FormElement_Field_Text> */
    private array $tracker_titles = [];

    private function __construct()
    {
        $this->call_count = 0;
    }

    public static function build(): self
    {
        return new self();
    }

    public function withTitleField(Tracker $tracker, Tracker_FormElement_Field_Text $title_field): self
    {
        $this->tracker_titles[$tracker->getId()] = $title_field;
        return $this;
    }

    public function fromTracker(Tracker $tracker): ?Tracker_FormElement_Field_Text
    {
        $this->call_count++;
        if (isset($this->tracker_titles[$tracker->getId()])) {
            return $this->tracker_titles[$tracker->getId()];
        }
        return null;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
