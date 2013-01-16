<?php

/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */
require_once 'common/project/RegisterProjectOneStep.class.php';
require_once 'common/include/Response.class.php';

class RegisterProjectOneStepTest extends TuleapTestCase {
    
    public function testNewObjectSetsFullName() {
        $full_name = 'my_test proj';
        
        $request_data = array(
            RegisterProjectOneStep::FULL_NAME => $full_name,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($full_name, $single_step->getFullName());
    }
    
    public function testNewObjectSetsUnixName() {
        $unix_name = 'fdgd';
        
        $request_data = array(
            RegisterProjectOneStep::UNIX_NAME => $unix_name,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($unix_name, $single_step->getUnixName());
    }
    
    public function testNewObjectSetsShortDescription() {
        $description = 'short description';
        
        $request_data = array(
            RegisterProjectOneStep::SHORT_DESCRIPTION => $description,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($description, $single_step->getShortDescription());
    }
    
    public function testNewObjectSetsIsPublic() {
        $is_public = true;
        
        $request_data = array(
            RegisterProjectOneStep::IS_PUBLIC => $is_public,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($is_public, $single_step->isPublic());
    }
    
    public function testNewObjectSetsTemplateId() {
        $id = 5689;
        
        $request_data = array(
            RegisterProjectOneStep::TEMPLATE_ID => $id,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($id, $single_step->getTemplateId());
    }
    
    public function testNewObjectSetsLicenseType() {
        $type = 'artistic';
        
        $request_data = array(
            RegisterProjectOneStep::LICENSE_TYPE=> $type,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($type, $single_step->getLicenseType());
    }
    
    public function testNewObjectSetsCustomLicense() {
        $license = 'do not copy';
        
        $request_data = array(
            RegisterProjectOneStep::CUSTOM_LICENSE => $license,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        $this->assertEqual($license, $single_step->getCustomLicense());
    }
    
    public function testGetProjectValuesUsesCustomLicenseIfTypeIsOther() {
        $full_name = 'my_test proj';
        $unix_name = 'fdgd';
        $description = 'short description';
        $is_public = true;
        $id = 5689;
        $type = 'other';
        $license = 'do not copy';
        
        $request_data = array(
            RegisterProjectOneStep::FULL_NAME => $full_name,
            RegisterProjectOneStep::UNIX_NAME => $unix_name,
            RegisterProjectOneStep::SHORT_DESCRIPTION => $description,
            RegisterProjectOneStep::IS_PUBLIC => $is_public,
            RegisterProjectOneStep::TEMPLATE_ID => $id,
            RegisterProjectOneStep::LICENSE_TYPE=> $type,
            RegisterProjectOneStep::CUSTOM_LICENSE => $license,
        );
        
        $single_step = new RegisterProjectOneStep($request_data);
        
        $expected = array(
            'project' => $request_data
        );
        
        $expected['project']['is_test'] = false;
        
        $this->assertEqual($expected, $single_step->getProjectValues());
    }
    
    public function testGetProjectValuesIgnoresCustomLicenseIfTypeIsNotOther() {
        $full_name = 'my_test proj';
        $unix_name = 'fdgd';
        $description = 'short description';
        $is_public = true;
        $id = 5689;
        $type = 'artistic';
        $license = 'do not copy';
        
        $request_data = array(
            RegisterProjectOneStep::FULL_NAME => $full_name,
            RegisterProjectOneStep::UNIX_NAME => $unix_name,
            RegisterProjectOneStep::SHORT_DESCRIPTION => $description,
            RegisterProjectOneStep::IS_PUBLIC => $is_public,
            RegisterProjectOneStep::TEMPLATE_ID => $id,
            RegisterProjectOneStep::LICENSE_TYPE=> $type,
            RegisterProjectOneStep::CUSTOM_LICENSE => $license,
        );
        
        
        
        $expected = array(
            'project' => $request_data
        );
        
        $expected['project']['is_test'] = false;
        $expected['project'][RegisterProjectOneStep::CUSTOM_LICENSE] = null;
        
        $single_step = new RegisterProjectOneStep($request_data);
        
        $this->assertEqual($expected, $single_step->getProjectValues());
    }
    
}

class RegisterProjectOneStepValidationTest extends RegisterProjectOneStepTest {
    
    public function setUp() {
        parent::setUp();
        
        $GLOBALS['Response'] = mock('Response');
    }
    
    public function testValidateAndGenerateErrorsValidatesFullname() {
        $request_data = array();
        $single_step = new RegisterProjectOneStep($request_data);
        
        $single_step->validateAndGenerateErrors();
    }

}





?>
