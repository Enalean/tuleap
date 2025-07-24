<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Tests\Stub;

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchOpenFeatures;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class SearchOpenFeaturesStub implements SearchOpenFeatures
{
    /**
     * @param list<array{artifact_id: int, program_id: int, title: string}> $open_features
     */
    private function __construct(private array $open_features, public array $program_identifiers)
    {
    }

    /**
     * @param list<array{artifact_id: int, program_id: int, title: string}> $open_features
     */
    public static function withRows(array $open_features): self
    {
        return new self($open_features, []);
    }

    #[\Override]
    public function searchOpenFeatures(int $offset, int $limit, ProgramIdentifier ...$program_identifiers): array
    {
        $this->program_identifiers = $program_identifiers;
        return $this->open_features;
    }

    public function getProgramIdentifiers(): array
    {
        return $this->program_identifiers;
    }
}
