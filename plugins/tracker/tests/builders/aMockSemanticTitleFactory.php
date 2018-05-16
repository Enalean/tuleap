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

Mock::generate('Tracker_Semantic_Title');
Mock::generate('Tracker_Semantic_TitleFactory');

class MockSemanticTitleFactoryBuilder {
    public function __construct() {
        $this->factory = new MockTracker_Semantic_TitleFactory();
    }
    
    public function withFieldForTracker($field, $tracker) {
        $semantic = mock('Tracker_Semantic_Title');
        stub($semantic)->getField()->returns($field);
        stub($this->factory)->getByTracker($tracker)->returns($semantic);
        return $this;
    }
    
    public function withNoFieldForTracker($tracker) {
        $this->withFieldForTracker(null, $tracker);
        return $this;
    }
    
    public function build() {
        return $this->factory;
    }
}

function aMockSemanticTitleFactory() { return new MockSemanticTitleFactoryBuilder(); }
?>
