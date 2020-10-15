<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use Tuleap\REST\JsonCast;
use Tuleap\TestManagement\Step\Step;

/**
 * @psalm-immutable
 */
class StepDefinitionRepresentation
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $description;
    /**
     * @var string
     */
    public $description_format;
    /**
     * @var string
     */
    public $expected_results;
    /**
     * @var string
     */
    public $expected_results_format;
    /**
     * @var int
     */
    public $rank;

    private function __construct(
        int $id,
        string $description,
        string $description_format,
        string $expected_results,
        string $expected_results_format,
        int $rank
    ) {
        $this->id                      = $id;
        $this->description             = $description;
        $this->description_format      = $description_format;
        $this->expected_results        = $expected_results;
        $this->expected_results_format = $expected_results_format;
        $this->rank                    = $rank;
    }

    public static function build(Step $step, \Codendi_HTMLPurifier $purifier, \Tuleap\Tracker\Artifact\Artifact $artifact): self
    {
        $project_id = $artifact->getTracker()->getGroupId();
        return new self(
            JsonCast::toInt($step->getId()),
            $purifier->purifyHTMLWithReferences($step->getDescription(), $project_id),
            $step->getDescriptionFormat(),
            $purifier->purifyHTMLWithReferences($step->getExpectedResults() ?? '', $project_id),
            $step->getExpectedResultsFormat(),
            JsonCast::toInt($step->getRank())
        );
    }
}
