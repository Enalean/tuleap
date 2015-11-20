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
require_once 'common/project/OneStepCreation/OneStepCreationRequest.class.php';

class OneStepProjectCreationRequestTest extends TuleapTestCase {

    private $template_id        = 100;
    private $service_git_id     = 11;
    private $service_tracker_id = 12;

    public function setUp() {
        parent::setUp();

        $service_git = mock('Service');
        stub($service_git)->getId()->returns($this->service_git_id);
        stub($service_git)->isUsed()->returns(false);

        $service_tracker = mock('Service');
        stub($service_tracker)->getId()->returns($this->service_tracker_id);
        stub($service_tracker)->isUsed()->returns(true);

        $template = mock('Project');
        stub($template)->getServices()->returns(array($service_git, $service_tracker));

        $this->project_manager = stub('ProjectManager')->getProject($this->template_id)->returns($template);
    }

    protected function aCreationRequest($request_data) {
        $request = aRequest()->withParams($request_data)->build();
        return new Project_OneStepCreation_OneStepCreationRequest($request, $this->project_manager);
    }

    public function testNewObjectSetsACustomTextDescriptionField() {
        $text_content = 'bla bla bla';
        $custom_id    = 101;

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id" => $text_content,
        );

        $creation_request = $this->aCreationRequest($request_data);
        $this->assertEqual($text_content, $creation_request->getCustomProjectDescription($custom_id));
    }

    public function itDoesNotSetACustomTextDescriptionFieldIfIdIsNotNumeric() {
        $text_content = 'bla bla bla';
        $custom_id    = 'name';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id" => $text_content,
        );

        $creation_request = $this->aCreationRequest($request_data);
        $this->assertNull($creation_request->getCustomProjectDescription($custom_id));
    }

    public function testGetProjectValuesContainsCustomTextDescriptionField() {
        $text_content = 'bla bla bla';
        $custom_id    = 101;

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id" => $text_content,
        );

        $creation_request = $this->aCreationRequest($request_data);

        $project_values = $creation_request->getProjectValues();
        $this->assertEqual($project_values['project'][Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX."$custom_id"], $text_content);
    }

    public function itForcesTheProjectToNotBeATest() {
        $request_data     = array('whatever');
        $creation_request = $this->aCreationRequest($request_data);

        $values = $creation_request->getProjectValues();
        $this->assertFalse($values['project']['is_test']);
    }

    public function itIncludesTheUsedServicesOfTheChoosenTemplate() {
        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $this->template_id,
        );
        $creation_request = $this->aCreationRequest($request_data);
        $values = $creation_request->getProjectValues();

        $this->assertEqual($values['project']['services'][$this->service_tracker_id]['is_used'], 1);
        $this->assertEqual($values['project']['services'][$this->service_git_id]['is_used'], 0);
    }
}
?>
