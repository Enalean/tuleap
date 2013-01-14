<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('Tracker_Semantic_Title.class.php');
require_once('Tracker_Semantic_Status.class.php');
require_once('Tracker_Semantic_Contributor.class.php');

class Tracker_SemanticManager {

    protected $tracker;

    public function __construct($tracker) {
        $this->tracker = $tracker;
    }

    public function process(TrackerManager $tracker_manager, $request, $current_user) {
        if ($request->existAndNonEmpty('semantic')) {
            $semantics = $this->getSemantics();
            if (isset($semantics[$request->get('semantic')])) {
                $semantics[$request->get('semantic')]->process($this, $tracker_manager, $request, $current_user);
            }
        }
        $this->displayAdminSemantic($tracker_manager, $request, $current_user);
    }

    public function displayAdminSemantic(TrackerManager $tracker_manager, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->tracker->displayAdminItemHeader($tracker_manager, 'editsemantic');

        echo '<p>';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_semantic','semantic_intro');
        echo '</p>';

        foreach($this->getSemantics() as $key => $s) {
            echo '<h3>'. $s->getLabel() .' <a href="'.TRACKER_BASE_URL.'/?'. http_build_query(array(
                'tracker'  => $this->tracker->getId(),
                'func'     => 'admin-semantic',
                'semantic' => $key,
            )) .'">';
            echo $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit'));
            echo '</a></h3>';
            $s->display();
        }

        $this->tracker->displayFooter($tracker_manager);
    }

    public function displaySemanticHeader(Tracker_Semantic $semantic, TrackerManager $tracker_manager) {
        $this->tracker->displayAdminItemHeader(
            $tracker_manager,
            'editsemantic',
            array(
                array(
                    'url'         => TRACKER_BASE_URL.'/?'. http_build_query(array(
                        'tracker'  => $this->tracker->getId(),
                        'func'     => 'admin-semantic',
                        'semantic' => $semantic->getShortName(),
                    )),
                    'title'       => $semantic->getLabel(),
                    'description' => $semantic->getDescription(),
                )
            ),
            $semantic->getLabel()
        );
    }

    public function displaySemanticFooter(Tracker_Semantic $semantic, TrackerManager $tracker_manager) {
        $this->tracker->displayFooter($tracker_manager);
        die();
    }

    /**
     * Is the field used in semantics?
     *
     * @param Tracker_FormElement_Field the field to test if it is used in semantics or not
     *
     * @return boolean returns true if the field is used in semantics, false otherwise
     */
    public function isUsedInSemantics($field) {
        $semantics = $this->getSemantics();
        foreach ($semantics as $semantic) {
            if ($semantic->isUsedInSemantics($field)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return array of Tracker_Semantic
     */
    public function getSemantics() {
        $semantics = array();

        $t = Tracker_Semantic_Title::load($this->tracker);
        $semantics[$t->getShortName()] = $t;

        $t = Tracker_Semantic_Status::load($this->tracker);
        $semantics[$t->getShortName()] = $t;

        $t = Tracker_Semantic_Contributor::load($this->tracker);
        $semantics[$t->getShortName()] = $t;

        $t = $this->tracker->getTooltip();
        $semantics[$t->getShortName()] = $t;

        return $semantics;
    }

    /**
     * Export semantic to XML
     *
     * @param SimpleXMLElement &$root     the node to which the tooltip is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping) {
        $semantics = $this->getSemantics();
        foreach ($semantics as $semantic) {
            $semantic->exportToXML($root, $xmlMapping);
        }
    }

    /**
     * Export the semantic to SOAP format
     * @return array the SOAPification of the semantic
     */
    public function exportToSOAP() {
        $semantic_order = array('title', 'status', 'contributor');
        $semantics      = $this->getSemantics();
        $soap_result    = array();

        foreach ($semantic_order as $semantic_key) {
            if (isset($semantics[$semantic_key])){
                $soap_result[$semantic_key] = $semantics[$semantic_key]->exportToSOAP();
            }
        }

        return $soap_result;
    }
}
?>