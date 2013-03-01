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

// This is an on going work to help developers to build more expressive tests
// please add the functions/methods below when needed.
// For further information about the Test Data Builder pattern
// @see http://nat.truemesh.com/archives/000727.html

require_once dirname(__FILE__).'/../../include/Planning/PlanningFactory.class.php';
require_once dirname(__FILE__).'/../../include/Planning/PlanningDao.class.php';
require_once dirname(__FILE__) .'/../../../tracker/include/Tracker/TrackerFactory.class.php';
require_once('common/user/User.class.php');

Mock::generate('PlanningDao');
Mock::generate('TrackerFactory');
Mock::generate('PFUser');

function aPlanningFactory() {
    return new TestPlanningFactoryBuilder();
}

class TestPlanningFactoryBuilder {
    
    public $dao;
    public $tracker_factory;
    
    public function __construct() {
        $this->dao             = new MockPlanningDao();
        $this->tracker_factory = new MockTrackerFactory();
    }
    
    public function withDao(DataAccessObject $dao) {
        $this->dao = $dao;
        return $this;
    }
    
    public function withTrackerFactory(TrackerFactory $tracker_factory) {
        $this->tracker_factory = $tracker_factory;
        return $this;
    }
    
    public function build() {
        return new PlanningFactory($this->dao, $this->tracker_factory);
    }
}

?>
