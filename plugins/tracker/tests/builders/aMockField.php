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

class MockFieldBuilder {
    public function __construct() {
        $this->field = mock('Tracker_FormElement_Field_SelectBox');
    }
    
    public function withId($id) {
        stub($this->field)->getId()->returns($id);
        return $this;
    }
    
    public function withTracker(Tracker $tracker) {
        stub($this->field)->getTracker()->returns($tracker);
        return $this;
    }
    
    public function withValueForChangesetId($value_id, $changeset_id) {
        $bind = mock('Tracker_FormElement_Field_List_Bind_Static');
        
        stub($this->field)->getBind()->returns($bind);
        stub($bind)->getChangesetValues($changeset_id)->returns(array(array('id' => $value_id)));
        
        return $this;
    }
    
    public function build() {
        return $this->field;
    }
}

function aMockField() {
    return new MockFieldBuilder();
}
?>
