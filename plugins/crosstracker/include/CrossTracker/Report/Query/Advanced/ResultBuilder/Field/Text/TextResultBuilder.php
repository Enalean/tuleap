<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
namespace Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Field\Text;

use LogicException;
use PFUser;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\Select\DuckTypedFieldSelect;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectResultKey;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\Tracker\Artifact\ChangesetValue\Text\TextValueInterpreter;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final readonly class TextResultBuilder
{
    public function __construct(
        private RetrieveArtifact $retrieve_artifact,
        private TextValueInterpreter $text_value_interpreter,
    ) {
    }

    public function getResult(DuckTypedFieldSelect $field, array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];
        $alias  = SelectResultKey::fromDuckTypedField($field);

        foreach ($select_results as $result) {
            $id     = (int) $result['id'];
            $value  = $result[(string) $alias];
            $format = $result["format_$alias"];

            if ($value === null) {
                continue;
            }

            $artifact = $this->retrieve_artifact->getArtifactById($id);
            if ($artifact === null) {
                throw new LogicException("Artifact #$id not found");
            }

            $interpreted_value = $this->text_value_interpreter->interpretValueAccordingToFormat($format, $value, (int) $artifact->getTracker()->getGroupId());
            $values[$id]       = new SelectedValue($field->name, new TextResultRepresentation($interpreted_value));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($field->name, CrossTrackerSelectedType::TYPE_TEXT),
            $values,
        );
    }
}
