<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframe;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeBuilder;
use Tuleap\Tracker\Semantic\Timeframe\SemanticTimeframeDao;

class Tracker_SemanticManager
{
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
        $title = $GLOBALS['Language']->getText('plugin_tracker_admin', 'manage_semantic');
        $this->tracker->displayWarningArtifactByEmailSemantic();
        $this->tracker->displayAdminItemHeader($tracker_manager, 'editsemantic', $title);

        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
        echo '<p>';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic', 'semantic_intro');
        echo '</p>';

        foreach ($this->getSemantics() as $semantic) {
            echo '<h3>' . $semantic->getLabel() . ' <a href="' . TRACKER_BASE_URL . '/?' . http_build_query(array(
                'tracker'  => $this->tracker->getId(),
                'func'     => 'admin-semantic',
                'semantic' => $semantic->getShortName(),
            )) . '">';
            echo $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit'));
            echo '</a></h3>';
            $semantic->display();
        }

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

    /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return bool returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics(Tracker_FormElement_Field $field)
    {
        $semantics = $this->getSemantics();
        foreach ($semantics as $semantic) {
            if ($semantic->isUsedInSemantics($field)) {
                return true;
            }
        }
        return false;
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
        $semantics->add(Tracker_Semantic_Contributor::load($this->tracker));

        $semantic_timeframe_builder = new SemanticTimeframeBuilder(
            new SemanticTimeframeDao(),
            Tracker_FormElementFactory::instance()
        );

        $semantic_timeframe = $semantic_timeframe_builder->getSemantic($this->tracker);

        $semantics->add($semantic_timeframe);
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
             TRACKER_EVENT_MANAGE_SEMANTICS,
             array(
                'semantics'   => $semantics,
                'tracker'     => $this->tracker,
             )
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
            $semantic->exportToXML($root, $xmlMapping);
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
        $order = array('title', 'description', 'status', 'contributor', SemanticTimeframe::NAME);
        EventManager::instance()->processEvent(
            TRACKER_EVENT_GET_SEMANTICS_NAMES,
            array(
                'semantics' => &$order
            )
        );

        return $order;
    }
}
