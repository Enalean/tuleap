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
     * @var BindValueIdentifier[]
     */
    private array $values;

    private function __construct(BindValueIdentifier ...$values)
    {
        $this->values = $values;
    }

    public static function withValues(int ...$bind_value_ids): self
    {
        $identifiers = array_map(
            static fn(int $bind_value_id): BindValueIdentifier => BindValueIdentifierStub::withId($bind_value_id),
            $bind_value_ids
        );
        return new self(...$identifiers);
    }

    public function mapStatusValueByDuckTyping(StatusValue $source_value, StatusFieldReference $target_field): array
    {
        return $this->values;
    }
}
