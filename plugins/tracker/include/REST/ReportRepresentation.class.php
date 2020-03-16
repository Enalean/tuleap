<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Tracker_Report;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\REST\Artifact\ArtifactRepresentation;

class ReportRepresentation
{

    public const ROUTE = 'tracker_reports';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $uri;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array
     */
    public $resources;

    public function build(Tracker_Report $report)
    {
        $this->id        = JsonCast::toInt($report->getId());
        $this->uri       = self::ROUTE . '/' . $this->id;
        $this->label     = $report->getName();
        $this->resources = array(
            array(
                'type' => 'artifacts',
                'uri'  => $this->uri . '/' . ArtifactRepresentation::ROUTE
            )
        );
    }
}
