<?php
/*
 * Copyright Enalean (c) 2013 - Present. All rights reserved.
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

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Renderer\ListFieldsIncluder;

class Tracker_Artifact_SubmitOverlayRenderer extends Tracker_Artifact_SubmitAbstractRenderer
{
    /**
     * @var Artifact
     */
    private $source_artifact;

    /**
     * @var Tracker_IFetchTrackerSwitcher $tracker_switcher
     */
    private $tracker_switcher;

    /**
     * @var PFUser
     */
    private $current_user;

    public function __construct(Tracker $tracker, Artifact $source_artifact, EventManager $event_manager, Tracker_IFetchTrackerSwitcher $tracker_switcher)
    {
        parent::__construct($tracker, $event_manager);
        $this->source_artifact  = $source_artifact;
        $this->tracker_switcher = $tracker_switcher;
    }

    public function display(Codendi_Request $request, PFUser $current_user)
    {
        $this->current_user = $current_user;

        parent::display($request, $current_user);
    }

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user)
    {
        return $this->fetchArtifactForm(
            $this->fetchNewArtifactForm($request, $current_user)
        );
    }

    protected function displayHeader()
    {
        $GLOBALS['HTML']->overlay_header();
        $this->displayTrackerSwitcher($this->current_user);
        ListFieldsIncluder::includeListFieldsAssets();
        echo $this->fetchSubmitInstructions();
    }

    private function displayTrackerSwitcher(PFUser $current_user)
    {
        $project = null;
        if ($this->source_artifact) {
            $project = $this->source_artifact->getTracker()->getProject();
            $GLOBALS['Response']->addFeedback(
                'warning',
                sprintf(dgettext('tuleap-tracker', 'This artifact (of %2$s) will be linked to %1$s'), $this->source_artifact->fetchDirectLinkToArtifact(), $this->tracker_switcher->fetchTrackerSwitcher($current_user, ' ', $project, $this->tracker)),
                CODENDI_PURIFIER_DISABLED
            );
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Error the artifact to link doesn\'t exist');
        }
        $GLOBALS['Response']->displayFeedback();
    }

    private function fetchNewArtifactForm(Codendi_Request $request, PFUser $current_user)
    {
        $html = '';

        $html .= '<input type="hidden" name="link-artifact-id" value="' . $this->source_artifact->getId() . '" />';
        if ($request->get('immediate')) {
            $html .= '<input type="hidden" name="immediate" value="1" />';
        }

        $html .= $this->fetchFormElements($request, $current_user);

        $html .= '<input class="btn btn-primary" type="submit" id="tracker_artifact_submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" />';

        return $html;
    }

    protected function displayFooter()
    {
        $GLOBALS['HTML']->overlay_footer();
    }
}
