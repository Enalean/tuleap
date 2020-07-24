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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\DefaultProjectVisibilityRetriever;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class OneStepProjectCreationPresenter_FieldsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function aOneStepProjectCreationForm($request_data): Project_OneStepCreation_OneStepCreationPresenter
    {
        $project_manager  = \Mockery::spy(\ProjectManager::class);
        $request          = new Codendi_Request($request_data);
        $creation_request = new Project_OneStepCreation_OneStepCreationRequest(
            $request,
            new DefaultProjectVisibilityRetriever()
        );

        return new Project_OneStepCreation_OneStepCreationPresenter(
            $creation_request,
            [],
            $project_manager,
            [],
            '',
            true
        );
    }

    public function testNewObjectSetsFullName(): void
    {
        $full_name = 'my_test proj';

        $request_data = [
            Project_OneStepCreation_OneStepCreationPresenter::FULL_NAME => $full_name,
        ];

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEquals($full_name, $single_step->getFullName());
    }

    public function testNewObjectSetsUnixName(): void
    {
        $unix_name = 'fdgd';

        $request_data = [
            Project_OneStepCreation_OneStepCreationPresenter::UNIX_NAME => $unix_name,
        ];

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEquals($unix_name, $single_step->getUnixName());
    }

    public function testNewObjectSetsShortDescription(): void
    {
        $description = 'short description';

        $request_data = [
            Project_OneStepCreation_OneStepCreationPresenter::SHORT_DESCRIPTION => $description,
        ];

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEquals($description, $single_step->getShortDescription());
    }

    public function testNewObjectSetsIsPublic(): void
    {
        $is_public = true;

        $request_data = [
            Project_OneStepCreation_OneStepCreationPresenter::IS_PUBLIC => $is_public,
        ];

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEquals($is_public, $single_step->isPublic());
    }

    public function testNewObjectSetsTemplateId(): void
    {
        $id = 5689;

        $request_data = [
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $id,
        ];

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertEquals($id, $single_step->getTemplateId());
    }

    public function testItSetsDefaultTemplateIdIfRequestDataDontHaveOne(): void
    {
        $request_data          = [];
        $single_step = $this->aOneStepProjectCreationForm($request_data);

        $this->assertEquals(Project_OneStepCreation_OneStepCreationPresenter::DEFAULT_TEMPLATE_ID, $single_step->getTemplateId());
    }

    public function testNewObjectSetsProjectApprobation(): void
    {
        $tos = 'approved';

        $request_data = [
            Project_OneStepCreation_OneStepCreationPresenter::TOS_APPROVAL => $tos,
        ];

        $single_step = $this->aOneStepProjectCreationForm($request_data);
        $this->assertTrue($single_step->getTosApproval());
    }
}
