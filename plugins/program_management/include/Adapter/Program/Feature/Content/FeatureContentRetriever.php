<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Feature\Content;

use Tuleap\ProgramManagement\Adapter\Program\Feature\FeatureRepresentationBuilder;
use Tuleap\ProgramManagement\Adapter\Program\Tracker\ProgramTrackerException;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\ContentStore;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveProgramIncrement;
use Tuleap\ProgramManagement\Program\Backlog\Feature\Content\RetrieveFeatureContent;

class FeatureContentRetriever implements RetrieveFeatureContent
{
    /**
     * @var ContentStore
     */
    private $content_store;
    /**
     * @var RetrieveProgramIncrement
     */
    private $program_increment_content_retriever;
    /**
     * @var FeatureRepresentationBuilder
     */
    private $feature_representation_builder;

    public function __construct(
        RetrieveProgramIncrement $program_increment_content_retriever,
        ContentStore $content_store,
        FeatureRepresentationBuilder $feature_representation_builder
    ) {
        $this->content_store                       = $content_store;
        $this->program_increment_content_retriever = $program_increment_content_retriever;
        $this->feature_representation_builder      = $feature_representation_builder;
    }

    /**
     * @throws ProgramIncrementNotFoundException
     * @throws ProgramTrackerException
     */
    public function retrieveProgramIncrementContent(int $id, \PFUser $user): array
    {
        $program_increment = $this->program_increment_content_retriever->retrieveProgramIncrement($id, $user);

        $planned_content = $this->content_store->searchContent($program_increment);

        $elements = [];
        foreach ($planned_content as $artifact) {
            $feature = $this->feature_representation_builder->buildFeatureRepresentation(
                $user,
                $artifact['artifact_id'],
                $artifact['field_title_id'],
                $artifact['artifact_title']
            );
            if ($feature) {
                $elements[] = $feature;
            }
        }

        return $elements;
    }
}
