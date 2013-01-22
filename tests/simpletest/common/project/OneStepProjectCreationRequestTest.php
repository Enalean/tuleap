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

    protected function aCreationRequest($request_data) {
        $request = aRequest()->withParams($request_data)->build();
        $creation_request = new Project_OneStepCreation_OneStepCreationRequest($request);
        return new Project_OneStepCreation_OneStepCreationRequest($creation_request);
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

    public function testGetProjectValuesUsesCustomLicenseIfTypeIsOther() {
        $full_name = 'my_test proj';
        $unix_name = 'fdgd';
        $description = 'short description';
        $is_public = true;
        $id = 5689;
        $type = 'other';
        $license = 'do not copy';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME => $full_name,
            Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME => $unix_name,
            Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION => $description,
            Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC => $is_public,
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $id,
            Project_OneStepCreation_OneStepCreationPresenter::LICENSE_TYPE => $type,
            Project_OneStepCreation_OneStepCreationPresenter::CUSTOM_LICENSE => $license,
        );

        $creation_request = $this->aCreationRequest($request_data);

        $expected = array(
            'project' => $request_data
        );

        $expected['project']['is_test'] = false;

        $this->assertEqual($expected, $creation_request->getProjectValues());
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
            Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME => $full_name,
            Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME => $unix_name,
            Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION => $description,
            Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC => $is_public,
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $id,
            Project_OneStepCreation_OneStepCreationPresenter::LICENSE_TYPE => $type,
            Project_OneStepCreation_OneStepCreationPresenter::CUSTOM_LICENSE => $license,
        );

        $expected = array(
            'project' => $request_data
        );

        $expected['project']['is_test'] = false;
        $expected['project'][Project_OneStepCreation_OneStepCreationPresenter::CUSTOM_LICENSE] = null;

        $creation_request = $this->aCreationRequest($request_data);

        $this->assertEqual($expected, $creation_request->getProjectValues());
    }
}
?>
