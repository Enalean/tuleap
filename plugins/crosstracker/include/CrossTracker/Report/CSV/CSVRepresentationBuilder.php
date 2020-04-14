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

use PFUser;
use Tracker_Artifact;
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\DateValue;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\CSV\Format\TextValue;
use Tuleap\CrossTracker\Report\CSV\Format\UserValue;
use Tuleap\CrossTracker\Report\SimilarField\SimilarFieldCollection;
use UserManager;

class CSVRepresentationBuilder
{
    /** @var CSVFormatterVisitor  */
    private $visitor;
    /** @var UserManager */
    private $user_manager;
    /** @var SimilarFieldsFormatter */
    private $similar_fields_formatter;

    public function __construct(
        CSVFormatterVisitor $visitor,
        UserManager $user_manager,
        SimilarFieldsFormatter $similar_fields_formatter
    ) {
        $this->visitor                  = $visitor;
        $this->user_manager             = $user_manager;
        $this->similar_fields_formatter = $similar_fields_formatter;
    }

    /**
     * @return CSVRepresentation
     */
    public function buildHeaderLine(PFUser $user, SimilarFieldCollection $similar_fields)
    {
        $semantic_and_always_there_fields = [
            "id",
            "project",
            "tracker",
            "title",
            "description",
            "status",
            "submitted_by",
            "submitted_on",
            "last_update_by",
            "last_update_date"
        ];

        $all_fields = array_merge(
            $semantic_and_always_there_fields,
            $similar_fields->getFieldNames()
        );

        $header_line = new CSVRepresentation();
        $header_line->build(
            $all_fields,
            $user
        );
        return $header_line;
    }

    /**
     * @return CSVRepresentation
     */
    public function build(Tracker_Artifact $artifact, PFUser $user, SimilarFieldCollection $similar_fields)
    {
        $formatter_parameters                       = new FormatterParameters($user);
        $semantic_and_always_there_formatted_values = $this->formatSemanticsAndAlwaysThereFields(
            $artifact,
            $formatter_parameters
        );

        $similar_field_values = $this->similar_fields_formatter->formatSimilarFields(
            $artifact,
            $similar_fields,
            $formatter_parameters
        );

        $all_values = array_merge(
            $semantic_and_always_there_formatted_values,
            $similar_field_values
        );

        $representation = new CSVRepresentation();
        $representation->build($all_values, $user);
        return $representation;
    }

    /**
     * @return mixed[]
     */
    private function formatSemanticsAndAlwaysThereFields(
        Tracker_Artifact $artifact,
        FormatterParameters $formatter_parameters
    ) {
        $tracker      = $artifact->getTracker();
        $project_name = new TextValue($tracker->getProject()->getPublicName());
        $formatted_project_name = $project_name->accept($this->visitor, $formatter_parameters);

        $tracker_name = new TextValue($tracker->getName());
        $formatted_tracker_name = $tracker_name->accept($this->visitor, $formatter_parameters);

        $submitted_by_user = $this->user_manager->getUserById($artifact->getSubmittedBy());
        $submitted_by      = new UserValue($submitted_by_user);
        $formatted_submitted_by = $submitted_by->accept($this->visitor, $formatter_parameters);

        $submitted_on = new DateValue($artifact->getSubmittedOn(), true);
        $formatted_submitted_on = $submitted_on->accept($this->visitor, $formatter_parameters);

        $last_update_by_user = $this->user_manager->getUserById($artifact->getLastModifiedBy());
        $last_update_by      = new UserValue($last_update_by_user);
        $formatted_last_update_by = $last_update_by->accept($this->visitor, $formatter_parameters);

        $last_update_date = new DateValue($artifact->getLastUpdateDate(), true);
        $formatted_last_update_date = $last_update_date->accept($this->visitor, $formatter_parameters);

        $title = new TextValue($artifact->getTitle() ?? '');
        $formatted_title = $title->accept($this->visitor, $formatter_parameters);

        $description = new TextValue((string) $artifact->getDescription());
        $formatted_description = $description->accept($this->visitor, $formatter_parameters);

        $status = new TextValue($artifact->getStatus());
        $formatted_status = $status->accept($this->visitor, $formatter_parameters);

        return [
            $artifact->getId(),
            $formatted_project_name,
            $formatted_tracker_name,
            $formatted_title,
            $formatted_description,
            $formatted_status,
            $formatted_submitted_by,
            $formatted_submitted_on,
            $formatted_last_update_by,
            $formatted_last_update_date
        ];
    }
}
