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

require_once dirname(__FILE__) .'/../include/AgileDashboardSearch.class.php';
require_once dirname(__FILE__) .'/../../tracker/include/Tracker/FormElement/Tracker_FormElement_Field_String.class.php';
Mock::generate('Project');
Mock::generate('Tracker');
Mock::generate('AgileDashboardSearchDao');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_FormElement_Field_String');

class AgileDashboardSearchTest extends UnitTestCase {

    function testGetMatchingIdsWhenCriteria() {
        $dar = array(
            array(
                'id' => 6,
                'title' => 'As a user I want to search on shared fields',
            ),
            array(
                'id' => 8,
                'title' => 'Add the form',
            )
        );
        
        $sharedValueDar = array(
            array(
                'id' => 214,
            ),
            array(
                'id' => 143,
            )
        );
        $dao = new MockAgileDashboardSearchDao();
        $dao->setReturnValue('searchMatchingArtifacts', $dar);
        $dao->expectOnce('searchMatchingArtifacts', array(array(214,143)));
        
        $dao->setReturnValue('searchSharedValueIds', $sharedValueDar);
        $dao->expectOnce('searchSharedValueIds', array(array(214)));
        
        $criteria = array('220' => array('values' => array('214')));
        
        
        $search = TestHelper::getPartialMock('AgileDashboardSearch', array('getDao'));
        $search->setReturnValue('getDao', $dao);
        
        $artifacts = $search->getMatchingArtifacts($criteria);
        $this->assertEqual($artifacts[0]['id'], 6);
        $this->assertEqual($artifacts[1]['title'], 'Add the form');
    }

}
?>
