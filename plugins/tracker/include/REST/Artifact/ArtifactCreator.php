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

use PFUser;
use Tracker;
use Tuleap\DB\DBTransactionExecutor;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValue\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\Artifact\ChangesetValue\ArtifactLink\NewArtifactLinkInitialChangesetValue;
use Tuleap\Tracker\Artifact\ChangesetValue\InitialChangesetValuesContainer;
use Tuleap\Tracker\Artifact\Creation\TrackerArtifactCreator;
use Tuleap\Tracker\Artifact\RetrieveTracker;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataBuilder;
use Tuleap\Tracker\REST\Artifact\ChangesetValue\FieldsDataFromValuesByFieldBuilder;
use Tuleap\Tracker\REST\TrackerReference;
use Tuleap\Tracker\REST\v1\ArtifactValuesRepresentation;

class ArtifactCreator
{
    public function __construct(
        private readonly FieldsDataBuilder $fields_data_builder,
        private readonly TrackerArtifactCreator $artifact_creator,
        private readonly RetrieveTracker $tracker_factory,
        private readonly FieldsDataFromValuesByFieldBuilder $values_by_field_builder,
        private readonly AddDefaultValuesToFieldsData $default_values_adder,
        private readonly VerifySubmissionPermissions $submission_permission_verifier,
        private readonly DBTransactionExecutor $transaction_executor,
        private readonly AddReverseLinks $reverse_links_adder,
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
            $this->artifact_creator->create(
                $tracker,
                new InitialChangesetValuesContainer($fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
                $submitter,
                \Tuleap\Request\RequestTime::getTimestamp(),
                true,
                $should_visit_be_recorded,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext(),
            ),
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
            $this->artifact_creator->create(
                $tracker,
                new InitialChangesetValuesContainer($fields_data, Option::nothing(NewArtifactLinkInitialChangesetValue::class)),
                $user,
                \Tuleap\Request\RequestTime::getTimestamp(),
                true,
                true,
                new \Tuleap\Tracker\Changeset\Validation\NullChangesetValidationContext(),
            ),
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
        ?Artifact $artifact,
        string $format,
        PFUser $submitter,
        InitialChangesetValuesContainer $changeset_values,
        array $values,
    ): ArtifactReference {
        if ($artifact) {
            $should_add_reverse_links = ! $this->isLinkKeyUsed($values);
            if ($should_add_reverse_links) {
                $this->reverse_links_adder->addReverseLinks($submitter, $changeset_values, $artifact);
            }
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
            if (isset($value->links) && is_array($value->links)) {
                return true;
            }
        }
        return false;
    }
}
