<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StartDateFieldReference;

/**
 * I hold the value of the start date field from the Timeframe semantic of the source Timebox.
 * I am the reference value that must be copied to Mirrored Timeboxes.
 * @psalm-immutable
 */
final class StartDateValue
{
    private function __construct(private int $date_value)
    {
    }

    /**
     * @throws ChangesetValueNotFoundException
     */
    public static function fromStartDateReference(
        RetrieveStartDateValue $start_date_retriever,
        StartDateFieldReference $start_date,
    ): self {
        return new self($start_date_retriever->getStartDateValue($start_date));
    }

    /**
     * @return int UNIX Timestamp
     */
    public function getValue(): int
    {
        return $this->date_value;
    }
}
