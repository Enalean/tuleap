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

namespace Tuleap\ProgramManagement\Tests\Stub\Program\Plan;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;

final readonly class RetrievePlanConfigurationStub implements \Tuleap\ProgramManagement\Domain\Program\Plan\RetrievePlanConfiguration
{
    /** @param list<PlanConfiguration> $configurations */
    private function __construct(
        private array $configurations,
    ) {
    }

    /** @no-named-arguments */
    public static function withPlanConfigurations(
        PlanConfiguration $plan_configuration,
        PlanConfiguration ...$other_configurations,
    ): self {
        return new self([$plan_configuration, ...$other_configurations]);
    }

    #[\Override]
    public function retrievePlan(ProgramIdentifier $program_identifier): Option
    {
        foreach ($this->configurations as $configuration) {
            if ($configuration->program_identifier->getId() === $program_identifier->getId()) {
                return Option::fromValue($configuration);
            }
        }
        return Option::nothing(PlanConfiguration::class);
    }
}
