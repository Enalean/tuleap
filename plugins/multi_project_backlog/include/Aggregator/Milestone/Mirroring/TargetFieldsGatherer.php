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

use Tuleap\MultiProjectBacklog\Aggregator\Milestone\NoArtifactLinkFieldException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\NoTitleFieldException;
use Tuleap\MultiProjectBacklog\Aggregator\Milestone\SynchronizedFieldRetrievalException;

class TargetFieldsGatherer
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var \Tracker_Semantic_TitleFactory
     */
    private $semantic_title_factory;

    public function __construct(
        \Tracker_FormElementFactory $form_element_factory,
        \Tracker_Semantic_TitleFactory $semantic_title_factory
    ) {
        $this->form_element_factory   = $form_element_factory;
        $this->semantic_title_factory = $semantic_title_factory;
    }

    /**
     * @throws SynchronizedFieldRetrievalException
     */
    public function gather(\Tracker $contributor_milestone_tracker): TargetFields
    {
        return new TargetFields(
            $this->getArtifactLinkField($contributor_milestone_tracker),
            $this->getTitleField($contributor_milestone_tracker)
        );
    }

    /**
     * @throws NoArtifactLinkFieldException
     */
    private function getArtifactLinkField(\Tracker $milestone_tracker): \Tracker_FormElement_Field_ArtifactLink
    {
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($milestone_tracker);
        if (count($artifact_link_fields) > 0) {
            return $artifact_link_fields[0];
        }
        throw new NoArtifactLinkFieldException((int) $milestone_tracker->getId());
    }

    /**
     * @throws NoTitleFieldException
     */
    private function getTitleField(\Tracker $milestone_tracker): \Tracker_FormElement_Field_Text
    {
        $title_field = $this->semantic_title_factory->getByTracker($milestone_tracker)->getField();
        if (! $title_field) {
            throw new NoTitleFieldException((int) $milestone_tracker->getId());
        }
        return $title_field;
    }
}
