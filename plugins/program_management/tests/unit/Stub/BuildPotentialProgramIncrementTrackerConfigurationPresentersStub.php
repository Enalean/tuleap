<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Stub;

use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramIncrementTrackerConfiguration\BuildPotentialProgramIncrementTrackerConfigurationPresenters;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramTrackerConfigurationPresenter;

final class BuildPotentialProgramIncrementTrackerConfigurationPresentersStub implements BuildPotentialProgramIncrementTrackerConfigurationPresenters
{
    private array $presenters;

    private function __construct(array $presenters)
    {
        $this->presenters = $presenters;
    }

    /**
     * @return ProgramTrackerConfigurationPresenter[]
     */
    public function buildPotentialProgramIncrementTrackerPresenters(int $program_id): array
    {
        return $this->presenters;
    }

    public static function buildWithValidProgramTrackers(ProgramTrackerConfigurationPresenter ...$presenters): self
    {
        return new self($presenters);
    }
}
