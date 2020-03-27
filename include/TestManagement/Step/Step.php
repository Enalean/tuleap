<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step;

class Step
{
    /** @var int */
    private $id;
    /** @var string */
    private $description;
    /** @var string */
    private $description_format;
    /** @var string|null */
    private $expected_results;
    /** @var string */
    private $expected_results_format;
    /** @var int */
    private $rank;

    public function __construct(
        int $id,
        string $description,
        string $description_format,
        ?string $expected_results,
        string $expected_results_format,
        int $rank
    ) {
        $this->id                      = $id;
        $this->description             = $description;
        $this->description_format      = $description_format;
        $this->rank                    = $rank;
        $this->expected_results        = $expected_results;
        $this->expected_results_format = $expected_results_format;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getDescriptionFormat()
    {
        return $this->description_format;
    }

    /**
     * @return string|null
     */
    public function getExpectedResults()
    {
        return $this->expected_results;
    }

    /**
     * @return string
     */
    public function getExpectedResultsFormat()
    {
        return $this->expected_results_format;
    }

    /**
     * @return int
     */
    public function getRank()
    {
        return $this->rank;
    }

    public function __toString(): string
    {
        return (string) json_encode(
            [
                $this->id,
                $this->description,
                $this->description_format,
                $this->expected_results,
                $this->expected_results_format,
                $this->rank
            ],
            JSON_THROW_ON_ERROR
        );
    }
}
