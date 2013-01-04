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

function aPostActionFieldFactory($mocked_methods = null) {
    if(!is_array($mocked_methods)) {
        $mocked_methods = array();
    }
    
    return new Test_Transition_PostAction_FieldFactoryBuilder($mocked_methods);
}

class Test_Transition_PostAction_FieldFactoryBuilder {
    
    public function __construct(array $mocked_methods) {
        $mocked_methods = array_merge($mocked_methods, array('getDao', 'getFormElementFactory',));


        $this->factory = TestHelper::getPartialMock('Transition_PostAction_FieldFactory',
                                                    $mocked_methods);
        
        $this->form_element_factory = mock('Tracker_FormElementFactory');
                
        $this->daos = array(
            'field_date'    => mock('Transition_PostAction_Field_DateDao'),
            'field_int'     => mock('Transition_PostAction_Field_IntDao'),
            'field_float'   => mock('Transition_PostAction_Field_FloatDao'),
      );
    }
    
    public function withFormElementFactory(Tracker_FormElementFactory $form_element_factory) {
        $this->form_element_factory = $form_element_factory;
        return $this;
    }
    
    public function withFieldDateDao(Transition_PostAction_Field_DateDao $dao) {
        $this->daos['field_date'] = $dao;
        return $this;
    }
    
    public function withFieldIntDao(Transition_PostAction_Field_IntDao $dao) {
        $this->daos['field_int'] = $dao;
        return $this;
    }
    
    public function withFieldFloatDao(Transition_PostAction_Field_FloatDao $dao) {
        $this->daos['field_float'] = $dao;
        return $this;
    }
    
    public function build() {
        stub($this->factory)->getFormElementFactory()->returns($this->form_element_factory);
        foreach($this->daos as $short_name => $dao) {
            stub($this->factory)->getDao($short_name)->returns($dao);
        }
        return $this->factory;
    }
}
?>