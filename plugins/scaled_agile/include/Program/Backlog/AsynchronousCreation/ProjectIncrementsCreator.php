<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Program\Backlog\AsynchronousCreation;

use Tuleap\DB\DBTransactionExecutor;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\ProjectIncrementFieldsData;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\Status\StatusValueMapper;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldRetrievalException;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Data\SynchronizedFields\SynchronizedFieldsGatherer;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Changeset\Values\SourceChangesetValuesCollection;
use Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Tracker\ProjectIncrementsTrackerCollection;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Changeset\Validation\ChangesetWithFieldsValidationContext;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Validation\SystemActionContext;

class ProjectIncrementsCreator
{
    /**
     * @var DBTransactionExecutor
     */
    private $transaction_executor;
    /**
     * @var SynchronizedFieldsGatherer
     */
    private $fields_gatherer;
    /**
     * @var StatusValueMapper
     */
    private $status_mapper;
    /**
     * @var TrackerArtifactCreator
     */
    private $artifact_creator;

    public function __construct(
        DBTransactionExecutor $transaction_executor,
        SynchronizedFieldsGatherer $fields_gatherer,
        StatusValueMapper $status_mapper,
        TrackerArtifactCreator $artifact_creator
    ) {
        $this->transaction_executor = $transaction_executor;
        $this->fields_gatherer      = $fields_gatherer;
        $this->status_mapper        = $status_mapper;
        $this->artifact_creator     = $artifact_creator;
    }

    /**
     * @throws ProjectIncrementArtifactCreationException
     * @throws SynchronizedFieldRetrievalException
     */
    public function createProjectIncrements(
        SourceChangesetValuesCollection $copied_values,
        ProjectIncrementsTrackerCollection $project_increments_tracker_collection,
        \PFUser $current_user
    ): void {
        $this->transaction_executor->execute(
            function () use ($copied_values, $project_increments_tracker_collection, $current_user) {
                foreach ($project_increments_tracker_collection->getProjectIncrementTrackers() as $project_increment_tracker) {
                    $synchronized_fields = $this->fields_gatherer->gather($project_increment_tracker);
                    $mapped_status       = $this->status_mapper->mapStatusValueByDuckTyping($copied_values, $synchronized_fields);
                    $fields_data         = ProjectIncrementFieldsData::fromSourceChangesetValuesAndSynchronizedFields(
                        $copied_values,
                        $mapped_status,
                        $synchronized_fields
                    );
                    $result              = $this->artifact_creator->create(
                        $project_increment_tracker,
                        $fields_data->toFieldsDataArray(),
                        $current_user,
                        $copied_values->getSubmittedOn(),
                        false,
                        false,
                        new ChangesetWithFieldsValidationContext(new SystemActionContext())
                    );
                    if (! $result) {
                        throw new ProjectIncrementArtifactCreationException($copied_values->getSourceArtifactId());
                    }
                }
            }
        );
    }
}
