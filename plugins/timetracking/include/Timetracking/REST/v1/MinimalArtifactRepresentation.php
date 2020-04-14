<?php
/**
 * Copyright Enalean (c) 2018-Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Timetracking\REST\v1;

use Tracker_Artifact;
use Tuleap\REST\JsonCast;

class MinimalArtifactRepresentation
{
    public const ROUTE = 'artifacts';

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
    public $xref;
    /**
     * @var string
     */
    public $html_url;
    /**
     * @var string
     */
    public $title;
    /**
     * @var string
     */
    public $badge_color;
    /**
     * @var string
     */
    public $submission_date;

    public function build(Tracker_Artifact $artifact)
    {
        $this->id   = JsonCast::toInt($artifact->getId());
        $this->uri  = self::ROUTE . '/' . $this->id;
        $this->xref = $artifact->getXRef();

        $this->html_url        = $artifact->getUri();
        $this->title           = $artifact->getTitle() ?? '';
        $this->badge_color     = $artifact->getTracker()->getColor()->getName();
        $this->submission_date = JsonCast::toDate($artifact->getSubmittedOn());
    }
}
