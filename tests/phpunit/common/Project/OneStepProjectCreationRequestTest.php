<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All rights reserved
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class OneStepProjectCreationRequestTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $template_id        = 100;
    private $service_git_id     = 11;
    private $service_tracker_id = 12;

    protected function aCreationRequest($request_data): Project_OneStepCreation_OneStepCreationRequest
    {
        $request = new Codendi_Request($request_data);
        $creation_request = new Project_OneStepCreation_OneStepCreationRequest(
            $request,
            new DefaultProjectVisibilityRetriever()
        );
        $service_git = Mockery::mock(Service::class, ['getId' => $this->service_git_id, 'isUsed' => false]);
        $service_tracker = Mockery::mock(Service::class, ['getId' => $this->service_tracker_id, 'isUsed' => true]);

        $project_used_as_template = Mockery::mock(Project::class);
        $project_used_as_template->shouldReceive('getID')->andReturn($this->template_id);
        $project_used_as_template->shouldReceive('getServices')->andReturn([$service_git, $service_tracker]);

        $template_for_project_creation = Mockery::mock(TemplateFromProjectForCreation::class);
        $template_for_project_creation->shouldReceive('getProject')->andReturn($project_used_as_template);

        $creation_request->setTemplateForProjectCreation($template_for_project_creation);

        return $creation_request;
    }

    public function testNewObjectSetsACustomTextDescriptionField()
    {
        $text_content = 'bla bla bla';
        $custom_id    = 101;

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX . "$custom_id" => $text_content,
        );

        $creation_request = $this->aCreationRequest($request_data);
        $this->assertEquals($text_content, $creation_request->getCustomProjectDescription($custom_id));
    }

    public function testItDoesNotSetACustomTextDescriptionFieldIfIdIsNotNumeric()
    {
        $text_content = 'bla bla bla';
        $custom_id    = 'name';

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX . "$custom_id" => $text_content,
        );

        $creation_request = $this->aCreationRequest($request_data);
        $this->assertNull($creation_request->getCustomProjectDescription($custom_id));
    }

    public function testGetProjectValuesContainsCustomTextDescriptionField()
    {
        $text_content = 'bla bla bla';
        $custom_id    = 101;

        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX . "$custom_id" => $text_content,
        );

        $creation_request = $this->aCreationRequest($request_data);

        $project_values = $creation_request->getProjectValues();
        $this->assertEquals(
            $text_content,
            $project_values['project'][Project_OneStepCreation_OneStepCreationPresenter::PROJECT_DESCRIPTION_PREFIX . "$custom_id"]
        );
    }

    public function testItForcesTheProjectToNotBeATest()
    {
        $request_data     = array('whatever');
        $creation_request = $this->aCreationRequest($request_data);

        $values = $creation_request->getProjectValues();
        $this->assertFalse($values['project']['is_test']);
    }

    public function testItIncludesTheUsedServicesOfTheChoosenTemplate()
    {
        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::TEMPLATE_ID => $this->template_id,
        );

        $creation_request = $this->aCreationRequest($request_data);
        $values = $creation_request->getProjectValues();

        $this->assertEquals(1, $values['project']['services'][$this->service_tracker_id]['is_used']);
        $this->assertEquals(0, $values['project']['services'][$this->service_git_id]['is_used']);
    }

    public function testItIncludesMandatoryTroveCats()
    {
        $request_data = array(
            Project_OneStepCreation_OneStepCreationPresenter::TROVE_CAT_PREFIX => array(
                1 => 235
            )
        );

        $creation_request = $this->aCreationRequest($request_data);
        $values           = $creation_request->getProjectValues();

        $this->assertNotNull($values['project']['trove'][1]);
        $this->assertEquals(array(235), $values['project']['trove'][1]);
    }
}
