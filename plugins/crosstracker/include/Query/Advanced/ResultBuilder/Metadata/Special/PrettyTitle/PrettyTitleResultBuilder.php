<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\PrettyTitle;

use LogicException;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\PrettyTitleRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Semantic\Title\RetrieveSemanticTitleField;

final readonly class PrettyTitleResultBuilder implements BuildResultPrettyTitle
{
    public function __construct(
        private RetrieveArtifact $retrieve_artifact,
        private RetrieveSemanticTitleField $semantic_retriever,
    ) {
    }

    #[\Override]
    public function getResult(array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (isset($values[$id])) {
                continue;
            }

            $tracker_name  = $result['@pretty_title.tracker'];
            $tracker_color = $result['@pretty_title.color'];
            $title         = $result['@pretty_title'];

            $artifact = $this->retrieve_artifact->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }

            $field = $this->semantic_retriever->fromTracker($artifact->getTracker());

            if ($field !== null) {
                $title = ! $field->userCanRead($user) ? '' : $title;
            }

            $values[$id] = new SelectedValue('@pretty_title', new PrettyTitleRepresentation($tracker_name, $tracker_color, $id, $title ?? ''));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@pretty_title', CrossTrackerSelectedType::TYPE_PRETTY_TITLE),
            $values,
        );
    }
}
