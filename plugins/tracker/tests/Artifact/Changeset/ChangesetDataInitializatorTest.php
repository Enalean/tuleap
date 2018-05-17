<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

require_once __DIR__.'/../../bootstrap.php';

class Tracker_Artifact_Changeset_ChangesetDataInitializator_LoadFromOldChangesetTest extends TuleapTestCase {

    private $initializator;
    private $formelement_factory;
    private $artifact_builder;
    private $tracker;

    public function setUp() {
        parent::setUp();
        $this->tracker             = aTracker()->build();
        $this->artifact_builder    = anArtifact()->withTracker($this->tracker);
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->formelement_factory)->getAllFormElementsForTracker($this->tracker)->returns(array());
        $this->initializator       = new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory);
    }

    public function itPreloadsDateFieldsFromPreviousChangeset() {
        $artifact = $this->artifact_builder->withChangesets(
            array(
                stub('Tracker_Artifact_Changeset')->getValues()->returns(
                    array(
                        14 => stub('Tracker_Artifact_ChangesetValue_Date')->getValue()->returns('2014-03-12')
                    )
                )
            )
        )->build();
        $fields_data   = array();

        $this->assertEqual(
            $this->initializator->process($artifact, $fields_data),
            array(
                14 => '2014-03-12'
            )
        );
    }

    public function itPreloadsListFieldsFromPreviousChangeset() {
        $artifact = $this->artifact_builder->withChangesets(
            array(
                stub('Tracker_Artifact_Changeset')->getValues()->returns(
                    array(
                        22 => stub('Tracker_Artifact_ChangesetValue_List')->getValue()->returns('101')
                    )
                )
            )
        )->build();
        $fields_data   = array();

        $this->assertEqual(
            $this->initializator->process($artifact, $fields_data),
            array(
                22 => '101'
            )
        );
    }

    public function testSubmittedDateFieldsOverridesPreviousChangeset() {
        $artifact = $this->artifact_builder->withChangesets(
            array(
                stub('Tracker_Artifact_Changeset')->getValues()->returns(
                    array(
                        14 => stub('Tracker_Artifact_ChangesetValue_Date')->getValue()->returns('2013-07-08')
                    )
                )
            )
        )->build();
        $fields_data   = array(
            14 => '2014-07-07'
        );

        $this->assertEqual(
            $this->initializator->process($artifact, $fields_data),
            array(
                14 => '2014-07-07'
            )
        );
    }

    public function testSubmittedListFieldsOverridesPreviousChangeset() {
        $artifact = $this->artifact_builder->withChangesets(
            array(
                stub('Tracker_Artifact_Changeset')->getValues()->returns(
                    array(
                        22 => stub('Tracker_Artifact_ChangesetValue_Date')->getValue()->returns('101')
                    )
                )
            )
        )->build();
        $fields_data   = array(
            22 => '108'
        );

        $this->assertEqual(
            $this->initializator->process($artifact, $fields_data),
            array(
                22 => '108'
            )
        );
    }
}

class Tracker_Artifact_Changeset_ChangesetDataInitializator_LoadAutomaticValuesTest extends TuleapTestCase {

    private $initializator;
    private $formelement_factory;
    private $artifact_builder;
    private $tracker;

    public function setUp() {
        parent::setUp();
        $this->tracker             = aTracker()->build();
        $this->artifact_builder    = anArtifact()
            ->withTracker($this->tracker)
            ->withChangesets(array(new Tracker_Artifact_Changeset_Null()));
        $this->formelement_factory = mock('Tracker_FormElementFactory');
        $this->initializator       = new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->formelement_factory);
    }

    public function itAppendsSubmittedBy() {
        $artifact = $this->artifact_builder->withSubmittedOn('2055-4-99')->build();
        stub($this->formelement_factory)->getAllFormElementsForTracker($this->tracker)->returns(
            array(
                stub('Tracker_FormElement_Field_SubmittedOn')->getId()->returns(12)
            )
        );

        $this->assertEqual(
            $this->initializator->process($artifact, array()),
            array(
                12 => '2055-4-99'
            )
        );
    }

    public function itAppendsLastUpdateDateAtCurrentTime() {
        $artifact = $this->artifact_builder->build();
        stub($this->formelement_factory)->getAllFormElementsForTracker($this->tracker)->returns(
            array(
                stub('Tracker_FormElement_Field_LastUpdateDate')->getId()->returns(55)
            )
        );

        $this->assertEqual(
            $this->initializator->process($artifact, array()),
            array(
                55 => date('Y-m-d', $_SERVER['REQUEST_TIME'])
            )
        );
    }
}
