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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Transition_PostAction_CIBuildTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
{
    use \Tuleap\GlobalResponseMock;

    private array $expected_parameters;
    private Transition $transition;
    private Tracker_Artifact_Changeset $changeset;
    private Jenkins_Client&MockObject $client;
    private Transition_PostAction_CIBuild $post_action_ci_build;
    private string $job_url;

    #[\Override]
    protected function setUp(): void
    {
        $build_user             = 101;
        $project_id             = 9852614;
        $artifact_id            = 333558899;
        $tracker_id             = 5245;
        $value_triggering_build = 'the fat field';

        $this->expected_parameters = [
            Transition_PostAction_CIBuild::BUILD_PARAMETER_USER => $build_user,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_PROJECT_ID => $project_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_ARTIFACT_ID => $artifact_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_TRACKER_ID => $tracker_id,
            Transition_PostAction_CIBuild::BUILD_PARAMETER_TRIGGER_FIELD_VALUE => $value_triggering_build,
        ];

        $field_value = ListStaticValueBuilder::aStaticValue($value_triggering_build)->build();

        $this->transition = new Transition(100001, 123, null, $field_value);
        $id               = 123;
        $this->job_url    = 'http://example.com/job';
        $this->client     = $this->createMock(\Jenkins_Client::class);

        $this->post_action_ci_build = new Transition_PostAction_CIBuild(
            $this->transition,
            $id,
            $this->job_url,
            $this->client
        );

        $project  = ProjectTestBuilder::aProject()->withId($project_id)->build();
        $tracker  = TrackerTestBuilder::aTracker()->withId($tracker_id)->withProject($project)->build();
        $artifact = ArtifactTestBuilder::anArtifact($artifact_id)->inTracker($tracker)->build();

        $this->changeset = ChangesetTestBuilder::aChangeset(1001)
            ->ofArtifact($artifact)
            ->submittedBy($build_user)
            ->build();
    }

    public function testItIsNotDefinedWhenJobUrlIsEmpty(): void
    {
        $transition = $this->createMock(\Transition::class);
        $id         = 123;
        $job_url    = null;

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $this->client);
        $this->assertFalse($post_action_ci_build->isDefined());
    }

    public function testItIsDefinedWhenJobUrlIsFilled(): void
    {
        $transition = $this->createMock(\Transition::class);
        $id         = 123;
        $job_url    = 'http://example.com/job';

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $this->client);
        $this->assertTrue($post_action_ci_build->isDefined());
    }

    public function testItExportsInXMLFormatTheJobUrl(): void
    {
        $transition = $this->createMock(\Transition::class);
        $id         = 123;
        $job_url    = 'http://example.com';

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $this->client);

        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertEquals($job_url, (string) $root->postaction_ci_build['job_url']);
    }

    public function testItDoesNotExportThePostActionIfJobUrlIsNotSet(): void
    {
        $transition = $this->createMock(\Transition::class);
        $id         = 123;
        $job_url    = '';

        $post_action_ci_build = new Transition_PostAction_CIBuild($transition, $id, $job_url, $this->client);

        $root              = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
        $array_xml_mapping = [];

        $post_action_ci_build->exportToXml($root, $array_xml_mapping);
        $this->assertFalse(isset($root->postaction_ci_build));
    }

    public function testItLaunchTheCIBuildOnAfter(): void
    {
        $this->client->expects($this->once())->method('launchJobBuild')->with($this->job_url, $this->anything());
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItDisplayInfoFeedbackIfLaunchSucceed(): void
    {
        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('info');
        $this->client->method('launchJobBuild');
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItDisplayErrorFeedbackIfLaunchFailed(): void
    {
        $error_message = 'Oops';
        $this->client->method('launchJobBuild')->with($this->job_url, $this->anything())->willThrowException(
            new Jenkins_ClientUnableToLaunchBuildException($error_message)
        );

        $GLOBALS['Response']->expects($this->once())->method('addFeedback')->with('error', $error_message);
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItIncludesTheNeededParameters(): void
    {
        $this->client->expects($this->once())->method('launchJobBuild')->with($this->job_url, $this->expected_parameters);
        $this->post_action_ci_build->after($this->changeset);
    }

    public function testItDoesNothingIfThePostActionIsNotDefined(): void
    {
        $id                   = 123;
        $job_url              = '';
        $post_action_ci_build = new Transition_PostAction_CIBuild($this->transition, $id, $job_url, $this->client);
        $GLOBALS['Response']->expects($this->never())->method('addFeedback');
        $this->client->expects($this->never())->method('launchJobBuild');
        $post_action_ci_build->after($this->changeset);
    }
}
