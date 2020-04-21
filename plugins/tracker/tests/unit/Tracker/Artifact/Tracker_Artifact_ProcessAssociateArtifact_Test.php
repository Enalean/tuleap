<?php
/**
 * Copyright (c) Enalean, 2012 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact;

use Codendi_Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_FormElement_Field_ArtifactLink;
use Tracker_FormElementFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use UserManager;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Artifact_ProcessAssociateArtifact_Test extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use GlobalResponseMock;

    /**
     * @var Mockery\Mock|Tracker_Artifact
     */
    private $artifact;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Codendi_Request
     */
    private $request;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $user_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElementFactory
     */
    private $factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Tracker_FormElement_Field_ArtifactLink
     */
    private $field;

    public function setUp(): void
    {
        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(120);

        $this->user_manager = Mockery::mock(UserManager::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($this->user);

        $this->field = Mockery::mock(Tracker_FormElement_Field_ArtifactLink::class);
        $this->field->shouldReceive('getId')->andReturn(1002);

        $this->factory = Mockery::mock(Tracker_FormElementFactory::class);

        $this->artifact = Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact->shouldReceive('getUserManager')->andReturn($this->user_manager);
        $this->artifact->shouldReceive('getFormElementFactory')->andReturn($this->factory);

        $this->request       = new Codendi_Request(
            [
                'func'               => 'associate-artifact-to',
                'linked-artifact-id' => 987
            ]
        );
    }

    public function testItCreatesANewChangesetWithdrawingAnExistingAssociation()
    {
        $this->request = new Codendi_Request(
            [
                'func'               => 'unassociate-artifact-to',
                'linked-artifact-id' => 987
            ]
        );


        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getProjectId')->andReturn(200);
        $tracker->shouldReceive('getGroupId')->andReturn(2001);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);
        $this->factory->shouldReceive('getUsedArtifactLinkFields')->andReturn([$this->field]);

        $expected_field_data = array(
            $this->field->getId() => array(
                'new_values'     => '',
                'removed_values' => array(987 => 1),
            ),
        );
        $no_comment          = '';

        $this->artifact->shouldReceive('createNewChangeset')
                       ->withArgs([$expected_field_data, $no_comment, $this->user])
                       ->once();

        $this->artifact->process(Mockery::mock(\TrackerManager::class), $this->request, $this->user);
    }

    public function testItCreatesANewChangesetWithANewAssociation()
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(2001);
        $tracker->shouldReceive('isProjectAllowedToUseNature');

        $this->artifact->shouldReceive('getLastChangeset')->andReturns(null);

        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->factory->shouldReceive('getUsedArtifactLinkFields')->andReturn([$this->field]);

        $expected_field_data = [$this->field->getId() => ['new_values' => 987]];
        $no_comment          = '';

        $this->artifact->shouldReceive('createNewChangeset')
                       ->withArgs([$expected_field_data, $no_comment, $this->user])
                       ->once();

        $this->artifact->process(Mockery::mock(\TrackerManager::class), $this->request, $this->user);
    }

    public function testItDoesNotCreateANewChangesetWithANewAssociationIfTheLinkAlreadyExists()
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturn(2002);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->factory->shouldReceive('getUsedArtifactLinkFields')->andReturn([$this->field]);

        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);

        $changeset_value = Mockery::mock(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $changeset->shouldReceive('getValue')->withArgs([$this->field])->andReturn($changeset_value);
        $changeset_value->shouldReceive('getArtifactIds')->andReturn(array(987));

        $this->artifact->shouldNotReceive('createNewChangeset');

        $this->artifact->process(Mockery::mock(\TrackerManager::class), $this->request, $this->user);
    }

    public function testItReturnsAnErrorCodeWhenItHasNoArtifactLinkField()
    {
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(456);
        $tracker->shouldReceive('getProjectId')->andReturn(120);
        $tracker->shouldReceive('getGroupId')->andReturn(1202);

        $artifact = $this->givenAnArtifact($tracker);

        $this->factory->shouldReceive('getUsedArtifactLinkFields')->withArgs([$tracker])->andReturn([]);
        $artifact->setFormElementFactory($this->factory);

        $artifact->shouldReceive('createNewChangeset')->never();
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(400)->once();
        $GLOBALS['Language']->shouldReceive('getText')
                            ->with('plugin_tracker', 'must_have_artifact_link_field')
                            ->andReturns('The destination artifact must have a artifact link field.');
        $GLOBALS['Response']->shouldReceive('addFeedback')
                            ->withArgs(['error', 'The destination artifact must have a artifact link field.']);

        $artifact->process(Mockery::mock(\TrackerManager::class), $this->request, $this->user);
    }

    private function givenAnArtifact($tracker)
    {
        $this->artifact->setTracker($tracker);
        return $this->artifact;
    }
}
