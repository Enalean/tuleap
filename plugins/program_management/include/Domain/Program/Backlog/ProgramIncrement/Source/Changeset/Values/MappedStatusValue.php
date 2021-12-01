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

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\NoDuckTypedMatchingValueException;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;

/**
 * I hold the mapped Tracker List field Bind values for the Status Semantic of a Mirrored Timebox tracker.
 * My Bind values' labels match those from StatusValue, but the Bind value ids are different.
 * They belong to the Mirrored Timebox tracker, not the source Timebox tracker.
 * @see StatusValue
 * @psalm-immutable
 */
final class MappedStatusValue
{
    /**
     * @var BindValueIdentifier[]
     */
    private array $values;

    private function __construct(BindValueIdentifier ...$values)
    {
        $this->values = $values;
    }

    /**
     * @throws NoDuckTypedMatchingValueException
     */
    public static function fromStatusValueAndListField(
        MapStatusByValue $status_mapper,
        StatusValue $source_value,
        StatusFieldReference $target_field,
    ): self {
        $identifiers = $status_mapper->mapStatusValueByDuckTyping($source_value, $target_field);
        return new self(...$identifiers);
    }

    /**
     * @return int[]
     */
    public function getValues(): array
    {
        return array_map(
            static fn(BindValueIdentifier $identifier): int => $identifier->getId(),
            $this->values
        );
    }
}
