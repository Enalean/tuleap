<?php
/**
 * Copyright Enalean (c) 2014 - Present. All rights reserved.
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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

class Tracker_Artifact_CopyRenderer extends Tracker_Artifact_ReadOnlyRenderer
{

    public function __construct(
        EventManager $event_manager,
        Tracker_Artifact $artifact,
        Tracker_IDisplayTrackerLayout $layout,
        NatureIsChildLinkRetriever $retriever,
        VisitRecorder $visit_recorder,
        HiddenFieldsetsDetector $hidden_fieldsets_detector
    ) {
        parent::__construct($event_manager, $artifact, $layout, $retriever, $visit_recorder, $hidden_fieldsets_detector);
        $this->redirect->query_parameters = array(
            'tracker' => $artifact->getTrackerId(),
            'func'    => 'submit-copy-artifact',
        );
    }

    public function fetchFormContent(Codendi_Request $request, PFUser $current_user)
    {
        $html = '';
        $html .= $this->fetchLastChangesetId();
        $html .= $this->fetchFromArtifactId();
        $html .= parent::fetchFormContent($request, $current_user);
        $html .= $this->fetchSubmitButton($current_user);

        return $this->fetchArtifactForm($html);
    }

    protected function displayHeader()
    {
        $title       = $GLOBALS['Language']->getText('plugin_tracker', 'copy_of', $this->artifact->getXRef());
        $breadcrumbs = array(
            array(
                'title' => $title,
                'url'   => TRACKER_BASE_URL . '/?aid=' . $this->artifact->getId() . '&func=copy-artifact'
            )
        );

        $this->tracker->displayHeader(
            $this->layout,
            $title,
            $breadcrumbs,
            $this->tracker->getDefaultToolbar(),
            array('body_class' => array('widgetable'))
        );
    }

    public function display(Codendi_Request $request, PFUser $current_user)
    {
        parent::display($request, $current_user);
    }

    /**
     * @see Tracker_Artifact_ArtifactRenderer::fetchSubmitButton()
     */
    public function fetchSubmitButton(PFUser $current_user)
    {
        $purifier            = Codendi_HTMLPurifier::instance();
        $copy_label          = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'copy_submit_button');
        $copy_children_label = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'copy_submit_button_children');
        $copy_children_title = $GLOBALS['Language']->getText('plugin_tracker_artifact', 'copy_submit_button_children_title');

        $button = '<button class="btn btn-large btn-primary" type="submit">' . $copy_label . '</button>';

        if (count($this->artifact->getChildrenForUser($current_user)) > 0) {
            $button = '<div class="btn-group dropup">
                <button class="btn btn-large btn-primary" type="submit">' . $copy_label . '</button>
                <button class="btn btn-large btn-primary dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>
                <ul class="dropdown-menu pull-right">
                    <li>
                        <input type="hidden" name="copy_children" id="copy_children" value="0" />
                        <a
                            href="#"
                            title="' . $purifier->purify($copy_children_title) . '"
                            id="copy_children_button">
                            ' . $copy_children_label . '
                        </a>
                    </li>
                </ul>
            </div>';
        }
        return '<div class="artifact-copy-button">
                    <input type="hidden" id="submit-type" />
                    ' . $button
                    . $this->getConcurrentEditMessage() . '
                </div>';
    }

    protected function fetchView(Codendi_Request $request, PFUser $user)
    {
        $view_collection = new Tracker_Artifact_View_ViewCollection();
        $view_collection->add(new Tracker_Artifact_View_Copy($this->artifact, $request, $user, $this));

        return $view_collection->fetchRequestedView($request);
    }

    protected function fetchTitle()
    {
        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '';
        $html .= '<div class="tracker_artifact_title">';
        $html .= $hp->purify($this->artifact->getTitle());
        $html .= '</div>';

        return $html;
    }

    private function fetchLastChangesetId()
    {
        return '<input type="hidden" name="from_changeset_id" value="' . $this->artifact->getLastChangeset()->getId() . '"/>';
    }

    private function fetchFromArtifactId()
    {
        return '<input type="hidden" name="from_artifact_id" value="' . $this->artifact->getId() . '"/>';
    }
}
