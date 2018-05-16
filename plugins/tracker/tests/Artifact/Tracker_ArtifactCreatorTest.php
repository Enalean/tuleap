<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

require_once __DIR__.'/../bootstrap.php';

class Tracker_ArtifactCreator_createTest extends TuleapTestCase {

    /** @var Tracker_Artifact_Changeset_InitialChangesetCreatorBase */
    private $changeset_creator;

    /** @var Tracker_Artifact_Changeset_FieldsValidator */
    private $fields_validator;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_ArtifactCreator */
    private $creator;

    /** @var Tracker */
    private $tracker;

    /** @var PFUser */
    private $user;

    /** @var Tracker_ArtifactDao */
    private $dao;

    /** @var Tracker_Artifact */
    private $bare_artifact;
    /**
     * @var Tuleap\Tracker\RecentlyVisited\VisitRecorder
     */
    private $visit_recorder;

    private $fields_data       = array();
    private $submitted_on      = 1234567890;
    private $send_notification = true;

    public function setUp() {
        parent::setUp();
        Tracker_ArtifactFactory::clearInstance();
        $this->artifact_factory  = Tracker_ArtifactFactory::instance();
        $this->changeset_creator = mock('Tracker_Artifact_Changeset_InitialChangesetCreator');
        $this->fields_validator  = mock('Tracker_Artifact_Changeset_InitialChangesetFieldsValidator');
        $this->dao               = mock('Tracker_ArtifactDao');
        $this->visit_recorder    = mock('Tuleap\\Tracker\\RecentlyVisited\\VisitRecorder');

        $this->artifact_factory->setDao($this->dao);

        $this->tracker       = aTracker()->withId(123)->build();
        $this->user          = aUser()->withId(101)->build();
        $this->bare_artifact = new Tracker_Artifact(0, 123, 101, 1234567890, 0);

        $this->creator = new Tracker_ArtifactCreator(
            $this->artifact_factory,
            $this->fields_validator,
            $this->changeset_creator,
            $this->visit_recorder
        );
    }

    public function tearDown() {
        Tracker_ArtifactFactory::clearInstance();
        parent::tearDown();
    }

    public function itValidateFields() {
        expect($this->fields_validator)->validate($this->bare_artifact, $this->fields_data)->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }

    public function itReturnsFalseIfFIeldsAreNotValid() {
        stub($this->fields_validator)->validate()->returns(false);

        expect($this->dao)->create()->never();
        expect($this->changeset_creator)->create()->never();

        $result = $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );

        $this->assertFalse($result);
    }

    public function itCreateArtifactsInDbIfFieldsAreValid() {
        stub($this->fields_validator)->validate()->returns(true);

        expect($this->dao)->create(123, 101, 1234567890, 0)->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }

    public function itReturnsFalseIfCreateArtifactsInDbFails() {
        stub($this->fields_validator)->validate()->returns(true);
        stub($this->dao)->create()->returns(false);

        expect($this->changeset_creator)->create()->never();

        $result = $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );

        $this->assertFalse($result);
    }

    public function itCreateChangesetIfCreateArtifactsInDbSucceeds() {
        stub($this->fields_validator)->validate()->returns(true);
        stub($this->dao)->create()->returns(1001);

        $this->bare_artifact->setId(1001);

        expect($this->changeset_creator)
            ->create($this->bare_artifact, $this->fields_data, $this->user, $this->submitted_on)
            ->once();

        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }

    public function itMarksTheArtifactAsVisitedWhenSuccessfullyCreated()
    {
        stub($this->fields_validator)->validate()->returns(true);
        stub($this->dao)->create()->returns(1001);
        stub($this->changeset_creator)->create()->returns(1);

        $this->send_notification = false;
        $this->bare_artifact->setId(1001);

        expect($this->visit_recorder)->record()->once();


        $this->creator->create(
            $this->tracker,
            $this->fields_data,
            $this->user,
            $this->submitted_on,
            $this->send_notification
        );
    }
}
