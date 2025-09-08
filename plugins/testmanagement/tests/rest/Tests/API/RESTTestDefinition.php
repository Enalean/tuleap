<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\Tests\API;

/**
 * @psalm-immutable
 */
final readonly class RESTTestDefinition
{
    /**
     * @param array{
     *     id: int,
     *     summary: string,
     *     category: string,
     *     description: string,
     *     decription_format: string,
     *     steps: array<array{
     *         id: int,
     *         description: string,
     *         decription_format: string,
     *         expected_results: string,
     *         expected_results_format: string,
     *         rank: int
     *     }>,
     *     all_requirements: array
     * } $json
     */
    public function __construct(public array $json)
    {
    }

    public function getAllRequirements(): array
    {
        return $this->json['all_requirements'];
    }

    public function getStepIds(): array
    {
        return array_map(
            static fn (array $step) => $step['id'],
            $this->json['steps'],
        );
    }
}
