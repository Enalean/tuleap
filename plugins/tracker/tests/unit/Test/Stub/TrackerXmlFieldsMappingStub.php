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

use LogicException;
use TrackerXmlFieldsMapping;

final readonly class TrackerXmlFieldsMappingStub implements TrackerXmlFieldsMapping
{
    /**
     * @param array<string, string> $mapping
     */
    private function __construct(private array $mapping)
    {
    }

    /**
     * @param array<string, string> $mapping
     */
    public static function buildWithMapping(array $mapping): self
    {
        return new self($mapping);
    }

    #[\Override]
    public function getNewValueId(mixed $old_value_id): string
    {
        if (isset($this->mapping[$old_value_id])) {
            return $this->mapping[$old_value_id];
        }
        throw new LogicException('test not covered');
    }

    #[\Override]
    public function getNewOpenValueId(mixed $old_value_id): void
    {
    }
}
