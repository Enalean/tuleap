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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\ProgramIncrementFieldsData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\SubmissionDate;
use Tuleap\ScaledAgile\TrackerData;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;

class ArtifactCreatorAdapter
{
    /**
     * @var TrackerArtifactCreator
     */
    private $artifact_creator;

    public function __construct(TrackerArtifactCreator $artifact_creator)
    {
        $this->artifact_creator = $artifact_creator;
    }

    /**
     * @throws ArtifactCreationException
     */
    public function create(
        TrackerData $tracker,
        ProgramIncrementFieldsData $fields_and_values,
        \PFUser $user,
        SubmissionDate $submission_date
    ): void {
        $artifact = $this->artifact_creator->create(
            $tracker->getFullTracker(),
            $fields_and_values->toFieldsDataArray(),
            $user,
            $submission_date->getValue(),
            false,
            false,
            new ChangesetWithFieldsValidationContext(new SystemActionContext())
        );
        if (! $artifact) {
            throw new ArtifactCreationException();
        }
    }
}
