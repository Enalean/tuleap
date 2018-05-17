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
require_once __DIR__.'/../bootstrap.php';

Mock::generate('Tracker_Semantic_Status');
Mock::generate('Tracker_Semantic_StatusFactory');

class MockSemanticStatusFactoryBuilder {
    public function __construct() {
        $this->factory = new MockTracker_Semantic_StatusFactory();
    }
    
    public function withNoFieldForTracker($tracker) {
        $this->withFieldForTracker(null, $tracker);
        return $this;
    }
    
    public function withFieldForTracker($field, $tracker) {
        $this->getSemantic($tracker)->setReturnValue('getField', $field);
        return $this;
    }
    
    public function withOpenValueForTracker($tracker, $value_id) {
        $this->getSemantic($tracker)->setReturnValue('getOpenValues', array($value_id));
        return $this;
    }
    
    public function withClosedValueForTracker($tracker, $value_id) {
        $this->getSemantic($tracker)->setReturnValue('getOpenValues', array());
        return $this;
    }
    
    /**
     * Assumes only one mocked tracker.
     */
    private function getSemantic($tracker) {
        if (! isset($this->semantic_status[$tracker->getId()])) {
            $this->semantic_status[$tracker->getId()] = mock('Tracker_Semantic_Status');
            stub($this->factory)->getByTracker($tracker)->returns($this->semantic_status[$tracker->getId()]);
        }
        
        return $this->semantic_status[$tracker->getId()];
    }
    
    public function build() {
        return $this->factory;
    }
}

function aMockSemanticStatusFactory() { return new MockSemanticStatusFactoryBuilder(); }
?>
