<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
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

require_once('bootstrap.php');

class Tracker_Artifact_ProcessAssociateArtifact_Test extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user = \Mockery::spy(PFUser::class);
        $this->request = new Codendi_Request(array(
            'func'               => 'associate-artifact-to',
            'linked-artifact-id' => 987));
    }

    public function itCreatesANewChangesetWithdrawingAnExistingAssociation()
    {
        $this->request = new Codendi_Request(array(
            'func'               => 'unassociate-artifact-to',
            'linked-artifact-id' => 987));

        $artifact = \Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $user_manager = mockery_stub(UserManager::class)->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);

        $factory  = \Mockery::spy(Tracker_FormElementFactory::class);
        stub($artifact)->getFormElementFactory()->returns($factory);

        $tracker = aTracker()->withProjectId(200)->build();
        stub($artifact)->getTracker()->returns($tracker);

        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $expected_field_data = array(
            $field->getId() => array(
                'new_values' => '',
                'removed_values' => array(987 => 1),
                ),
            );
        $no_comment = '';

        stub($artifact)->createNewChangeset($expected_field_data, $no_comment, $this->user)->once();

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    public function itCreatesANewChangesetWithANewAssociation()
    {
        $artifact = \Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $artifact->shouldReceive('getLastChangeset')->andReturns(null);

        $factory  = \Mockery::spy(Tracker_FormElementFactory::class);
        stub($artifact)->getFormElementFactory()->returns($factory);

        $user_manager = mockery_stub(UserManager::class)->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);
        stub($artifact)->getTracker()->returns(mock('Tracker'));

        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $expected_field_data = array($field->getId() => array('new_values' => 987));
        $no_comment = '';

        $artifact->shouldReceive('createNewChangeset')->with($expected_field_data, $no_comment, $this->user)->once();

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    public function itDoesNotCreateANewChangesetWithANewAssociationIfTheLinkAlreadyExists()
    {
        $artifact = \Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $factory  = \Mockery::spy(Tracker_FormElementFactory::class);
        stub($artifact)->getFormElementFactory()->returns($factory);

        $user_manager = mockery_stub(UserManager::class)->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);
        stub($artifact)->getTracker()->returns(mock('Tracker'));

        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $changeset = \Mockery::spy(Tracker_Artifact_Changeset::class);
        stub($artifact)->getLastChangeset()->returns($changeset);

        $changeset_value = \Mockery::spy(Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        stub($changeset)->getValue($field)->returns($changeset_value);
        stub($changeset_value)->getArtifactIds()->returns(array(987));

        expect($artifact)->createNewChangeset()->never();

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    public function itReturnsAnErrorCodeWhenItHasNoArtifactLinkField()
    {
        $tracker  = aTracker()->withId(456)->withProjectId(120)->build();

        $artifact = $this->GivenAnArtifact($tracker);

        $factory  = mockery_stub(Tracker_FormElementFactory::class)->getUsedArtifactLinkFields($tracker)->returns(array());
        $artifact->setFormElementFactory($factory);

        $artifact->shouldReceive('createNewChangeset')->never();
        $GLOBALS['Response']->shouldReceive('sendStatusCode')->with(400)->once();
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker', 'must_have_artifact_link_field')->andReturns('The destination artifact must have a artifact link field.');
        $this->expectFeedback('error', 'The destination artifact must have a artifact link field.');

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    private function GivenAnArtifact($tracker)
    {
        $artifact = \Mockery::mock(Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $user_manager = mockery_stub(UserManager::class)->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);

        $artifact->setTracker($tracker);
        return $artifact;
    }
}
