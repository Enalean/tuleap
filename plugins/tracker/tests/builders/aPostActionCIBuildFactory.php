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

require_once dirname(__FILE__).'/../../include/workflow/PostAction/Transition_PostActionFactory.class.php';
require_once dirname(__FILE__).'/../../include/Tracker/FormElement/Tracker_FormElementFactory.class.php';

function aPostActionCIBuildFactory() {
    return new Test_Transition_PostAction_CIBuildFactoryBuilder();
}

class Test_Transition_PostAction_CIBuildFactoryBuilder {
    
    public function __construct() {
                
    }
    
    public function withCIBuildDao(Transition_PostAction_CIBuildDao $dao) {
        $this->daos['ci_build'] = $dao;
        return $this;
    }

    public function build() {
        $this->factory = partial_mock(
            'Transition_PostAction_CIBuildFactory',
            array('getDao')
        );
        
        stub($this->factory)->getDao()->returns(mock('Transition_PostAction_CIBuildDao'));
        
        return $this->factory;
    }
}
?>