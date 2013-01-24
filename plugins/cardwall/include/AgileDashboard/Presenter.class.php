<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class Cardwall_AgileDashboard_Presenter {
    /**
     * @var Tracker_Artifact_Burndown_Pane
     */
    private $pane;

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Cardwall_Pane $pane, Planning_Milestone $milestone) {
        $this->pane = $pane;
        $this->milestone = $milestone;
    }

    public function milestone_title() {
        return $this->milestone->getArtifactTitle();
    }

    public function identifier() {
        return $this->pane->getIdentifier();
    }

    public function full_content() {
        return $this->pane->getFullContent();
    }
}

?>
