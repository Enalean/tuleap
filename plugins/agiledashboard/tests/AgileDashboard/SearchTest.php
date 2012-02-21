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

require_once dirname(__FILE__) .'/../../include/AgileDashboard/Search.class.php';
Mock::generate('AgileDashboard_SharedFieldFactory');
Mock::generate('AgileDashboard_SearchDao');

class AgileDashboard_SearchTest extends UnitTestCase {
    
    function testGetMatchingArtifactsDelegatesToSharedFieldFactoryAndSearchDao() {
        $criteria  = array('220' => array('values' => array('350')));
        
        $artifacts = new MockDataAccessResult();
        
        $sharedFieldFactory = new MockAgileDashboard_SharedFieldFactory();
        $sharedFieldFactory->expectOnce('getSharedFields', array($criteria));
        $sharedFieldFactory->setReturnValue('getSharedFields', $artifacts);
        
        $searchDao = new MockAgileDashboard_SearchDao();
        $searchDao->expectOnce('searchMatchingArtifacts', array($artifacts));
        
        $search = new AgileDashboard_Search($sharedFieldFactory, $searchDao);
        $search->getMatchingArtifacts($criteria);
    }
}
?>
