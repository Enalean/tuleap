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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Description;

use LogicException;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Semantic\Description\RetrieveSemanticDescriptionField;

final readonly class DescriptionResultBuilder implements BuildResultDescription
{
    public function __construct(
        private RetrieveArtifact $retrieve_artifact,
        private TextValueInterpreter $text_value_interpreter,
        private RetrieveSemanticDescriptionField $semantic_retriever,
    ) {
    }

    #[\Override]
    public function getResult(Metadata $metadata, array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];
        $alias  = $metadata->getName();

        foreach ($select_results as $result) {
            $id = (int) $result['id'];
            if (isset($values[$id])) {
                continue;
            }
            $value  = $result[$alias];
            $format = $result[$alias . '_format'];

            if ($value === null) {
                $values[$id] = $this->getEmptyValue($metadata);
                continue;
            }

            $artifact = $this->retrieve_artifact->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }

            $field = $this->semantic_retriever->fromTracker($artifact->getTracker());
            if ($field === null || ! $field->userCanRead($user)) {
                $values[$id] = $this->getEmptyValue($metadata);
            } else {
                $interpreted_value = $this->text_value_interpreter->interpretValueAccordingToFormat($format, $value, (int) $artifact->getTracker()->getGroupId());
                $values[$id]       = new SelectedValue($metadata->getName(), new TextResultRepresentation($interpreted_value));
            }
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($metadata->getName(), CrossTrackerSelectedType::TYPE_TEXT),
            $values,
        );
    }

    private function getEmptyValue(Metadata $metadata): SelectedValue
    {
        return new SelectedValue($metadata->getName(), new TextResultRepresentation(''));
    }
}
