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

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\AsynchronousCreation\MapStatusByValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\BindValueIdentifier;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Changeset\Values\StatusValue;
use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\Source\Fields\StatusFieldReference;

final class MapStatusByValueStub implements MapStatusByValue
{
    /**
     * @param array<BindValueIdentifier[]> $return_values
     */
    private function __construct(private array $return_values)
    {
    }

    public static function withSuccessiveBindValueIds(int ...$bind_value_ids): self
    {
        return new self(
            array_map(
                static fn(int $bind_value_id) => [BindValueIdentifierStub::withId($bind_value_id)],
                $bind_value_ids
            )
        );
    }

    /**
     * @param BindValueIdentifier[] $bind_value_identifiers
     */
    public static function withMultipleValuesOnce(array $bind_value_identifiers): self
    {
        return new self([$bind_value_identifiers]);
    }

    #[\Override]
    public function mapStatusValueByDuckTyping(StatusValue $source_value, StatusFieldReference $target_field): array
    {
        if (count($this->return_values) > 0) {
            return array_shift($this->return_values);
        }
        throw new \LogicException('No bind value identifiers configured');
    }
}
