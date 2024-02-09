<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\Artifact;

use Luracast\Restler\RestException;
use PFUser;
use Tracker;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\CreateNewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\AddReverseLinksCommand;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\ReverseLinksToNewChangesetsConverter;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\RetrieveTracker;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\FaultMapper;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class ArtifactCreator
{
    public function __construct(
        private readonly FieldsDataBuilder $fields_data_builder,
        private readonly \Tracker_ArtifactFactory $artifact_factory,
        private readonly RetrieveTracker $tracker_factory,
        private readonly FieldsDataFromValuesByFieldBuilder $values_by_field_builder,
        private readonly AddDefaultValuesToFieldsData $default_values_adder,
        private readonly VerifySubmissionPermissions $submission_permission_verifier,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly ReverseLinksToNewChangesetsConverter $changesets_converter,
        private readonly CreateNewChangeset $changeset_creator,
    ) {
    }

    /**
     *
     * @param ArtifactValuesRepresentation[] $values
     * @throws \Luracast\Restler\RestException
     */
    public function create(
        PFUser $submitter,
        TrackerReference $tracker_reference,
        array $values,
        bool $should_visit_be_recorded,
    ): ArtifactReference {
        $tracker          = $this->getTracker($tracker_reference);
        $changeset_values = $this->fields_data_builder->getFieldsDataOnCreate($values, $tracker);

        $fields_data = $this->default_values_adder->getUsedFieldsWithDefaultValue(
            $tracker,
            $changeset_values->getFieldsData(),
            $submitter
        );
        $this->checkUserCanSubmit($submitter, $tracker);

        return $this->transaction_executor->execute(fn(): ArtifactReference => $this->returnReferenceOrError(
            $this->artifact_factory->createArtifact($tracker, $fields_data, $submitter, $should_visit_be_recorded),
            '',
            $submitter,
            $changeset_values,
            $values
        ));
    }

    /**
     *
     * @param array $values
     * @return ArtifactReference
     * @throws \Luracast\Restler\RestException
     */
    public function createWithValuesIndexedByFieldName(PFUser $user, TrackerReference $tracker_reference, array $values)
    {
        $tracker          = $this->getTracker($tracker_reference);
        $changeset_values = $this->values_by_field_builder->getFieldsDataOnCreate($values, $tracker);
        $fields_data      = $this->default_values_adder->getUsedFieldsWithDefaultValue(
            $tracker,
            $changeset_values->getFieldsData(),
            $user
        );
        $this->checkUserCanSubmit($user, $tracker);

        return $this->transaction_executor->execute(fn(): ArtifactReference => $this->returnReferenceOrError(
            $this->artifact_factory->createArtifact($tracker, $fields_data, $user, true),
            'by_field',
            $user,
            $changeset_values,
            $values
        ));
    }

    private function getTracker(TrackerReference $tracker_reference): Tracker
    {
        $tracker = $this->tracker_factory->getTrackerById($tracker_reference->id);
        if (! $tracker) {
            throw new \Luracast\Restler\RestException(404, 'Tracker not found');
        }
        return $tracker;
    }

    /**
     * @param ''|'by_field' $format
     * @throws \Luracast\Restler\RestException
     */
    private function returnReferenceOrError(
        Artifact|false $artifact,
        string $format,
        PFUser $submitter,
        InitialChangesetValuesContainer $changeset_values,
        array $values,
    ): ArtifactReference {
        if ($artifact) {
            $this->addReverseLinks($submitter, $changeset_values, $artifact, $values);
            return ArtifactReference::build($artifact, $format);
        }
        if ($GLOBALS['Response']->feedbackHasErrors()) {
            throw new \Luracast\Restler\RestException(400, $GLOBALS['Response']->getRawFeedback());
        }
        throw new \Luracast\Restler\RestException(500, 'Unable to create artifact');
    }

    public function checkUserCanSubmit(PFUser $user, Tracker $tracker): void
    {
        if (! $this->submission_permission_verifier->canUserSubmitArtifact($user, $tracker)) {
            throw new \Luracast\Restler\RestException(
                403,
                dgettext(
                    'tuleap-tracker',
                    'You can\'t submit an artifact because you do not have the right to submit all required fields'
                )
            );
        }
    }

    private function isLinkKeyUsed(array $values): bool
    {
        foreach ($values as $value) {
            if (is_array($value->links)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @throws RestException
     * @throws \Tracker_Exception
     * @throws \Tuleap\Tracker\Artifact\Exception\FieldValidationException
     */
    private function addReverseLinks(
        PFUser $submitter,
        InitialChangesetValuesContainer $changeset_values,
        Artifact $artifact,
        array $values,
    ): void {
        $changeset_values->getArtifactLinkValue()->apply(
            function (NewArtifactLinkInitialChangesetValue $artifact_link_value) use (
                $values,
                $submitter,
                $artifact
            ): void {
                if ($artifact_link_value->getParent()->isNothing() && ! $this->isLinkKeyUsed($values)) {
                    $submission_date = new \DateTimeImmutable();
                    $this->changesets_converter->convertAddReverseLinks(
                        AddReverseLinksCommand::fromParts($artifact, $artifact_link_value->getReverseLinks()),
                        $submitter,
                        $submission_date
                    )->match(
                        $this->saveChangesets(...),
                        FaultMapper::mapToRestException(...)
                    );
                }
            }
        );
    }

    /**
     * @param list<NewChangeset> $new_changesets
     * @throws \Tracker_Exception
     * @throws \Tuleap\Tracker\Artifact\Exception\FieldValidationException
     */
    private function saveChangesets(array $new_changesets): void
    {
        foreach ($new_changesets as $changeset) {
            try {
                $this->changeset_creator->create($changeset, PostCreationContext::withNoConfig(true));
            } catch (\Tracker_NoChangeException) {
                //Ignore, it should not stop the update
            }
        }
    }
}
