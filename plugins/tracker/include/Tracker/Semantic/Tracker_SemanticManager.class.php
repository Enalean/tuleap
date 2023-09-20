<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\Tracker\Semantic\CollectionOfSemanticsUsingAParticularTrackerField;
use Tuleap\Tracker\Semantic\Progress\MethodBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgress;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressBuilder;
use Tuleap\Tracker\Semantic\Progress\SemanticProgressDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

class Tracker_SemanticManager
{
    /**
     * Fetch the semantics used by other plugins
     *
     * Parameters:
     * 'semantics' => @var Tracker_SemanticCollection A collection of semantics that needs adding to.
     * 'tracker'   => @var Tracker                    The Tracker the semantics are defined upon
     *
     * Expected results
     * The semantics parameter is populated with additional semantic fields
     */
    public final const TRACKER_EVENT_MANAGE_SEMANTICS = 'tracker_event_manage_semantics';

    /**
     * Fetches all the semantic names
     *
     * Parameters:
     * 'semantic' => @var array of semantic name strings
     */
    public final const TRACKER_EVENT_GET_SEMANTICS_NAMES = 'tracker_event_get_semantics_names';

    /** @var Tracker */
    protected $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $tracker_manager, $request, $current_user)
    {
        if ($request->existAndNonEmpty('semantic')) {
            $semantics = $this->getSemantics();
            if (isset($semantics[$request->get('semantic')])) {
                $semantics[$request->get('semantic')]->process($this, $tracker_manager, $request, $current_user);
            }
        }
        $this->displayAdminSemantic($tracker_manager, $request, $current_user);
    }

    public function displayAdminSemantic(TrackerManager $tracker_manager, $request, $current_user)
    {
        $assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../frontend-assets', '/assets/trackers');
        $layout = $GLOBALS['HTML'];
        assert($layout instanceof \Tuleap\Layout\BaseLayout);
        $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($assets, 'tracker-admin-semantics.js'));

        $title = dgettext('tuleap-tracker', 'Manage Semantic');
        $this->tracker->displayWarningArtifactByEmailSemantic();
        $this->tracker->displayAdminItemHeaderBurningParrot($tracker_manager, 'editsemantic', $title);

        echo '<div class="tlp-framed">';
        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
        echo '<p>';
        echo dgettext('tuleap-tracker', 'As trackers can be fully customized, you may want to define what is the title of your artifacts, or when you consider an artifact to be open or close. This information is used in the application to display artifact summary, and tooltip for instance.');
        echo '</p>';
        echo '<div class="tracker-admin-semantics">';

        $purifier = Codendi_HTMLPurifier::instance();
        echo '<nav class="tlp-tabs tlp-tabs-vertical tracker-admin-semantics-toc">';
        foreach ($this->getSemantics() as $semantic) {
            if ($semantic === false) {
                continue;
            }
            echo '<a href="#' . $purifier->purify('tracker-admin-semantic-' . $semantic->getShortName()) . '" class="tlp-tab">
                ' . $purifier->purify($semantic->getLabel()) . '
            </a>';
        }
        echo '</nav>';

        echo '<div class="tracker-admin-semantics-list">';

        foreach ($this->getSemantics() as $semantic) {
            if ($semantic === false) {
                continue;
            }
            $url = TRACKER_BASE_URL . '/?' . http_build_query([
                'tracker'  => $this->tracker->getId(),
                'func'     => 'admin-semantic',
                'semantic' => $semantic->getShortName(),
            ]);

            $translated_button = dgettext('tuleap-tracker', 'Configure semantic');
            echo '<section class="tlp-pane tracker-admin-semantic" id="' . $purifier->purify('tracker-admin-semantic-' . $semantic->getShortName()) . '">
                    <div class="tlp-pane-container">
                        <div class="tlp-pane-header">
                            <h1 class="tlp-pane-title">' . $purifier->purify($semantic->getLabel()) . '</h1>
                        </div>
                        <section class="tlp-pane-section">' . $semantic->fetchForSemanticsHomepage() . '</section>
                         <section class="tlp-pane-section tlp-pane-section-submit tracker-admin-semantics-edit-button-section">
                            <a href="' . $url . '" class="tlp-button-primary tlp-button-outline">
                                <i class="fas fa-pencil-alt tlp-button-icon" aria-hidden="true"></i>' . $translated_button
                            . '</a>
                        </section>
                    </div>
                </section>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';

        $this->tracker->displayFooter($tracker_manager);
    }

    public function displaySemanticHeader(Tracker_Semantic $semantic, TrackerManager $tracker_manager)
    {
        $title = $semantic->getLabel();
        $this->tracker->displayAdminItemHeader(
            $tracker_manager,
            'editsemantic',
            $title
        );

        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
    }

    public function displaySemanticFooter(Tracker_Semantic $semantic, TrackerManager $tracker_manager)
    {
        $this->tracker->displayFooter($tracker_manager);
        die();
    }

    public function getSemanticsTheFieldBelongsTo(Tracker_FormElement_Field $field): CollectionOfSemanticsUsingAParticularTrackerField
    {
        $semantics             = $this->getSemantics();
        $timeframe_dao         = new SemanticTimeframeDao();
        $semantics_using_field = [];

        $configs                    = $timeframe_dao->getSemanticsImpliedFromGivenTracker($this->tracker->getId()) ?: [];
        $semantic_timeframe_builder = SemanticTimeframeBuilder::build();

        foreach ($semantics as $semantic) {
            if ($semantic !== false && $semantic->isUsedInSemantics($field)) {
                $semantics_using_field[] = $semantic;
            }
        }

        foreach ($configs as $config) {
            $tracker = \TrackerFactory::instance()->getTrackerById($config['tracker_id']);
            if ($tracker === null) {
                continue;
            }

            $semantic_timeframe = $semantic_timeframe_builder->getSemantic($tracker);

            if ($semantic_timeframe->isUsedInSemantics($field)) {
                $semantics_using_field[] = $semantic_timeframe;
            }
        }

        return new CollectionOfSemanticsUsingAParticularTrackerField($field, $semantics_using_field);
    }

    /**
     * @return Tracker_SemanticCollection
     */
    public function getSemantics()
    {
        $semantics = new Tracker_SemanticCollection();

        $semantics->add(Tracker_Semantic_Title::load($this->tracker));
        $semantics->add(Tracker_Semantic_Description::load($this->tracker));
        $semantics->add(Tracker_Semantic_Status::load($this->tracker));
        $semantics->insertAfter(
            Tracker_Semantic_Status::NAME,
            SemanticDone::load($this->tracker)
        );
        $semantics->add(Tracker_Semantic_Contributor::load($this->tracker));

        $semantic_timeframe    = SemanticTimeframeBuilder::build()->getSemantic($this->tracker);
        $semantic_progress_dao = new SemanticProgressDao();
        $semantic_progress     = (new SemanticProgressBuilder(
            $semantic_progress_dao,
            new MethodBuilder(
                \Tracker_FormElementFactory::instance(),
                $semantic_progress_dao,
                new TypePresenterFactory(
                    new TypeDao(),
                    new ArtifactLinksUsageDao()
                )
            )
        ))->getSemantic($this->tracker);

        $semantics->add($semantic_timeframe);
        $semantics->add($semantic_progress);
        $semantics->add($this->tracker->getTooltip());

        $this->addOtherSemantics($semantics);

        return $semantics;
    }

    /**
     * Use an event to get semantics from other plugins.
     *
     */
    private function addOtherSemantics(Tracker_SemanticCollection $semantics)
    {
         EventManager::instance()->processEvent(
             self::TRACKER_EVENT_MANAGE_SEMANTICS,
             [
                 'semantics'   => $semantics,
                 'tracker'     => $this->tracker,
             ]
         );
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root     the node to which the tooltip is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $semantics = $this->getSemantics();
        foreach ($semantics as $semantic) {
            if ($semantic !== false) {
                $semantic->exportToXML($root, $xmlMapping);
            }
        }
    }

    public function exportToREST(PFUser $user)
    {
        $results        = [];
        $semantic_order = $this->getSemanticOrder();
        $semantics      = $this->getSemantics();

        foreach ($semantic_order as $semantic_key) {
            if (isset($semantics[$semantic_key])) {
                $results[$semantic_key] = $semantics[$semantic_key]->exportToREST($user);
            }
        }

        return array_filter($results);
    }

    protected function getSemanticOrder()
    {
        $order = ['title', 'description', 'status', 'contributor', SemanticTimeframe::NAME, SemanticProgress::NAME];
        EventManager::instance()->processEvent(
            self::TRACKER_EVENT_GET_SEMANTICS_NAMES,
            [
                'semantics' => &$order,
            ]
        );

        return $order;
    }
}
