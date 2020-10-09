<?php
/*
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

namespace Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring;

use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Status\NoEndPeriodChangesetValueException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Status\NoStartDateChangesetValueException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\Mirroring\Status\NoStatusChangesetValueException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFields;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldsGatherer;

class CopiedValuesGatherer
{
    /**
     * @var SynchronizedFieldsGatherer
     */
    private $fields_gatherer;

    public function __construct(SynchronizedFieldsGatherer $fields_gatherer)
    {
        $this->fields_gatherer = $fields_gatherer;
    }

    /**
     * @throws MilestoneMirroringException
     * @throws SynchronizedFieldRetrievalException
     */
    public function gather(
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset,
        \Tracker $aggregator_top_milestone_tracker
    ): CopiedValues {
        $fields            = $this->fields_gatherer->gather($aggregator_top_milestone_tracker);
        $title_value       = $this->readTitle($fields, $aggregator_milestone_last_changeset);
        $description_value = $this->readDesription($fields, $aggregator_milestone_last_changeset);
        $status_value      = $this->readStatus($fields, $aggregator_milestone_last_changeset);
        $start_date_value  = $this->readStartDate($fields, $aggregator_milestone_last_changeset);
        $end_period_value  = $this->readEndPeriod($fields, $aggregator_milestone_last_changeset);

        return new CopiedValues(
            $title_value,
            $description_value,
            $status_value,
            (int) $aggregator_milestone_last_changeset->getSubmittedOn(),
            (int) $aggregator_milestone_last_changeset->getArtifact()->getId(),
            $start_date_value,
            $end_period_value
        );
    }

    /**
     * @throws MilestoneMirroringException
     */
    private function readTitle(
        SynchronizedFields $fields,
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset
    ): \Tracker_Artifact_ChangesetValue_String {
        $title_field = $fields->getTitleField();
        $title_value = $aggregator_milestone_last_changeset->getValue($title_field);
        if (! $title_value) {
            throw new NoTitleChangesetValueException(
                (int) $aggregator_milestone_last_changeset->getId(),
                (int) $title_field->getId()
            );
        }
        if (! ($title_value instanceof \Tracker_Artifact_ChangesetValue_String)) {
            throw new UnsupportedTitleFieldException((int) $title_field->getId());
        }
        return $title_value;
    }

    /**
     * @throws NoDescriptionChangesetValueException
     */
    private function readDesription(
        SynchronizedFields $fields,
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset
    ): \Tracker_Artifact_ChangesetValue_Text {
        $description_field = $fields->getDescriptionField();
        $description_value = $aggregator_milestone_last_changeset->getValue($description_field);
        if (! $description_value) {
            throw new NoDescriptionChangesetValueException(
                (int) $aggregator_milestone_last_changeset->getId(),
                (int) $description_field->getId()
            );
        }
        assert($description_value instanceof \Tracker_Artifact_ChangesetValue_Text);
        return $description_value;
    }

    /**
     * @throws NoStatusChangesetValueException
     */
    private function readStatus(
        SynchronizedFields $fields,
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset
    ): \Tracker_Artifact_ChangesetValue_List {
        $status_field = $fields->getStatusField();
        $status_value = $aggregator_milestone_last_changeset->getValue($status_field);
        if (! $status_value) {
            throw new NoStatusChangesetValueException(
                (int) $aggregator_milestone_last_changeset->getId(),
                (int) $status_field->getId()
            );
        }
        assert($status_value instanceof \Tracker_Artifact_ChangesetValue_List);
        return $status_value;
    }

    private function readStartDate(
        SynchronizedFields $fields,
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset
    ): \Tracker_Artifact_ChangesetValue_Date {
        $start_date_field = $fields->getStartDateField();
        $start_date_value = $aggregator_milestone_last_changeset->getValue($start_date_field);

        if (! $start_date_value) {
            throw new NoStartDateChangesetValueException(
                (int) $aggregator_milestone_last_changeset->getId(),
                (int) $start_date_field->getId()
            );
        }
        assert($start_date_value instanceof \Tracker_Artifact_ChangesetValue_Date);
        return $start_date_value;
    }

    /**
     * @return \Tracker_Artifact_ChangesetValue_Numeric|\Tracker_Artifact_ChangesetValue_Date
     */
    private function readEndPeriod(
        SynchronizedFields $fields,
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset
    ) {
        $end_period_field = $fields->getTimeframeFields()->getEndPeriodField();
        $end_period_value = $aggregator_milestone_last_changeset->getValue($end_period_field);

        if (! $end_period_value) {
            throw new NoEndPeriodChangesetValueException(
                (int) $aggregator_milestone_last_changeset->getId(),
                (int) $end_period_field->getId()
            );
        }

        assert($end_period_value instanceof \Tracker_Artifact_ChangesetValue_Date ||
               $end_period_value instanceof  \Tracker_Artifact_ChangesetValue_Numeric);
        return $end_period_value;
    }
}
