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

namespace Tuleap\Tracker\Test\Stub;

use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatusField;
use Tuleap\Tracker\Tracker;

final class RetrieveSemanticStatusFieldStub implements RetrieveSemanticStatusField
{
    private int $call_count = 0;
    /**
     * @var array<int, ListField>
     */
    private array $fields = [];

    private function __construct()
    {
    }

    public static function build(): self
    {
        return new self();
    }

    public function withField(ListField $field): self
    {
        $this->fields[$field->getTrackerId()] = $field;
        return $this;
    }

    #[\Override]
    public function fromTracker(Tracker $tracker): ?ListField
    {
        $this->call_count++;
        return $this->fields[$tracker->getId()] ?? null;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
