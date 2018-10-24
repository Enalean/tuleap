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

class CSVRepresentationBuilder
{
    /**
     * @return CSVRepresentation
     */
    public function build(\Tracker_Artifact $artifact, PFUser $user)
    {
        $tracker        = $artifact->getTracker();
        $project_name   = $tracker->getProject()->getUnconvertedPublicName();
        $tracker_name   = $tracker->getName();
        $representation = new CSVRepresentation();
        $representation->build([$artifact->getId(), $project_name, $tracker_name], $user);
        return $representation;
    }

    /**
     * @return CSVRepresentation
     */
    public function buildHeaderLine(PFUser $user)
    {
        $header_line = new CSVRepresentation();
        $header_line->build(["id", "project", "tracker"], $user);
        return $header_line;
    }
}
