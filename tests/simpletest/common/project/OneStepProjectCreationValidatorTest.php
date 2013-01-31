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
require_once 'common/project/OneStepCreation/OneStepCreationValidator.class.php';

class OneStepCreationValidatorTest extends TuleapTestCase {

    private $template_id = 100;

    public function setUp() {
        parent::setUp();
        $GLOBALS['ftp_frs_dir_prefix']  = 'whatever';
        $GLOBALS['ftp_anon_dir_prefix'] = 'whatever';
        $GLOBALS['svn_prefix']          = 'whatever';
        $GLOBALS['cvs_prefix']          = 'whatever';
        $GLOBALS['grpdir_prefix']       = 'whatever';

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
    }

    public function tearDown() {
        UserManager::clearInstance();
        ProjectManager::clearInstance();
        SystemEventManager::clearInstance();
        parent::tearDown();
    }

    protected function aCreationValidator($request_data, $required_custom_descriptions) {
        $request = aRequest()->withParams($request_data)->build();
        $creation_request = new Project_OneStepCreation_OneStepCreationRequest($request, ProjectManager::instance());
        $validator = new Project_OneStepCreation_OneStepCreationValidator($creation_request, $required_custom_descriptions);

        return $validator;
    }

    public function testValidateAndGenerateErrorsValidatesFullname() {
        $request_data = array();
        $validator = $this->aCreationValidator($request_data, array());

        $validator->validateAndGenerateErrors();
    }

    public function itReturnsFalseIfARequiredCustomDescriptionIsNotSet() {
        $required_custom_descriptions = array(
            101 => new Project_CustomDescription_CustomDescription(101, "A REQUIRED description field", "desc", Project_CustomDescription_CustomDescription::REQUIRED, Project_CustomDescription_CustomDescription::TYPE_TEXT, 1),
        );
        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME => 'my_test proj',
            Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME => 'fdgd',
            Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION => 'short description',
            Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC => true,
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $this->template_id,
            Project_OneStepCreation_OneStepCreationPresenter::LICENSE_TYPE => 'other',
            Project_OneStepCreation_OneStepCreationPresenter::CUSTOM_LICENSE => 'do not copy',
            Project_OneStepCreation_OneStepCreationPresenter::TOS_APPROVAL => 'approved',
        );
        $validator = $this->aCreationValidator($request_data, $required_custom_descriptions);

        expect($GLOBALS['Response'])->addFeedback('error', '*')->once();
        expect($GLOBALS['Language'])->getText('register_project_one_step', 'custom_description_missing', "A REQUIRED description field")->once();
        $this->assertFalse($validator->validateAndGenerateErrors());
    }
}

?>
