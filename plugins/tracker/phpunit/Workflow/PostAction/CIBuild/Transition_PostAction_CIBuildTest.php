<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

declare(strict_types=1);

final class Transition_PostAction_CIBuildTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use \Tuleap\GlobalResponseMock;
    use \Tuleap\GlobalLanguageMock;

    /**
     * @var array
     */
    private $expected_parameters;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker
     */
    private $tracker;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Transition
     */
    private $transition;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_Artifact_Changeset
     */
    private $changeset;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $field;
    /**
     * @var Jenkins_Client|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $client;
    /**
     * @var Transition_PostAction_CIBuild
     */
    private $post_action_ci_build;
    /**
     * @var string
     */
    private $job_url;

    protected function setUp(): void
    {
        $build_user             = 'mickey mooouse';
        $project_id             = 9852614;
        $artifact_id            = 333558899;
        $tracker_id             = 5245;
        $value_triggering_build = 'the fat field';

        $this->expected_parameters = [
            Transition_PostAction_CIBuild::BUILD_PARAMETER_USER                => $build_user,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_PROJECT_ID          => $project_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_ARTIFACT_ID         => $artifact_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_TRACKER_ID          => $tracker_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_TRIGGER_FIELD_VALUE => $value_triggering_build,
        ];

        $this->transition = \Mockery::spy(\Transition::class);
        $id               = 123;
        $this->job_url    = 'http://example.com/job';
        $this->client     = \Mockery::spy(\Jenkins_Client::class);

        $this->post_action_ci_build = new Transition_PostAction_CIBuild(
            $this->transition,
            $id,
            $this->job_url,
            $this->client
        );

        $this->tracker   = \Mockery::spy(\Tracker::class);
        $this->project   = \Mockery::spy(\Project::class);
        $this->artifact  = \Mockery::spy(\Tracker_Artifact::class);
        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->field     = \Mockery::spy(\Tracker_FormElement_Field_Selectbox::class);

        $this->changeset->shouldReceive('getSubmittedBy')->andReturns($build_user);

        $this->changeset->shouldReceive('getArtifact')->andReturns($this->artifact);
        $this->artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->tracker->shouldReceive('getProject')->andReturns($this->project);
        $this->project->shouldReceive('getId')->andReturns($project_id);

        $this->artifact->shouldReceive('getId')->andReturns($artifact_id);

        $this->tracker->shouldReceive('getId')->andReturns($tracker_id);

        $this->transition->shouldReceive('getFieldValueTo')->andReturns($this->field);
        $this->field->shouldReceive('getLabel')->andReturns($value_triggering_build);
    }

    public function testItIsNotDefinedWhenJobUrlIsEmpty(): void
    {
        $transition = \Mockery::spy(\Transition::class);
        $id         = 123;
        $job_url    = null;
        $client     = \Mockery::spy(\Jenkins_Client::class);

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
        $this->assertFalse($post_action_ci_build->isDefined());
    }

    public function testItIsDefinedWhenJobUrlIsFilled(): void
    {
        $transition = \Mockery::spy(\Transition::class);
        $id         = 123;
        $job_url    = 'http://example.com/job';
        $client     = \Mockery::spy(\Jenkins_Client::class);

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);
        $this->assertTrue($post_action_ci_build->isDefined());
    }

    public function testItExportsInXMLFormatTheJobUrl(): void
    {
        $transition = \Mockery::spy(\Transition::class);
        $id         = 123;
        $job_url    = 'http://example.com';
        $client     = \Mockery::spy(\Jenkins_Client::class);

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertEquals($job_url, (string) $root->postaction_ci_build['job_url']);
    }

    public function testItDoesNotExportThePostActionIfJobUrlIsNotSet(): void
    {
        $transition = \Mockery::spy(\Transition::class);
        $id         = 123;
        $job_url    = '';
        $client     = \Mockery::spy(\Jenkins_Client::class);

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $client);

        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertFalse(isset($root->postaction_ci_build));
    }

    public function testItLaunchTheCIBuildOnAfter(): void
    {
        $this->client->shouldReceive('launchJobBuild')->with($this->job_url, \Mockery::any())->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItDisplayInfoFeedbackIfLaunchSucceed(): void
    {
        $GLOBALS['Response']->shouldReceive('addFeedback')->with('info', \Mockery::any())->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItDisplayErrorFeedbackIfLaunchFailed(): void
    {
        $error_message = 'Oops';
        $this->client->shouldReceive('launchJobBuild')->with($this->job_url, \Mockery::any())->andThrows(
            new Jenkins_ClientUnableToLaunchBuildException($error_message)
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->with('error', $error_message)->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItIncludesTheNeededParameters(): void
    {
        $this->client->shouldReceive('launchJobBuild')->with($this->job_url, $this->expected_parameters)->once();
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItDoesNothingIfThePostActionIsNotDefined(): void
    {
        $id                   = 123;
        $job_url              = '';
        $post_action_ci_build = new Transition_PostAction_CIBuild($this->transition, $id, $job_url, $this->client);
        $GLOBALS['Response']->shouldReceive('addFeedback')->never();
        $this->client->shouldReceive('launchJobBuild')->never();
        $post_action_ci_build->after($this->changeset);
    }
}
