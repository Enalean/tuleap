<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

use Tuleap\ProgramManagement\Domain\Program\Plan\CreatePlanConfiguration;
use Tuleap\ProgramManagement\Domain\Program\Plan\PlanConfigurationChange;

final class CreatePlanConfigurationStub implements CreatePlanConfiguration
{
    private bool $will_throw_exception_on_plan_change_creation = false;

    /**
     * @var PlanConfigurationChange[]
     */
    private array $method_create_calls_history = [];

    public static function build(): self
    {
        return new self();
    }

    public function willThrowExceptionOnPlanChangeCreation(): void
    {
        $this->will_throw_exception_on_plan_change_creation = true;
    }

    /**
     * @throws \Exception
     */
    #[\Override]
    public function create(PlanConfigurationChange $plan_change): void
    {
        if ($this->will_throw_exception_on_plan_change_creation) {
            throw new \Exception('PlanChange creation has failed for some reasons ¯\_(ツ)_/¯');
        }

        $this->method_create_calls_history[] = $plan_change;
    }

    /**
     * @throws \Exception
     */
    public function getCreateMethodCallsArgs(int $call_number): PlanConfigurationChange
    {
        if (! isset($this->method_create_calls_history[$call_number])) {
            throw new \Exception(sprintf('Method CreatePlanStub::create has not been called %d time', $call_number + 1));
        }

        return $this->method_create_calls_history[$call_number];
    }
}
