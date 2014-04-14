<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
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

class Tracker_Artifact_EditRenderer extends Tracker_Artifact_EditAbstractRenderer {

    /**
     * @var Tracker_FormElementFactory
     */
    private $formelement_factory;

    /**
     * @var Tracker_IDisplayTrackerLayout
     */
    private $layout;

    /**
     * @var Tracker_Artifact[]
     */
    private $hierarchy;

    public function __construct(EventManager $event_manager, Tracker_Artifact $artifact, Tracker_FormElementFactory $formelement_factory, Tracker_IDisplayTrackerLayout $layout) {
        parent::__construct($artifact, $event_manager);
        $this->formelement_factory = $formelement_factory;
        $this->layout              = $layout;
    }

    /**
     * Display the artifact
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param PFUser                           $current_user    The current user
     *
     * @return void
     */
    public function display(Codendi_Request $request, PFUser $current_user) {
        // the following statement needs to be called before displayHeader
        // in order to get the feedback, if any
        $this->hierarchy = $this->artifact->getAllAncestors($current_user);

        parent::display($request, $current_user);
    }

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user) {
        $html  = parent::fetchFormContent($request, $current_user);
        $html .= $this->fetchTitleInHierarchy($this->hierarchy);
        $html .= $this->fetchView($request, $current_user);
        return $html;
    }

    protected function enhanceRedirect(Codendi_Request $request) {
        $from_aid = $request->get('from_aid');
        if ($from_aid != null) {
            $this->redirect->query_parameters['from_aid'] = $from_aid;
        }
        parent::enhanceRedirect($request);
    }

    protected function displayHeader() {
        $hp          = Codendi_HTMLPurifier::instance();
        $title       = $hp->purify($this->tracker->getItemName(), CODENDI_PURIFIER_CONVERT_HTML)  .' #'. $this->artifact->getId();
        $breadcrumbs = array(
            array('title' => $title,
                  'url'   => TRACKER_BASE_URL.'/?aid='. $this->artifact->getId())
        );
        $this->tracker->displayHeader($this->layout, $title, $breadcrumbs, null, array('body_class' => array('widgetable')));
    }

    private function fetchView(Codendi_Request $request, PFUser $user) {
        $view_collection = new Tracker_Artifact_View_ViewCollection();
        $view_collection->add(new Tracker_Artifact_View_Edit($this->artifact, $request, $user, $this));
        if ($this->artifact->getTracker()->getChildren()) {
            $view_collection->add(new Tracker_Artifact_View_Hierarchy($this->artifact, $request, $user));
        }

        return $view_collection->fetchRequestedView($request);
    }

    private function fetchTitleInHierarchy(array $hierarchy) {
        $html  = '';
        $html .= $this->artifact->fetchHiddenTrackerId();
        if ($hierarchy) {
            array_unshift($hierarchy, $this->artifact);
            $html .= $this->fetchParentsTitle($hierarchy);
        } else {
            $html .= $this->artifact->fetchTitle();
        }
        return $html;
    }

    private function fetchParentsTitle(array $parents, $padding_prefix = '') {
        $html   = '';
        $parent = array_pop($parents);
        if ($parent) {
            $html .= '<ul class="tracker-hierarchy">';
            $html .= '<li>';
            $html .= $padding_prefix;
            $html .= '<div class="tree-last">&nbsp;</div> ';
            if ($parents) {
                $html .= $parent->fetchDirectLinkToArtifactWithTitle();
            } else {
                $html .= $parent->getXRefAndTitle();
            }
            if ($parents) {
                $html .= '</a>';
                $div_prefix = '';
                $div_suffix = '';
                if (count($parents) == 1) {
                    $div_prefix = '<div class="tracker_artifact_title">';
                    $div_suffix = '</div>';
                }
                $html .= $div_prefix;
                $html .= $this->fetchParentsTitle($parents, $padding_prefix . '<div class="tree-blank">&nbsp;</div>');
                $html .= $div_suffix;
            }
            $html .= '</li>';
            $html .= '</ul>';
        }
        return $html;
    }

    protected function displayFooter() {
        $this->tracker->displayFooter($this->layout);
    }
}

?>
