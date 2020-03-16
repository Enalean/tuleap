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

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\ParentOfArtifactCollection;
use Tuleap\Tracker\Artifact\View\Nature;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets\HiddenFieldsetsDetector;

class Tracker_Artifact_EditRenderer extends Tracker_Artifact_EditAbstractRenderer
{
    /**
     * Add tab at the top of artifact view
     *
     * Parameters:
     *  - artifact  Tracker_Artifact
     *  - collection    Tracker_Artifact_View_ViewCollection
     *  - request   Codendi_Request
     *  - user  PFUser
     */
    public const EVENT_ADD_VIEW_IN_COLLECTION = 'tracker_artifact_editrenderer_add_view_in_collection';

    /**
     * @var Tracker_IDisplayTrackerLayout
     */
    protected $layout;
    private $retriever;

    /**
     * @var HiddenFieldsetsDetector
     */
    private $hidden_fieldsets_detector;

    /**
     * @var Tracker_Artifact[]
     */
    private $hierarchy;

    public function __construct(
        EventManager $event_manager,
        Tracker_Artifact $artifact,
        Tracker_IDisplayTrackerLayout $layout,
        NatureIsChildLinkRetriever $retriever,
        VisitRecorder $visit_recorder,
        HiddenFieldsetsDetector $hidden_fieldsets_detector
    ) {
        parent::__construct($artifact, $event_manager, $visit_recorder);
        $this->layout                    = $layout;
        $this->retriever                 = $retriever;
        $this->hidden_fieldsets_detector = $hidden_fieldsets_detector;
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
    public function display(Codendi_Request $request, PFUser $current_user)
    {
        // the following statement needs to be called before displayHeader
        // in order to get the feedback, if any
        $this->hierarchy = $this->artifact->getAllAncestors($current_user);
        parent::display($request, $current_user);
    }

    protected function fetchFormContent(Codendi_Request $request, PFUser $current_user)
    {
        $html  = parent::fetchFormContent($request, $current_user);

        if ($this->artifact->getTracker()->isProjectAllowedToUseNature()) {
            $parents = $this->retriever->getParentsHierarchy($this->artifact);
            if ($parents->isGraph()) {
                $html .= "<div class='alert alert-warning'>" .
                $GLOBALS['Language']->getText('plugin_tracker_artifact_links_natures', 'error_multiple_parents') . "</div>";
            }
            $html .= $this->fetchTitleIsGraph($parents);
        } else {
            $html .= $this->fetchTitleInHierarchy($this->hierarchy);
        }

        $html .= $this->fetchView($request, $current_user);
        return $html;
    }

    protected function enhanceRedirect(Codendi_Request $request)
    {
        $from_aid = $request->get('from_aid');
        if ($from_aid != null) {
            $this->redirect->query_parameters['from_aid'] = $from_aid;
        }
        parent::enhanceRedirect($request);
    }

    protected function displayHeader()
    {
        $hp          = Codendi_HTMLPurifier::instance();
        $title = sprintf(
            '%s - %s #%d',
            substr($this->artifact->getTitle(), 0, 64),
            $this->tracker->getItemName(),
            $this->artifact->getId()
        );
        $breadcrumbs = array(
            array('title' => $this->artifact->getXRef(),
                  'url'   => TRACKER_BASE_URL . '/?aid=' . $this->artifact->getId())
        );
        $toolbar = $this->tracker->getDefaultToolbar();
        $params = [
            'body_class' => ['widgetable'],
            'open_graph' => new \Tuleap\OpenGraph\OpenGraphPresenter(
                HTTPRequest::instance()->getServerUrl() . $this->artifact->getUri(),
                $this->artifact->getTitle(),
                $this->artifact->getDescription()
            )
        ];
        $this->tracker->displayHeader($this->layout, $title, $breadcrumbs, $toolbar, $params);
    }

    protected function fetchView(Codendi_Request $request, PFUser $user)
    {
        $view_collection = new Tracker_Artifact_View_ViewCollection();
        $view_collection->add(new Tracker_Artifact_View_Edit($this->artifact, $request, $user, $this));

        if ($this->artifact->getTracker()->isProjectAllowedToUseNature()) {
            $artifact_links = $this->retriever->getChildren($this->artifact);
            if ($artifact_links->count() > 0) {
                $view_collection->add(new Nature($this->artifact, $request, $user));
            }
        } else {
            if ($this->artifact->getTracker()->getChildren()) {
                $view_collection->add(new Tracker_Artifact_View_Hierarchy($this->artifact, $request, $user));
            }
        }

        EventManager::instance()->processEvent(
            self::EVENT_ADD_VIEW_IN_COLLECTION,
            array(
                'artifact'   => $this->artifact,
                'collection' => $view_collection,
                'request'    => $request,
                'user'       => $user
            )
        );

        return $view_collection->fetchRequestedView($request);
    }

    protected function fetchTitle()
    {
        return $this->artifact->fetchTitle();
    }

    private function fetchTitleIsGraph(ParentOfArtifactCollection $parents)
    {
        $html  = '';
        $html .= $this->artifact->fetchHiddenTrackerId();
        $html .= $this->fetchMultipleParentsTitle($this->artifact, $parents);

        return $html;
    }

    private function fetchTitleInHierarchy(array $hierarchy)
    {
        $html  = '';
        $html .= $this->artifact->fetchHiddenTrackerId();
        if ($hierarchy) {
            array_unshift($hierarchy, $this->artifact);
            $html .= $this->fetchParentsTitle($hierarchy);
        } else {
            $html .= $this->fetchTitle();
        }
        return $html;
    }

    private function fetchMultipleParentsTitle(Tracker_Artifact $artifact, ParentOfArtifactCollection $hierarchy)
    {
        $tab_level = 0;
        $html      = '';
        $html     .= '<ul class="tracker-hierarchy">';
        $parents = array_reverse($hierarchy->getArtifacts());

        foreach ($parents as $parent) {
            foreach ($parent as $father) {
                $html .= '<li>';
                $html .= $this->displayANumberOfBlankTab($tab_level);
                $html .= '<div class="tree-last">&nbsp;</div>';
                $html .= $father->fetchDirectLinkToArtifactWithTitle();
                $html .= '</li>';
            }
            $tab_level++;
        }
        $html .= '</ul>';
        $html .= '<div class="tracker_artifact_title">';
        $html .= '<ul class="tracker-hierarchy">';
        $html .= '<li>';
        $html .= $this->displayANumberOfBlankTab($tab_level);
        $html .= '<div class="tree-last">&nbsp;</div>';
        $html .= $artifact->getXRefAndTitle();
        $html .= '</li>';
        $html .= '</ul>';
        $html .= $artifact->fetchActionButtons();
        $html .= $this->fetchShowHideFieldSetsButton();
        $html .= '</div>';
        return $html;
    }

    private function fetchShowHideFieldSetsButton() : string
    {
        if (! $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($this->artifact)) {
            return '';
        }

        return '<div class="header-spacer"></div>
            <div class="show-hide-fieldsets">' . dgettext('tuleap-tracker', 'Hidden fieldsets:') . '
                <div class="btn-group" data-toggle="buttons-radio">
                    <button type="button" class="btn show-fieldsets"><i class="fa fa-eye"></i></button>
                    <button type="button" class="btn active hide-fieldsets"><i class="fa fa-eye-slash"></i></button>
                </div>
            </div>';
    }

    private function displayANumberOfBlankTab($number)
    {
        $html = "";
        for ($i = 1; $i <= $number; $i++) {
            $html .= '<div class="tree-blank">&nbsp;</div> ';
        }
        return $html;
    }

    /**
     * @param Tracker_Artifact[] $parents
     * @param string             $padding_prefix
     *
     * @return string
     */
    private function fetchParentsTitle(array $parents, $padding_prefix = '')
    {
        $html   = '';
        $parent = array_pop($parents);
        if ($parent) {
            $html .= '<ul class="tracker-hierarchy">';

            $html .= '<li>';
            $html .= $padding_prefix;

            $html .= '<span class="tree-last">&nbsp;</span>';
            if ($parents) {
                $html .= $parent->fetchDirectLinkToArtifactWithTitle();
            } else {
                $html .= $parent->getXRefAndTitle();
            }
            if ($parents) {
                $html .= "</li><li>";

                $div_prefix = '';
                $div_suffix = '';
                if (count($parents) === 1) {
                    $div_prefix = '<span class="tracker_artifact_title">';
                    $div_suffix = '</span>';
                }
                $html .= $div_prefix;
                $html .= $this->fetchParentsTitle(
                    $parents,
                    $padding_prefix . '<span class="tree-blank">&nbsp;</span>'
                );
                $html .= $div_suffix;
            } else {
                $html .= $parent->fetchActionButtons();
            }

            $html .= '</li>';
            $html .= '</ul>';
        }
        return $html;
    }

    protected function displayFooter()
    {
        $this->tracker->displayFooter($this->layout);
    }
}
