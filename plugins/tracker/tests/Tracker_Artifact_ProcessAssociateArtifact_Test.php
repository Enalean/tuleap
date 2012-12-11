<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once(dirname(__FILE__).'/../include/constants.php');
require_once(dirname(__FILE__).'/../include/Tracker/Artifact/Tracker_Artifact.class.php');

class Tracker_Artifact_ProcessAssociateArtifact_Test extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user = new MockUser();
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
                    )
                );
        
        $factory  = mock('Tracker_FormElementFactory');
        stub($artifact)->getFormElementFactory()->returns($factory);
        
        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $expected_field_data = array(
            $field->getId() => array(
                'new_values' => '',
                'removed_values' => array(987 => 1),
                ),
            );
        $no_comment = $no_email = '';

        stub($artifact)->createNewChangeset($expected_field_data, $no_comment, $this->user, $no_email)->once();      
 
        $artifact->process(new MockTrackerManager(), $this->request, $this->user);
    }

    public function itCreatesANewChangesetWithANewAssociation() {
        $artifact = partial_mock('Tracker_Artifact', 
                array(
                    'getFormElementFactory', 
                    'getTracker',
                    'createNewChangeset',
                    )
                );

        
        $factory  = mock('Tracker_FormElementFactory');
        stub($artifact)->getFormElementFactory()->returns($factory);
        
        $field = anArtifactLinkField()->withId(1002)->build();
        stub($factory)->getUsedArtifactLinkFields()->returns(array($field));

        $expected_field_data = array($field->getId() => array('new_values' => 987));
        $no_comment = $no_email = '';

        $artifact->expectOnce('createNewChangeset', array($expected_field_data, $no_comment, $this->user, $no_email));

        $artifact->process(new MockTrackerManager(), $this->request, $this->user);
    }

    public function itReturnsAnErrorCodeWhenItHasNoArtifactLinkField() {
        $tracker  = aTracker()->withId(456)->build();

        $artifact = $this->GivenAnArtifact($tracker);

        $factory  = stub('Tracker_FormElementFactory')->getUsedArtifactLinkFields($tracker)->returns(array());
        $artifact->setFormElementFactory($factory);

        $artifact->expectNever('createNewChangeset');
        $GLOBALS['Response']->expectOnce('sendStatusCode', array(400));
        $GLOBALS['Language']->setReturnValue('getText', 'The destination artifact must have a artifact link field.', array('plugin_tracker', 'must_have_artifact_link_field'));
        $this->expectFeedback('error', 'The destination artifact must have a artifact link field.');

        $artifact->process(new MockTrackerManager(), $this->request, $this->user);
    }

    private function GivenAnArtifact($tracker) {
        $artifact = TestHelper::getPartialMock('Tracker_Artifact', array('createNewChangeset'));
        $artifact->setTracker($tracker);
        return $artifact;
    }
}

?>