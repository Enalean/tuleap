<?php
/**
 * Copyright Enalean (c) 2013 - present. All rights reserved.
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

use Tuleap\Date\RelativeDatesAssetsRetriever;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\CodeBlockFeaturesOnArtifact;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\Renderer\GetAdditionalAssetsForArtifactDisplay;
use Tuleap\Tracker\Artifact\Renderer\ListFieldsIncluder;
use Tuleap\Tracker\Artifact\View\TypeView;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\ParentOfArtifactCollection;
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
     * @var Artifact[]
     */
    private $hierarchy;
    private ?ParentOfArtifactCollection $cached_parent_hierarchy = null;

    public function __construct(
        EventManager $event_manager,
        Artifact $artifact,
        Tracker_IDisplayTrackerLayout $layout,
        TypeIsChildLinkRetriever $retriever,
        VisitRecorder $visit_recorder,
        HiddenFieldsetsDetector $hidden_fieldsets_detector,
    ) {
        parent::__construct($artifact, $event_manager, $visit_recorder);
        $this->layout                    = $layout;
        $this->retriever                 = $retriever;
        $this->hidden_fieldsets_detector = $hidden_fieldsets_detector;
    }

    /**
     * Display the artifact
     *
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
        $html = parent::fetchFormContent($request, $current_user);

        if ($this->artifact->getTracker()->isProjectAllowedToUseType()) {
            $html .= $this->fetchTitleIsGraph();
        } else {
            $html .= $this->fetchTitleInHierarchy($this->hierarchy);
        }

        $html .= $this->fetchView($request, $current_user);
        return $html;
    }

    private function getParentHierarchy(): ParentOfArtifactCollection
    {
        if ($this->cached_parent_hierarchy === null) {
            $this->cached_parent_hierarchy = $this->retriever->getParentsHierarchy($this->artifact);
        }

        return $this->cached_parent_hierarchy;
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
        if (CodeBlockFeaturesOnArtifact::getInstance()->isMermaidNeeded()) {
            $js_asset = new \Tuleap\Layout\JavascriptViteAsset(
                new \Tuleap\Layout\IncludeViteAssets(
                    __DIR__ . '/../../../../../../src/scripts/mermaid-diagram-element/frontend-assets',
                    '/assets/core/mermaid-diagram-element',
                ),
                'src/index.ts',
            );
            $GLOBALS['HTML']->addJavascriptAsset($js_asset);
        }
        $parents = $this->getParentHierarchy();
        if ($parents->isGraph()) {
            $GLOBALS['HTML']->addFeedback(Feedback::WARN, dgettext('tuleap-tracker', 'When more than one parent, we cannot display rest of hierarchy.'));
        }

        $title       = sprintf(
            '%s - %s #%d',
            mb_substr($this->artifact->getTitle() ?? '', 0, 64),
            $this->tracker->getItemName(),
            $this->artifact->getId()
        );
        $breadcrumbs = [
            ['title' => $this->artifact->getXRef(),
                'url'   => TRACKER_BASE_URL . '/?aid=' . $this->artifact->getId(),
            ],
        ];
        $params      = [
            'body_class' => ['widgetable', 'has-sidebar-with-pinned-header', 'tracker-artifact-view-body'],
            'open_graph' => new \Tuleap\OpenGraph\OpenGraphPresenter(
                \Tuleap\ServerHostname::HTTPSUrl() . $this->artifact->getUri(),
                $this->artifact->getTitle(),
                $this->artifact->getDescription()
            ),
        ];

        $GLOBALS['HTML']->includeFooterJavascriptFile(RelativeDatesAssetsRetriever::retrieveAssetsUrl());
        ListFieldsIncluder::includeListFieldsAssets();

        $event = new GetAdditionalAssetsForArtifactDisplay();
        $this->event_manager->dispatch($event);
        foreach ($event->getAssets() as $asset) {
            $GLOBALS['HTML']->addJavascriptAsset($asset);
        }

        $assets = new \Tuleap\Layout\IncludeCoreAssets();
        $GLOBALS['HTML']->addCssAsset(new \Tuleap\Layout\CssAssetWithoutVariantDeclinaisons($assets, 'syntax-highlight'));

        $this->tracker->displayHeader($this->layout, $title, $breadcrumbs, $params);


        $status = new Tracker_ArtifactByEmailStatus(
            new MailGatewayConfig(
                new MailGatewayConfigDao(),
            )
        );
        if ($status->canUpdateArtifactInInsecureMode($this->tracker)) {
            $renderer = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../../templates/artifact');
            $renderer->renderToPage("reply-by-mail-modal-info", [
                'email' => $this->artifact->getInsecureEmailAddress(),
            ]);
        }
    }

    protected function fetchView(Codendi_Request $request, PFUser $user)
    {
        $view_collection = new Tracker_Artifact_View_ViewCollection($this->event_manager);
        $view_collection->add(new Tracker_Artifact_View_Edit($this->artifact, $request, $user, $this));

        if ($this->artifact->getTracker()->isProjectAllowedToUseType()) {
            $artifact_links = $this->retriever->getChildren($this->artifact);
            if (count($artifact_links) > 0) {
                $view_collection->add(new TypeView($this->artifact, $request, $user));
            }
        } else {
            if ($this->artifact->getTracker()->getChildren()) {
                $view_collection->add(new TypeView($this->artifact, $request, $user));
            }
        }

        EventManager::instance()->processEvent(
            self::EVENT_ADD_VIEW_IN_COLLECTION,
            [
                'artifact'   => $this->artifact,
                'collection' => $view_collection,
                'request'    => $request,
                'user'       => $user,
            ]
        );

        return $view_collection->fetchRequestedView($request);
    }

    protected function fetchTitle()
    {
        return $this->artifact->fetchTitle();
    }

    private function fetchTitleIsGraph(): string
    {
        $html  = '';
        $html .= $this->artifact->fetchHiddenTrackerId();
        $html .= $this->fetchMultipleParentsTitle($this->artifact);

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

    private function fetchMultipleParentsTitle(Artifact $artifact): string
    {
        $hierarchy = $this->getParentHierarchy();
        $tab_level = 0;
        $html      = '';
        $html     .= '<ul class="tracker-hierarchy" data-test="tracker-hierarchy">';
        $parents   = array_reverse($hierarchy->getArtifacts());

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
        $html .= '<div class="tracker_artifact_title" data-test="tracker-artifact-title">';
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

    private function fetchShowHideFieldSetsButton(): string
    {
        if (! $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($this->artifact)) {
            return '';
        }

        return '<div class="header-spacer"></div>
            <div class="show-hide-fieldsets">' . dgettext('tuleap-tracker', 'Hidden fieldsets:') . '
                <div class="btn-group" data-toggle="buttons-radio">
                    <button type="button" class="btn show-fieldsets"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" class="btn active hide-fieldsets"><i class="fa-solid fa-eye-slash"></i></button>
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
     * @param Artifact[] $parents
     * @param string     $padding_prefix
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
        if (CodeBlockFeaturesOnArtifact::getInstance()->isSyntaxHighlightNeeded()) {
            $GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset(new \Tuleap\Layout\IncludeCoreAssets(), 'syntax-highlight.js'));
        }

        $this->tracker->displayFooter($this->layout);
    }
}
