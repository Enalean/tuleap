<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use TuleapTestCase;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class ArtifactLinkValueSaver_saveValueTest extends TuleapTestCase {

    /** @var Tracker_FormElement_Field_ArtifactLink */
    private $field;

    /** @var ArtifactLinkValueSaver */
    private $saver;

    /** @var Tracker_ReferenceManager */
    private $reference_manager;

    /** @var Tracker_Artifact */
    private $initial_linked_artifact;

    /** @var Tracker_Artifact */
    private $some_artifact;

    /** @var Tracker_Artifact */
    private $other_artifact;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_ChangesetValue_ArtifactLink */
    private $previous_changesetvalue;

    /** @var PFUser */
    private $user;

    private $changeset_value_id = 56;

    /** @var Tracker_FormElement_Field_Value_ArtifactLinkDao */
    private $dao;

    public function setUp() {
        parent::setUp();

        $this->field             = mock('Tracker_FormElement_Field_ArtifactLink');
        $this->reference_manager = mock('Tracker_ReferenceManager');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->dao               = mock('Tracker_FormElement_Field_Value_ArtifactLinkDao');

        $this->tracker       = stub('Tracker')->getId()->returns(102);
        $this->tracker_child = stub('Tracker')->getId()->returns(101);

        stub($this->tracker)->getChildren()->returns(array($this->tracker_child));
        stub($this->tracker_child)->getChildren()->returns(array());

        $this->initial_linked_artifact = mock('Tracker_Artifact');
        stub($this->initial_linked_artifact)->getId()->returns(36);
        stub($this->initial_linked_artifact)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactById(36)->returns($this->initial_linked_artifact);

        $this->some_artifact = mock('Tracker_Artifact');
        stub($this->some_artifact)->getId()->returns(456);
        stub($this->some_artifact)->getTracker()->returns($this->tracker_child);
        stub($this->artifact_factory)->getArtifactById(456)->returns($this->some_artifact);

        $this->other_artifact = mock('Tracker_Artifact');
        stub($this->other_artifact)->getId()->returns(457);
        stub($this->other_artifact)->getTracker()->returns($this->tracker_child);
        stub($this->artifact_factory)->getArtifactById(457)->returns($this->other_artifact);

        $this->previous_changesetvalue = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($this->previous_changesetvalue)->getArtifactIds()->returns(array(36));

        $this->user = aUser()->withId(101)->build();

        $this->saver = new ArtifactLinkValueSaver(
            $this->artifact_factory,
            $this->dao,
            $this->reference_manager,
            mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector')
        );
    }

    public function itRemovesACrossReference() {
        $artifact = stub('Tracker_Artifact')->getTracker()->returns($this->tracker);

        $value = array(
            'new_values' => '',
            'removed_values' => array(
                36 => 1
            )
        );

        stub($this->field)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array())->at(0)->returns(array());
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36))->at(1)->returns(array($this->initial_linked_artifact));

        expect($this->reference_manager)->removeBetweenTwoArtifacts($artifact, $this->initial_linked_artifact, $this->user)->once();
        expect($this->dao)->create()->never();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value,
            $this->previous_changesetvalue
        );
    }

    public function itAddsACrossReference() {
        $artifact = stub('Tracker_Artifact')->getTracker()->returns($this->tracker);

        $value = array(
            'new_values' => 36,
            'removed_values' => array()
        );

        stub($this->dao)->create()->returns(true);
        stub($this->field)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36))->at(0)->returns(array($this->initial_linked_artifact));
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36))->at(1)->returns(array());

        expect($this->reference_manager)->insertBetweenTwoArtifacts($artifact, $this->initial_linked_artifact, $this->user)->once();
        expect($this->dao)->create()->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value,
            $this->previous_changesetvalue
        );
    }

    public function itCallsOnlyOneTimeCreateInDBIfAllArtifactsAreInTheSameTracker() {
        $artifact_to_link   = array($this->some_artifact, $this->other_artifact);
        $artifact           = mock('Tracker_Artifact');

        $value = array(
            'new_values' => '36,37',
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(0)->returns($artifact_to_link);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(1)->returns(array());

        expect($this->dao)->create()->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value,
            $this->previous_changesetvalue
        );
    }

    public function itUsesArtifactLinkNature() {
        $artifact           = mock('Tracker_Artifact');
        $artifact_to_link   = array($this->some_artifact, $this->other_artifact);

        $value = array(
            'new_values' => '36,37',
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(0)->returns($artifact_to_link);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(1)->returns(array());

        expect($this->dao)->create('*', '_is_child', '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value,
            $this->previous_changesetvalue
        );
    }

    public function itUsesDefaultArtifactLinkNature() {
        $artifact           = mock('Tracker_Artifact');
        $artifact_to_link   = array($this->some_artifact, $this->other_artifact);

        $value = array(
            'new_values' => '36,37',
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker_child);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(0)->returns($artifact_to_link);
        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(36,37))->at(1)->returns(array());

        expect($this->dao)->create('*', NULL, '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value,
            $this->previous_changesetvalue
        );
    }
}

class ArtifactLinkValueSaver_updateLinkingDirectionTest extends TuleapTestCase {

    /** @var ArtifactLinkValueSaver */
    private $saver;

    /** @var SourceOfAssociationDetector */
    private $source_of_association_detector;

    /** @var Tracker_ReferenceManager */
    private $reference_manager;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_FormElement_Field_Value_ArtifactLinkDao */
    private $dao;

    /** @var Tracker_Artifact_ChangesetValue_ArtifactLink */
    private $previous_changesetvalue;

    /** @var Tracker_Artifact */
    private $artifact;

    /** @var Tracker_Artifact */
    private $art_123;

    /** @var Tracker_Artifact */
    private $art_124;

    public function setUp() {
        parent::setUp();

        $this->artifact = anArtifact()->withId(120)->build();
        $this->art_123  = anArtifact()->withId(123)->build();
        $this->art_124  = anArtifact()->withId(124)->build();

        $this->reference_manager = mock('Tracker_ReferenceManager');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->dao               = mock('Tracker_FormElement_Field_Value_ArtifactLinkDao');

        $this->source_of_association_detector = mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector');

        $this->previous_changesetvalue = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($this->previous_changesetvalue)->getArtifactIds()->returns(array());

        stub($this->artifact_factory)->getArtifactsByArtifactIdList(array(123,124))->returns(array($this->art_123, $this->art_124));

        $this->source_of_association_collection = new SourceOfAssociationCollection();
        $this->saver = new ArtifactLinkValueSaver(
            $this->artifact_factory,
            $this->dao,
            $this->reference_manager,
            $this->source_of_association_detector
        );
    }

    public function itRemovesFromSubmittedValuesArtifactsThatWereUpdatedByDirectionChecking() {
        $submitted_value = array('new_values' => '123, 124');

        stub($this->source_of_association_detector)->isChild($this->art_123, $this->artifact)->returns(false);
        stub($this->source_of_association_detector)->isChild($this->art_124, $this->artifact)->returns(true);

        $this->assertEqual(
            $this->saver->updateLinkingDirection(
                $this->source_of_association_collection,
                $this->artifact,
                $submitted_value,
                $this->previous_changesetvalue
            ),
            array('new_values' => '123')
        );
    }
}
