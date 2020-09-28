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

use Tuleap\MultiProjectBacklog\Aggregator\Milestone\NoTitleFieldException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;

class CopiedValuesGatherer
{
    /**
     * @var \Tracker_Semantic_TitleFactory
     */
    private $semantic_title_factory;

    public function __construct(\Tracker_Semantic_TitleFactory $semantic_title_factory)
    {
        $this->semantic_title_factory = $semantic_title_factory;
    }

    /**
     * @throws MilestoneMirroringException
     * @throws SynchronizedFieldRetrievalException
     */
    public function gather(
        \Tracker_Artifact_Changeset $aggregator_milestone_last_changeset,
        \Tracker $aggregator_top_milestone_tracker
    ): CopiedValues {
        $semantic_title       = $this->semantic_title_factory->getByTracker($aggregator_top_milestone_tracker);
        $semantic_title_field = $semantic_title->getField();
        if (! $semantic_title_field) {
            throw new NoTitleFieldException((int) $aggregator_top_milestone_tracker->getId());
        }

        $title_value = $aggregator_milestone_last_changeset->getValue($semantic_title_field);
        if (! $title_value) {
            throw new NoTitleChangesetValueException(
                (int) $aggregator_milestone_last_changeset->getId(),
                (int) $semantic_title_field->getId()
            );
        }
        if (! ($title_value instanceof \Tracker_Artifact_ChangesetValue_String)) {
            throw new UnsupportedTitleFieldException((int) $semantic_title_field->getId());
        }

        return new CopiedValues(
            $title_value,
            (int) $aggregator_milestone_last_changeset->getSubmittedOn(),
            (int) $aggregator_milestone_last_changeset->getArtifact()->getId()
        );
    }
}
