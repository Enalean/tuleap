<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once(dirname(__FILE__) . '/../include/constants.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_SemanticManager.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_Semantic_Title.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_Semantic_Status.class.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Semantic/Tracker_Semantic_Contributor.class.php');
require_once(dirname(__FILE__) . '/builders/aMockTracker.php');
require_once(dirname(__FILE__) . '/../include/Tracker/Tooltip/Tracker_Tooltip.class.php');

class Tracker_SemanticManagerTest extends TuleapTestCase {
    
    public function setUp() {
        $this->tracker_semantic_title       = mock('Tracker_Semantic_Title');
        $this->tracker_semantic_status      = mock('Tracker_Semantic_Status');
        $this->tracker_semantic_contributor = mock('Tracker_Semantic_Contributor');
        $this->tracker_tooltip              = mock('Tracker_Tooltip');
        
        $this->tracker_semantic_title->setReturnValue('getShortName', 'title');
        $this->tracker_semantic_status->setReturnValue('getShortName', 'status');
        $this->tracker_semantic_contributor->setReturnValue('getShortName', 'contributor');
        $this->tracker_tooltip->setReturnValue('getShortName', 'tooltip');
        
        $this->tracker_semanticManager = partial_mock('Tracker_SemanticManager', array('getSemantics'));
        $semantics_return = array(
            $this->tracker_semantic_title->getShortName()       => $this->tracker_semantic_title,
            $this->tracker_semantic_status->getShortName()      => $this->tracker_semantic_status,
            $this->tracker_semantic_contributor->getShortName() => $this->tracker_semantic_contributor,
            $this->tracker_tooltip->getShortName()              => $this->tracker_tooltip
        );
        
        $this->tracker_semanticManager->setReturnValue('getSemantics', $semantics_return);
    }
    
    public function itReturnsAnEmptySOAPArray() {
        $title_field_name       = '';
        $status_field_name      = '';
        $contributor_field_name = '';
        
        $this->tracker_semantic_title->setReturnValue('exportToSOAP', array(
             $this->tracker_semantic_title->getShortName() => array('field_name' => $title_field_name)
        ));
        $this->tracker_semantic_status->setReturnValue('exportToSOAP', array(
             $this->tracker_semantic_status->getShortName() => array(
                 'field_name' => $status_field_name,
                 'values'     => array()
             )
        ));
        $this->tracker_semantic_contributor->setReturnValue('exportToSOAP', array(
             $this->tracker_semantic_contributor->getShortName() => array('field_name' => $contributor_field_name)
        ));
        $this->tracker_tooltip->setReturnValue('exportToSOAP', array(
             $this->tracker_tooltip->getShortName() => null
        ));
        
        $expected = array(
            $this->tracker_semantic_title->getShortName()       => array('field_name' => $title_field_name),
            $this->tracker_semantic_status->getShortName()      => array('field_name' => $status_field_name, 'values' => array()),
            $this->tracker_semantic_contributor->getShortName() => array('field_name' => $contributor_field_name)
        );

        $result = $this->tracker_semanticManager->exportToSOAP();
        $this->assertEqual($result, $expected);
    }
    
    public function itReturnsTheSemanticInSOAPFormat() {
        $title_field_name       = 'some_title';
        $status_field_name      = 'some_status';
        $contributor_field_name = 'some_contributor';
        
        $this->tracker_semantic_title->setReturnValue('exportToSOAP', array(
            $this->tracker_semantic_title->getShortName() => array('field_name' => $title_field_name)
        ));
        $this->tracker_semantic_status->setReturnValue('exportToSOAP', array(
            $this->tracker_semantic_status->getShortName() => array(
                'field_name' => $status_field_name,
                'values' => array(1,2,3)
            )
        ));
        $this->tracker_semantic_contributor->setReturnValue('exportToSOAP', array(
            $this->tracker_semantic_contributor->getShortName() => array('field_name' => $contributor_field_name)
        ));
        $this->tracker_tooltip->setReturnValue('exportToSOAP', array(
            $this->tracker_tooltip->getShortName() => null
        ));

        $expected = array(
            $this->tracker_semantic_title->getShortName()       => array('field_name' => $title_field_name),
            $this->tracker_semantic_status->getShortName()      => array('field_name' => $status_field_name, 'values' => array(1,2,3)),
            $this->tracker_semantic_contributor->getShortName() => array('field_name' => $contributor_field_name)
        );

        $result = $this->tracker_semanticManager->exportToSOAP();
        $this->assertEqual($result, $expected);
    }

}

?>