<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

function aCIBuildPostAction() {
    return new Test_Transition_PostAction_CIBuild_Builder();
}

class Test_Transition_PostAction_CIBuild_Builder {
    
    private $id;
    
    public function __construct() {
        $this->transition = aTransition()->build();
    }
    
    public function withId($id) {
        $this->id = $id;
        return $this;
    }
    
    public function withTransition(Transition $transition) {
        $this->transition = $transition;
        return $this;
    }
    
    public function withTransitionId($transition_id) {
        $this->transition = aTransition()->withId($transition_id)->build();
        return $this;
    }
    
    public function withValue($value) {
        $this->value = $value;
        return $this;
    }

    public function withCIClient($ci_client) {
        $this->ci_client = $ci_client;
        return $this;
    }
    
    public function build() {
        return new Transition_PostAction_CIBuild(
            $this->transition,
            $this->id,
            $this->value,
            $this->ci_client
        );
    }
}

?>
