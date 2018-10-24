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
use Tuleap\CrossTracker\Report\CSV\Format\CSVFormatterVisitor;
use Tuleap\CrossTracker\Report\CSV\Format\DateValue;
use Tuleap\CrossTracker\Report\CSV\Format\FormatterParameters;
use Tuleap\CrossTracker\Report\CSV\Format\TextValue;

class CSVRepresentationBuilder
{
    /** @var CSVFormatterVisitor  */
    private $visitor;

    public function __construct(CSVFormatterVisitor $visitor)
    {
        $this->visitor = $visitor;
    }

    /**
     * @return CSVRepresentation
     */
    public function buildHeaderLine(PFUser $user)
    {
        $header_line = new CSVRepresentation();
        $header_line->build(
            [
                "id",
                "project",
                "tracker",
                "submitted_on",
                "last_update_date"
            ],
            $user
        );
        return $header_line;
    }

    /**
     * @return CSVRepresentation
     */
    public function build(\Tracker_Artifact $artifact, PFUser $user)
    {
        $formatter_parameters = new FormatterParameters($user);
        $tracker              = $artifact->getTracker();

        $project_name           = new TextValue($tracker->getProject()->getUnconvertedPublicName());
        $formatted_project_name = $project_name->accept($this->visitor, $formatter_parameters);

        $tracker_name           = new TextValue($tracker->getName());
        $formatted_tracker_name = $tracker_name->accept($this->visitor, $formatter_parameters);

        $submitted_on           = new DateValue($artifact->getSubmittedOn(), true);
        $formatted_submitted_on = $submitted_on->accept($this->visitor, $formatter_parameters);

        $last_update_date           = new DateValue($artifact->getLastUpdateDate(), true);
        $formatted_last_update_date = $last_update_date->accept($this->visitor, $formatter_parameters);

        $representation = new CSVRepresentation();
        $representation->build(
            [
                $artifact->getId(),
                $formatted_project_name,
                $formatted_tracker_name,
                $formatted_submitted_on,
                $formatted_last_update_date
            ],
            $user
        );
        return $representation;
    }
}
