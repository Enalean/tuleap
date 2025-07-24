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

use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\SearchPlannableFeatures;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final class SearchPlannableFeaturesStub implements SearchPlannableFeatures
{
    private function __construct(private array $rows)
    {
    }

    /**
     * @no-named-arguments
     */
    public static function withFeatureIds(int $feature_id, int ...$other_ids): self
    {
        return new self([$feature_id, ...$other_ids]);
    }

    public static function withoutFeatures(): self
    {
        return new self([]);
    }

    #[\Override]
    public function searchPlannableFeatures(ProgramIdentifier $program): array
    {
        return $this->rows;
    }
}
