<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV;

use Tracker_Artifact;
use Tuleap\CrossTracker\Report\CSV\Format\BindToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\CSV\Format\FormElementToValueVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\ValueVisitable;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;

class SimilarFieldsFormatter
{
    /** @var CSVFormatterVisitor */
    private $csv_formatter_visitor;
    /** @var BindToValueVisitor */
    private $bind_to_value_visitor;

    public function __construct(
        CSVFormatterVisitor $csv_formatter_visitor,
        BindToValueVisitor $bind_to_value_visitor
    ) {
        $this->csv_formatter_visitor = $csv_formatter_visitor;
        $this->bind_to_value_visitor = $bind_to_value_visitor;
    }

    /**
     * @return mixed[]
     */
    public function formatSimilarFields(
        Tracker_Artifact $artifact,
        SimilarFieldCollection $similar_fields,
        FormatterParameters $parameters
    ) {
        $field_identifiers = $similar_fields->getFieldIdentifiers();
        $last_changeset    = $artifact->getLastChangeset();

        $field_values = [];
        foreach ($field_identifiers as $identifier) {
            $field = $similar_fields->getField($artifact, $identifier);
            if ($field === null) {
                $field_values[] = CSVRepresentation::CSV_EMPTY_VALUE;
                continue;
            }

            $field_values[] = $this->getFieldValue($last_changeset, $field, $parameters);
        }

        return $field_values;
    }

    private function getFieldValue(
        \Tracker_Artifact_Changeset $last_changeset,
        \Tracker_FormElement_Field $field,
        FormatterParameters $parameters
    ) {
        $changeset_value = $last_changeset->getValue($field);
        if ($changeset_value === null) {
            return CSVRepresentation::CSV_EMPTY_VALUE;
        }

        $form_element_visitor = new FormElementToValueVisitor($changeset_value, $this->bind_to_value_visitor);
        $value_holder = $field->accept($form_element_visitor);
        \assert($value_holder instanceof ValueVisitable);
        return $value_holder->accept($this->csv_formatter_visitor, $parameters);
    }
}
