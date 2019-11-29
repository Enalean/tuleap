<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

use Tuleap\Project\DefaultProjectVisibilityRetriever;

class OneStepProjectCreationPresenter_FieldsTest extends TuleapTestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    protected function aOneStepProjectCreationForm($request_data)
    {
        $project_manager  = mock('ProjectManager');
        $request          = aRequest()->withParams($request_data)->build();
        $creation_request = new Project_OneStepCreation_OneStepCreationRequest(
            $request,
            new DefaultProjectVisibilityRetriever()
        );

        return new Project_OneStepCreation_OneStepCreationPresenter(
            $creation_request,
            array(),
            $project_manager,
            array(),
            '',
            true
        );
    }

    public function testNewObjectSetsFullName()
    {
        $full_name = 'my_test proj';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME => $full_name,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($full_name, $single_step->getFullName());
    }

    public function testNewObjectSetsUnixName()
    {
        $unix_name = 'fdgd';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME => $unix_name,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($unix_name, $single_step->getUnixName());
    }

    public function testNewObjectSetsShortDescription()
    {
        $description = 'short description';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION => $description,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($description, $single_step->getShortDescription());
    }

    public function testNewObjectSetsIsPublic()
    {
        $is_public = true;

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC => $is_public,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($is_public, $single_step->isPublic());
    }

    public function testNewObjectSetsTemplateId()
    {
        $id = 5689;

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $id,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEqual($id, $single_step->getTemplateId());
    }

    public function itSetsDefaultTemplateIdIfRequestDataDontHaveOne()
    {
        $request_data          = array();
        $single_step = $this->aOneStepProjectCreationForm($request_data);

        $this->assertEqual(Project_OneStepCreation_OneStepCreationPresenter::DEFAULT_TEMPLATE_ID, $single_step->getTemplateId());
    }

    public function testNewObjectSetsProjectApprobation()
    {
        $tos = 'approved';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::TOS_APPROVAL => $tos,
        );

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertTrue($single_step->getTosApproval());
    }
}
