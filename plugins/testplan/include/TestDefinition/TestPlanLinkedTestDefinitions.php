<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\TestPlan\TestDefinition;

final class TestPlanLinkedTestDefinitions
{
    /**
     * @var TestPlanTestDefinitionWithTestStatus[]
     * @psalm-readonly
     */
    private $requested_linked_test_definitions;
    /**
     * @var int
     * @psalm-readonly
     */
    private $total_number_of_linked_test_definitions;

    /**
     * @param TestPlanTestDefinitionWithTestStatus[] $requested_linked_test_definitions
     */
    private function __construct(array $requested_linked_test_definitions, int $total_number_of_linked_test_definitions)
    {
        if (count($requested_linked_test_definitions) > $total_number_of_linked_test_definitions) {
            throw new \LogicException("The total number of linked artifacts cannot be smaller than the requested part");
        }

        $this->requested_linked_test_definitions       = $requested_linked_test_definitions;
        $this->total_number_of_linked_test_definitions = $total_number_of_linked_test_definitions;
    }

    /**
     * @param TestPlanTestDefinitionWithTestStatus[] $requested_linked_test_definitions
     */
    public static function subset(array $requested_linked_test_definitions, int $total_number_of_linked_test_definitions): self
    {
        return new self($requested_linked_test_definitions, $total_number_of_linked_test_definitions);
    }

    public static function empty(): self
    {
        return new self([], 0);
    }

    /**
     * @return TestPlanTestDefinitionWithTestStatus[]
     */
    public function getRequestedLinkedTestDefinitions(): array
    {
        return $this->requested_linked_test_definitions;
    }

    public function getTotalNumberOfLinkedTestDefinitions(): int
    {
        return $this->total_number_of_linked_test_definitions;
    }
}
