<?php
/**
 * Copyright Enalean (c) 2013 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
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

use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

abstract class Tracker_Artifact_EditAbstractRenderer extends Tracker_Artifact_ArtifactRenderer
{
    /**
     * @var Tracker_Artifact
     */
    protected $artifact;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;

    public function __construct(Tracker_Artifact $artifact, EventManager $event_manager, VisitRecorder $visit_recorder)
    {
        parent::__construct($artifact->getTracker(), $event_manager);
        $this->artifact = $artifact;

        $this->redirect->query_parameters = array(
            'aid'       => $this->artifact->getId(),
            'func'      => 'artifact-update',
        );
        $this->visit_recorder = $visit_recorder;
    }

    public function display(Codendi_Request $request, PFUser $current_user)
    {
        $this->visit_recorder->record($current_user, $this->artifact);
        parent::display($request, $current_user);
    }

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user)
    {
        return $this->fetchArtifactInformations($this->artifact);
    }

    private function fetchArtifactInformations(Tracker_Artifact $artifact)
    {
        $html          = "";
        $html_purifier = Codendi_HTMLPurifier::instance();
        $artifact_id   = $html_purifier->purify($artifact->getId());
        $changeset_id  = $html_purifier->purify($artifact->getLastChangeset()->getId());

        $html .= '<input type="hidden" id="artifact_informations" data-artifact-id="' . $artifact_id . '" data-changeset-id="' . $changeset_id . '">';

        return $html;
    }
}
