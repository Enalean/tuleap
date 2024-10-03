<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Plan\Inheritance;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\ProgramManagement\Domain\Program\Plan\NewPlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\SaveNewPlanConfiguration;

final readonly class PlanInheritanceHandler
{
    public function __construct(
        private RetrievePlanConfiguration $retrieve_plan,
        private PlanConfigurationMapper $mapper,
        private SaveNewPlanConfiguration $save_new_plan,
    ) {
    }

    /**
     * @return Ok<void> | Err<Fault>
     */
    public function handle(ProgramInheritanceMapping $mapping): Ok|Err
    {
        $configuration = $this->retrieve_plan->retrievePlan($mapping->source_program);
        return $this->mapper->mapFromTemplateProgramToNewProgram($mapping, $configuration)
            ->map(function (NewPlanConfiguration $new_plan_configuration) {
                $this->save_new_plan->save($new_plan_configuration);
            });
    }
}
