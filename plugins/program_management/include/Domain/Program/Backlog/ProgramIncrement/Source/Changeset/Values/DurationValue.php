<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values;

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\DurationFieldReference;

/**
 * I hold the value of the Duration timeframe field of the source Timebox.
 * I am the reference value that must be copied to Mirrored Timeboxes.
 * @psalm-immutable
 */
final class DurationValue
{
    private function __construct(private int $value)
    {
    }

    public static function fromDurationReference(
        RetrieveDurationValue $duration_retriever,
        DurationFieldReference $duration,
    ): self {
        return new self($duration_retriever->getDurationValue($duration));
    }

    /**
     * @return int Number of days
     */
    public function getValue(): int
    {
        return $this->value;
    }
}
