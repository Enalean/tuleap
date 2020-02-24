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

namespace Tuleap\CrossTracker\Report\SimilarField;

use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;

class SimilarFieldsMatcher
{
    /** @var SupportedFieldsDao */
    private $similar_fields_dao;
    /** @var \Tracker_FormElementFactory */
    private $form_element_factory;
    /** @var SimilarFieldsFilter */
    private $similar_fields_filter;
    /** @var BindNameVisitor */
    private $bind_name_visitor;

    public function __construct(
        SupportedFieldsDao $similar_fields_dao,
        \Tracker_FormElementFactory $form_element_factory,
        SimilarFieldsFilter $similar_fields_filter,
        BindNameVisitor $bind_name_visitor
    ) {
        $this->similar_fields_dao    = $similar_fields_dao;
        $this->form_element_factory  = $form_element_factory;
        $this->similar_fields_filter = $similar_fields_filter;
        $this->bind_name_visitor     = $bind_name_visitor;
    }

    /**
     * @return SimilarFieldCollection
     */
    public function getSimilarFieldsCollection(CrossTrackerReport $report, \PFUser $user)
    {
        $rows = $this->similar_fields_dao->searchByTrackerIds($report->getTrackerIds());

        $similar_field_candidates = [];
        foreach ($rows as $row) {
            $field      = $this->form_element_factory->getCachedInstanceFromRow($row);
            $similar_field_candidates[] = $this->buildCandidate($field, $row['formElement_type']);
        }
        $similar_fields_without_permissions_verification = new SimilarFieldCollection(...$similar_field_candidates);

        $similar_field_candidates_not_used_in_semantics = new SimilarFieldCollection(
            ...$this->similar_fields_filter->filterCandidatesUsedInSemantics(
                ...$similar_fields_without_permissions_verification
            )
        );

        $similar_field_candidates_with_permissions_verification = [];
        foreach ($similar_field_candidates_not_used_in_semantics as $similar_field_candidate) {
            if ($similar_field_candidate->getField()->userCanRead($user)) {
                $similar_field_candidates_with_permissions_verification[] = $similar_field_candidate;
            }
        }

        return new SimilarFieldCollection(...$similar_field_candidates_with_permissions_verification);
    }

    private function buildCandidate(\Tracker_FormElement_Field $field, $type_shortname)
    {
        $bind_name = null;
        if ($field instanceof \Tracker_FormElement_Field_List) {
            $bind = $field->getBind();
            $bind_name = $bind->accept($this->bind_name_visitor, new BindParameters($field));
        }
        $type = new SimilarFieldType($type_shortname, $bind_name);

        $identifier = new SimilarFieldIdentifier($field->getName(), $bind_name);

        return new SimilarFieldCandidate($identifier, $type, $field);
    }
}
