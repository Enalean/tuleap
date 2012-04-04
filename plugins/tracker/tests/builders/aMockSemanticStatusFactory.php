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

require_once dirname(__FILE__).'/../../include/Tracker/Semantic/Tracker_Semantic_Status.class.php';
require_once dirname(__FILE__).'/../../include/Tracker/Semantic/Tracker_Semantic_StatusFactory.class.php';

Mock::generate('Tracker_Semantic_Status');
Mock::generate('Tracker_Semantic_StatusFactory');

class MockSemanticStatusFactoryBuilder {
    public function __construct() {
        $this->factory = new MockTracker_Semantic_StatusFactory();
    }
    
    public function withNoFieldForTracker($tracker) {
        $semantic_status = new MockTracker_Semantic_Status();
        
        $semantic_status->setReturnValue('getField', null);
        $this->factory->setReturnValue('getByTracker', $semantic_status, array($tracker));
        
        return $this;
    }
    
    public function build() {
        return $this->factory;
    }
}

function aMockSemanticStatusFactory() { return new MockSemanticStatusFactoryBuilder(); }
?>
