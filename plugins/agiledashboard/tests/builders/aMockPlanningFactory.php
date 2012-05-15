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

require_once(dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php');

function aMockPlanningFactory() {
    return new Test_MockPlanningFactory_Builder();
}

class Test_MockPlanningFactory_Builder {
    public function __construct() {
        $this->factory = mock('PlanningFactory');
    }
    
    public function withPlanning(Planning $planning) {
        stub($this->factory)->getPlanningWithTrackers($planning->getId())->returns($planning);
        return $this;
    }
    
    public function build() {
        return $this->factory;
    }
}
?>
