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

use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;

class OneStepProjectCreationValidatorTest extends TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalLanguageMock, ForgeConfigSandbox;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Rule_ProjectName
     */
    private $rule_project_name;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Rule_ProjectFullName
     */
    private $rule_project_full_name;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectManager
     */
    private $project_manager;

    public function setUp(): void
    {
        parent::setUp();

        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->rule_project_name = Mockery::mock(Rule_ProjectName::class);
        $this->rule_project_full_name = Mockery::mock(Rule_ProjectFullName::class);

        $GLOBALS['Response'] = Mockery::mock(BaseLayout::class);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['Response']);

        parent::tearDown();
    }

    public function testValidateAndGenerateErrorsValidatesTemplateId(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(null);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-names');
        $request->shouldReceive('isPublic')->once()->andReturnTrue();
        $request->shouldReceive('getFullName')->twice()->andReturn('project full name');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $GLOBALS['Response']->shouldReceive('addFeedback')->once()->withArgs([Feedback::ERROR, Mockery::any()]);

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testItReturnsFalseIfSelectedTemplateIsNotActive(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(342);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-names');
        $request->shouldReceive('isPublic')->once()->andReturnTrue();
        $request->shouldReceive('getFullName')->twice()->andReturn('project full name');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getID')->once()->andReturn(342);
        $project->shouldReceive('isTemplate')->once()->andReturnFalse();
        $project->shouldReceive('isActive')->once()->andReturnFalse();
        $project->shouldReceive('isError')->once()->andReturnFalse();
        $this->project_manager->shouldReceive('getProject')->once()->withArgs([342])->andReturn($project);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once()->withArgs([Feedback::ERROR, Mockery::any()]);

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $this->assertFalse($validator->validateAndGenerateErrors());
    }

    public function testValidateAndGenerateErrorsValidatesFullname(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getUnixName')->twice()->andReturn('...invalidProjectName');

        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('isPublic')->once()->andReturnTrue();
        $request->shouldReceive('getFullName')->twice()->andReturn('project full name');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->twice();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnFalse();
        $this->rule_project_name->shouldReceive('getErrorMessage')->once();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsValidatesProjectPrivacy(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturn(null);
        $request->shouldReceive('getFullName')->twice()->andReturn('project full name');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->once();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsValidatesProjectFullName(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->twice();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnFalse();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->once();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsValidatesNotSetDescription(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->once()->andReturn(null);
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->once();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsValidatesRequiredAndEmptyDescription(): void
    {
        ForgeConfig::set('enable_not_mandatory_description', false);

        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->once()->andReturn(null);
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->once();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsValidatesNotMandatoryDescription(): void
    {
        ForgeConfig::set('enable_not_mandatory_description', true);

        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->never();
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->never();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsTosApproval(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturn(null);

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->once();

        $required_custom_descriptions = [];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsWhenARequiredCustomDescriptionIsNotSet()
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();
        $request->shouldReceive('getCustomProjectDescription')->once()->withArgs([101])->andReturnFalse();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->once();

        $required_custom_descriptions = [
            101 => new Project_CustomDescription_CustomDescription(
                101,
                "A REQUIRED description field",
                "desc",
                Project_CustomDescription_CustomDescription::REQUIRED,
                Project_CustomDescription_CustomDescription::TYPE_TEXT,
                1
            ),
        ];
        $trove_cats                   = [];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);

        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    public function testValidateAndGenerateErrorsWhenMandatoryTroveCatIsNotSet(): void
    {
        $request = Mockery::mock(Project_OneStepCreation_OneStepCreationRequest::class);
        $request->shouldReceive('getTemplateId')->once()->andReturn(10);
        $request->shouldReceive('getCurrentUser')->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('setTemplateForProjectCreation')->once();
        $request->shouldReceive('getUnixName')->twice()->andReturn('project-name');
        $request->shouldReceive('isPublic')->once()->andReturnFalse();
        $request->shouldReceive('getFullName')->twice()->andReturn('invalid');
        $request->shouldReceive('getShortDescription')->once()->andReturn('a short description');
        $request->shouldReceive('getTosApproval')->once()->andReturnTrue();
        $request->shouldReceive('getTroveCat')->withArgs([1])->andReturnFalse();

        $this->generateAValidTemplateId();

        $GLOBALS['Response']->shouldReceive('addFeedback')->withArgs([Feedback::ERROR, Mockery::any()])->once();

        $required_custom_descriptions = [];
        $trove_cat = new TroveCat(1, 'whatever', 'WhatEver');
        $trove_cats = [$trove_cat];
        $validator                    = $this->getValidator($request, $required_custom_descriptions, $trove_cats);
        $this->rule_project_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_name->shouldReceive('getErrorMessage')->never();

        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->never();

        $validator->validateAndGenerateErrors();
    }

    private function generateAValidTemplateId(): void
    {
        $project = Mockery::mock(Project::class);
        $project->shouldReceive('isActive')->andReturnTrue();
        $project->shouldReceive('isTemplate')->andReturnTrue();
        $project->shouldReceive('isError')->andReturnFalse();
        $this->project_manager->shouldReceive('getProject')->withArgs([10])->andReturn($project);
    }

    /**
     * @return \Mockery\Mock|Project_OneStepCreation_OneStepCreationValidator
     */
    private function getValidator(Project_OneStepCreation_OneStepCreationRequest $request, array $required_custom_descriptions, array $trove_cats)
    {
        return new Project_OneStepCreation_OneStepCreationValidator(
            $request,
            $required_custom_descriptions,
            $trove_cats,
            $this->project_manager,
            $this->rule_project_full_name,
            $this->rule_project_name
        );
    }
}
