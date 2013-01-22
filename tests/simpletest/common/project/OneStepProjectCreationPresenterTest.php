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
require_once 'common/project/OneStepProjectCreationPresenter.class.php';
require_once 'common/include/Response.class.php';

class OneStepProjectCreationPresenter_FieldsTest extends TuleapTestCase {
    protected function aOneStepProjectCreationForm($request_data) {
        return new OneStepProjectCreationPresenter($request_data, aUser()->build(), array(), array(), mock('ProjectManager'), mock('ProjectDao'));
    }

    public function testNewObjectSetsFullName() {
        $full_name = 'my_test proj';

        $request_data = array(
            OneStepProjectCreationPresenter::FULL_NAME => $full_name,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($full_name, $single_step->getFullName());
    }

    public function testNewObjectSetsUnixName() {
        $unix_name = 'fdgd';

        $request_data = array(
            OneStepProjectCreationPresenter::UNIX_NAME => $unix_name,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($unix_name, $single_step->getUnixName());
    }

    public function testNewObjectSetsShortDescription() {
        $description = 'short description';

        $request_data = array(
            OneStepProjectCreationPresenter::SHORT_DESCRIPTION => $description,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($description, $single_step->getShortDescription());
    }

    public function testNewObjectSetsIsPublic() {
        $is_public = true;

        $request_data = array(
            OneStepProjectCreationPresenter::IS_PUBLIC => $is_public,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($is_public, $single_step->isPublic());
    }

    public function testNewObjectSetsTemplateId() {
        $id = 5689;

        $request_data = array(
            OneStepProjectCreationPresenter::TEMPLATE_ID => $id,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($id, $single_step->getTemplateId());
    }

    public function itSetsDefaultTemplateIdIfRequestDataDontHaveOne() {
        $request_data          = array();
        $single_step = $this->aOneStepProjectCreationForm($request_data);

        $this->assertEqual(OneStepProjectCreationPresenter::DEFAULT_TEMPLATE_ID , $single_step->getTemplateId());
    }

    public function testNewObjectSetsLicenseType() {
        $type = 'artistic';

        $request_data = array(
            OneStepProjectCreationPresenter::LICENSE_TYPE => $type,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($type, $single_step->getLicenseType());
    }

    public function testNewObjectSetsCustomLicense() {
        $license = 'do not copy';

        $request_data = array(
            OneStepProjectCreationPresenter::CUSTOM_LICENSE => $license,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($license, $single_step->getCustomLicense());
    }

    public function testNewObjectSetsProjectApprobation() {
        $tos = 'approved';

        $request_data = array(
            OneStepProjectCreationPresenter::TOS_APPROVAL => $tos,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertTrue($single_step->getTosApproval());
    }

    public function testNewObjectSetsACustomTextDescriptionField() {
        $text_content = 'bla bla bla';
        $custom_id    = 101;

        $request_data = array(
            OneStepProjectCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id" => $text_content,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($text_content, $single_step->getCustomProjectDescription($custom_id));
    }

    public function itDoesNotSetACustomTextDescriptionFieldIfIdIsNotNumeric() {
        $text_content = 'bla bla bla';
        $custom_id    = 'name';

        $request_data = array(
            OneStepProjectCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id" => $text_content,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertNull($single_step->getCustomProjectDescription($custom_id));
    }

    public function testGetProjectValuesContainsCustomTextDescriptionField() {
        $text_content = 'bla bla bla';
        $custom_id    = 101;

        $request_data = array(
            OneStepProjectCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id" => $text_content,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);

        $project_values = $single_step->getProjectValues();
        $this->assertEqual($project_values['project'][OneStepProjectCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id"], $text_content);
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
            OneStepProjectCreationPresenter::FULL_NAME => $full_name,
            OneStepProjectCreationPresenter::UNIX_NAME => $unix_name,
            OneStepProjectCreationPresenter::SHORT_DESCRIPTION => $description,
            OneStepProjectCreationPresenter::IS_PUBLIC => $is_public,
            OneStepProjectCreationPresenter::TEMPLATE_ID => $id,
            OneStepProjectCreationPresenter::LICENSE_TYPE => $type,
            OneStepProjectCreationPresenter::CUSTOM_LICENSE => $license,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);

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
            OneStepProjectCreationPresenter::FULL_NAME => $full_name,
            OneStepProjectCreationPresenter::UNIX_NAME => $unix_name,
            OneStepProjectCreationPresenter::SHORT_DESCRIPTION => $description,
            OneStepProjectCreationPresenter::IS_PUBLIC => $is_public,
            OneStepProjectCreationPresenter::TEMPLATE_ID => $id,
            OneStepProjectCreationPresenter::LICENSE_TYPE => $type,
            OneStepProjectCreationPresenter::CUSTOM_LICENSE => $license,
        );



        $expected = array(
            'project' => $request_data
        );

        $expected['project']['is_test'] = false;
        $expected['project'][OneStepProjectCreationPresenter::CUSTOM_LICENSE] = null;

        $single_step = $this->aOneStepProjectCreationForm($request_data);

        $this->assertEqual($expected, $single_step->getProjectValues());
    }

}

class OneStepProjectCreationFormValidationTest extends TuleapTestCase {

    private $template_id = 101;

    public function setUp() {
        parent::setUp();

        $template = stub('Project')->isTemplate()->returns(true);

        $user_manager = mock('UserManager');
        UserManager::setInstance($user_manager);

        $project_manager = mock('ProjectManager');
        ProjectManager::setInstance($project_manager);
        stub($project_manager)->getProject($this->template_id)->returns($template);

        $system_event_manager = mock('SystemEventManager');
        SystemEventManager::setInstance($system_event_manager);
        stub($system_event_manager)->isUserNameAvailable()->returns(true);
        stub($system_event_manager)->isProjectNameAvailable()->returns(true);

        $GLOBALS['Response'] = mock('Response');
    }

    public function tearDown() {
        UserManager::clearInstance();
        ProjectManager::clearInstance();
        SystemEventManager::clearInstance();
        parent::tearDown();
    }

    protected function aOneStepProjectCreationForm($request_data, $required_custom_descriptions) {
        $single_step = partial_mock('OneStepProjectCreationPresenter' , array('getTemplateId'), array($request_data, aUser()->build(), array(), $required_custom_descriptions, mock('ProjectManager'), mock('ProjectDao')));

        return $single_step;
    }

    public function testValidateAndGenerateErrorsValidatesFullname() {
        $request_data = array();
        $single_step = $this->aOneStepProjectCreationForm($request_data, array());
        stub($single_step)->getTemplateId()->returns(null);

        $single_step->validateAndGenerateErrors();
    }

    public function itReturnsFalseIfARequiredCustomDescriptionIsNotSet() {
        $required_custom_descriptions = array(
            101 => new ProjectCustomDescription(101, "A REQUIRED description field", "desc", ProjectCustomDescription::REQUIRED, ProjectCustomDescription::TYPE_TEXT, 1),
        );
        $full_name = 'my_test proj';
        $unix_name = 'fdgd';
        $description = 'short description';
        $is_public = true;
        $id = 5689;
        $type = 'other';
        $license = 'do not copy';
        $request_data = array(
            OneStepProjectCreationPresenter::FULL_NAME => $full_name,
            OneStepProjectCreationPresenter::UNIX_NAME => $unix_name,
            OneStepProjectCreationPresenter::SHORT_DESCRIPTION => $description,
            OneStepProjectCreationPresenter::IS_PUBLIC => $is_public,
            OneStepProjectCreationPresenter::TEMPLATE_ID => $id,
            OneStepProjectCreationPresenter::LICENSE_TYPE => $type,
            OneStepProjectCreationPresenter::CUSTOM_LICENSE => $license,
            OneStepProjectCreationPresenter::TOS_APPROVAL => 'approved',
        );
        $single_step = $this->aOneStepProjectCreationForm($request_data, $required_custom_descriptions);
        stub($single_step)->getTemplateId()->returns($this->template_id);

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Language'])->getText('register_projectname', 'custom_description_missing', "A REQUIRED description field")->once();
        $this->assertFalse($single_step->validateAndGenerateErrors());
    }
}

?>
