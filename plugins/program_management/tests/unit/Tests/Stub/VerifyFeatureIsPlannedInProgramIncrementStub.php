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

use Tuleap\ProgramManagement\Domain\Program\Backlog\ProgramIncrement\VerifyFeatureIsPlannedInProgramIncrement;

final class VerifyFeatureIsPlannedInProgramIncrementStub implements VerifyFeatureIsPlannedInProgramIncrement
{
    private bool $is_planned;

    private function __construct(bool $is_planned)
    {
        $this->is_planned = $is_planned;
    }

    public static function buildUnPlannedFeature(): self
    {
        return new self(false);
    }

    public static function buildPlannedFeature(): self
    {
        return new self(true);
    }

    #[\Override]
    public function isFeaturePlannedInProgramIncrement(int $program_increment_id, int $feature_id): bool
    {
        return $this->is_planned;
    }
}
