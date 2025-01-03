<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

/**
 * A swimline in the dashboard
 */
class Cardwall_SwimlineSoloNoMatchingColumns extends Cardwall_Swimline
{
    /**
     * @var Artifact
     */
    private $artifact;

    public function __construct(Cardwall_CardInCellPresenter $swimline_artifact_presenter, Artifact $artifact, array $cells)
    {
        parent::__construct($swimline_artifact_presenter, $cells);

        $this->artifact = $artifact;
    }

    /**
     * @var bool
     */
    public $is_no_matching_column = true;

    public function getErrorMessage()
    {
        $backlog_item_name = $this->artifact->getTitle();
        $tracker_name      = $this->artifact->getTracker()->getName();
        $uri               = $this->artifact->getUri();

        return sprintf(dgettext('tuleap-cardwall', 'The backlog item <a href="%3$s"><u>%1$s</u></a> cannot be displayed since the column matchings have not be defined between <b>%2$s</b> and this planning'), $backlog_item_name ?? '', $tracker_name, $uri);
    }
}
