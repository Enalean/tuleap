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
    public $id;
    public $raw_description;
    public $description_format;
    public $purified_description;
    public $rank;

    public function __construct(Step $step, Project $project)
    {

        $this->id                   = $step->getId();
        $this->rank                 = $step->getRank();
        $this->raw_description      = $step->getDescription();
        $this->description_format   = $step->getDescriptionFormat();
        $this->purified_description = $this->getPurifiedDescription($step, $project);
    }

    private function getPurifiedDescription(Step $step, Project $project)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        if ($step->getDescriptionFormat() === Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT) {
            return $purifier->purifyHTMLWithReferences($step->getDescription(), $project->getID());
        }

        return $purifier->purifyTextWithReferences($step->getDescription(), $project->getID());
    }
}
