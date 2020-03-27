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

use Codendi_HTMLPurifier;
use Project;
use Tracker_Artifact_ChangesetValue_Text;

class StepPresenter
{
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $raw_description;
    /**
     * @var string
     */
    public $description_format;
    /**
     * @var string
     */
    public $purified_description;
    /**
     * @var int
     */
    public $rank;
    /**
     * @var string
     */
    public $raw_expected_results;
    /**
     * @var string
     */
    public $expected_results_format;
    /**
     * @var string
     */
    public $purified_expected_results;

    public function __construct(Step $step, Project $project)
    {
        $this->id                        = $step->getId();
        $this->rank                      = $step->getRank();
        $this->raw_description           = $step->getDescription();
        $this->description_format        = $step->getDescriptionFormat();
        $this->purified_description      = $this->getPurifiedText(
            $step->getDescription(),
            $step->getDescriptionFormat(),
            $project
        );
        $this->raw_expected_results      = $step->getExpectedResults() ?? '';
        $this->expected_results_format   = $step->getExpectedResultsFormat();
        $this->purified_expected_results = $this->getPurifiedText(
            $step->getExpectedResults() ?? '',
            $step->getExpectedResultsFormat(),
            $project
        );
    }

    private function getPurifiedText(string $text, string $format, Project $project): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        if ($format === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            return $purifier->purifyHTMLWithReferences($text, $project->getID());
        }

        return $purifier->purifyTextWithReferences($text, $project->getID());
    }
}
