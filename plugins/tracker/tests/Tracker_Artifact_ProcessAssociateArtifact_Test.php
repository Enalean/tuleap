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

class Tracker_Artifact_ProcessAssociateArtifact_Test extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user = mock('PFUser');
        $this->request = new Codendi_Request(array(
            'func'               => 'associate-artifact-to',
            'linked-artifact-id' => 987));
    }

    public function itCreatesANewChangesetWithdrawingAnExistingAssociation() {
        $this->request = new Codendi_Request(array(
            'func'               => 'unassociate-artifact-to',
            'linked-artifact-id' => 987));

        $artifact = partial_mock('Tracker_Artifact',
                array(
                    'getFormElementFactory',
                    'getTracker',
                    'createNewChangeset',
                    'getUserManager'
                    )
                );

        $user_manager = stub('UserManager')->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);

        $factory  = mock('Tracker_FormElementFactory');
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

    public function itCreatesANewChangesetWithANewAssociation() {
        $artifact = partial_mock('Tracker_Artifact',
            array(
                'getFormElementFactory',
                'getTracker',
                'createNewChangeset',
                'getUserManager',
                'getLastChangeset'
            )
        );

        $factory  = mock('Tracker_FormElementFactory');
        stub($artifact)->getFormElementFactory()->returns($factory);

        $user_manager = stub('UserManager')->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);
        stub($artifact)->getTracker()->returns(mock('Tracker'));

        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $expected_field_data = array($field->getId() => array('new_values' => 987));
        $no_comment = '';

        $artifact->expectOnce('createNewChangeset', array($expected_field_data, $no_comment, $this->user));

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    public function itDoesNotCreateANewChangesetWithANewAssociationIfTheLinkAlreadyExists()
    {
        $artifact = partial_mock('Tracker_Artifact',
            array(
                'getFormElementFactory',
                'getTracker',
                'createNewChangeset',
                'getUserManager',
                'getLastChangeset'
            )
        );

        $factory  = mock('Tracker_FormElementFactory');
        stub($artifact)->getFormElementFactory()->returns($factory);

        $user_manager = stub('UserManager')->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);
        stub($artifact)->getTracker()->returns(mock('Tracker'));

        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $changeset = mock('Tracker_Artifact_Changeset');
        stub($artifact)->getLastChangeset()->returns($changeset);

        $changeset_value = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($changeset)->getValue($field)->returns($changeset_value);
        stub($changeset_value)->getArtifactIds()->returns(array(987));

        expect($artifact)->createNewChangeset()->never();

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    public function itReturnsAnErrorCodeWhenItHasNoArtifactLinkField() {
        $tracker  = aTracker()->withId(456)->withProjectId(120)->build();

        $artifact = $this->GivenAnArtifact($tracker);

        $factory  = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields($tracker)->returns(array());
        $artifact->setFormElementFactory($factory);

        $artifact->expectNever('createNewChangeset');
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(400));
        $GLOBALS['Language']->setReturnValue('getText', 'The destination artifact must have a artifact link field.', array('plugin_tracker', 'must_have_artifact_link_field'));
        $this->expectFeedback('error', 'The destination artifact must have a artifact link field.');

        $artifact->process(mock('TrackerManager'), $this->request, $this->user);
    }

    private function GivenAnArtifact($tracker) {
        $artifact = TestHelper::getPartialMock('Tracker_Artifact', array('createNewChangeset','getUserManager'));

        $user_manager = stub('UserManager')->getCurrentUser()->returns(aUser()->withId(120)->build());
        stub($artifact)->getUserManager()->returns($user_manager);

        $artifact->setTracker($tracker);
        return $artifact;
    }
}
