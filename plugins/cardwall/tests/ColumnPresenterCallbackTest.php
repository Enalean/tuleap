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

require_once dirname(__FILE__).'/../include/ColumnPresenterCallback.class.php';

class ColumnPresenterCallbackTest extends TuleapTestCase {
    
    public function itJustClonesTheNodeIfItIsNotAPresenterNode() {
        $callback = new ColumnPresenterCallback();
        $node     = aNode()->withId(4444)->build();
        $result = $callback->apply($node);
        $this->assertIdentical($node, $result);
    }
    
    public function itCreatesAColumnPresenterNode() {
        $callback = new ColumnPresenterCallback();
        
        $node     = aNode()->withId(4444)->build();
        $presenter = mock('Cardwall_CardPresenter');
        $presenter_node     = new Tracker_TreeNode_CardPresenterNode($node, $presenter);
        $result = $callback->apply($presenter_node);
        $this->assertIsA($result, 'Cardwall_ColumnPresenterNode');
    }
}
?>
