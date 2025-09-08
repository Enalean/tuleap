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
 * along with Tuleap. If not, <see http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\TestManagement\REST\v1\DefinitionRepresentations\StepDefinitionRepresentations;

/**
 * @psalm-immutable
 */
final class StepDefinitionRepresentation implements \JsonSerializable
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string The description of the step
     */
    public $description;
    /**
     * @var string The format of the description.
     *             Here it can be 'text' or 'html'
     */
    public $description_format;
    /**
     * @var string|null The description text which is not interpreted
     */
    public $commonmark_description;
    /**
     * @var string The result expected of the step
     */
    public $expected_results;
    /**
     * @var string The format of the result expected for the step.
     *             Here it can be 'text' or 'html'
     */
    public $expected_results_format;
    /**
     * @var string|null The expected results text which is not interpreted
     */
    public $commonmark_expected_results;
    /**
     * @var int The rank of the step in the test case
     */
    public $rank;

    public function __construct(
        int $id,
        string $description,
        string $description_format,
        ?string $commonmark_description,
        string $expected_results,
        string $expected_results_format,
        ?string $commonmark_expected_results,
        int $rank,
    ) {
        $this->id                          = $id;
        $this->description                 = $description;
        $this->description_format          = $description_format;
        $this->commonmark_description      = $commonmark_description;
        $this->expected_results            = $expected_results;
        $this->expected_results_format     = $expected_results_format;
        $this->commonmark_expected_results = $commonmark_expected_results;
        $this->rank                        = $rank;
    }

    #[\Override]
    public function jsonSerialize(): array
    {
        $json_representation = [
            'id'                 => $this->id,
            'description'        => $this->description,
            'description_format' => $this->description_format,
        ];
        if ($this->commonmark_description) {
            $json_representation['commonmark_description'] = $this->commonmark_description;
        }
        $json_representation['expected_results']        = $this->expected_results;
        $json_representation['expected_results_format'] = $this->expected_results_format;
        if ($this->commonmark_expected_results) {
            $json_representation['commonmark_expected_results'] = $this->commonmark_expected_results;
        }
        $json_representation['rank'] = $this->rank;
        return $json_representation;
    }
}
