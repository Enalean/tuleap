<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step;

/**
 * @psalm-immutable
 */
final readonly class Step
{
    public function __construct(
        private int $id,
        private string $description,
        private string $description_format,
        private ?string $expected_results,
        private string $expected_results_format,
        private int $rank,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDescriptionFormat(): string
    {
        return $this->description_format;
    }

    public function getExpectedResults(): ?string
    {
        return $this->expected_results;
    }

    public function getExpectedResultsFormat(): string
    {
        return $this->expected_results_format;
    }

    public function getRank(): int
    {
        return $this->rank;
    }

    #[\Override]
    public function __toString(): string
    {
        return json_encode(
            [
                $this->id,
                $this->description,
                $this->description_format,
                $this->expected_results,
                $this->expected_results_format,
                $this->rank,
            ],
            JSON_THROW_ON_ERROR
        );
    }
}
