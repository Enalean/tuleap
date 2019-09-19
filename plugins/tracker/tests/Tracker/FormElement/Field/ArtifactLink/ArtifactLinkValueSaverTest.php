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
use Tracker_ArtifactLinkInfo;
use Tracker_ArtifactFactory;

require_once __DIR__.'/../../../../bootstrap.php';

class ArtifactLinkValueSaverTest extends TuleapTestCase
{

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

    public function setUp()
    {
        parent::setUp();

        $this->field             = mock('Tracker_FormElement_Field_ArtifactLink');
        $this->reference_manager = mock('Tracker_ReferenceManager');
        $this->artifact_factory  = mock('Tracker_ArtifactFactory');
        $this->dao               = mock('Tracker_FormElement_Field_Value_ArtifactLinkDao');

        $project = aMockProject()->withId(101)->build();

        $this->tracker       = stub('Tracker')->getId()->returns(102);
        $this->tracker_child = stub('Tracker')->getId()->returns(101);

        stub($this->tracker)->getChildren()->returns(array($this->tracker_child));
        stub($this->tracker_child)->getChildren()->returns(array());

        stub($this->tracker)->getProject()->returns($project);
        stub($this->tracker_child)->getProject()->returns($project);

        $this->initial_linked_artifact = mock('Tracker_Artifact');
        stub($this->initial_linked_artifact)->getId()->returns(36);
        stub($this->initial_linked_artifact)->getTracker()->returns($this->tracker);
        stub($this->initial_linked_artifact)->getLastChangeset()->returns(
            stub('Tracker_Artifact_Changeset')->getId()->returns(361)
        );
        stub($this->artifact_factory)->getArtifactById(36)->returns($this->initial_linked_artifact);

        $this->some_artifact = mock('Tracker_Artifact');
        stub($this->some_artifact)->getId()->returns(456);
        stub($this->some_artifact)->getTracker()->returns($this->tracker_child);
        stub($this->some_artifact)->getLastChangeset()->returns(
            stub('Tracker_Artifact_Changeset')->getId()->returns(4561)
        );
        stub($this->artifact_factory)->getArtifactById(456)->returns($this->some_artifact);

        $this->other_artifact = mock('Tracker_Artifact');
        stub($this->other_artifact)->getId()->returns(457);
        stub($this->other_artifact)->getTracker()->returns($this->tracker_child);
        stub($this->other_artifact)->getLastChangeset()->returns(
            stub('Tracker_Artifact_Changeset')->getId()->returns(4571)
        );
        stub($this->artifact_factory)->getArtifactById(457)->returns($this->other_artifact);

        $this->another_artifact = mock('Tracker_Artifact');
        stub($this->another_artifact)->getId()->returns(458);
        stub($this->another_artifact)->getTracker()->returns($this->tracker);
        stub($this->another_artifact)->getLastChangeset()->returns(
            stub('Tracker_Artifact_Changeset')->getId()->returns(4581)
        );
        stub($this->artifact_factory)->getArtifactById(458)->returns($this->another_artifact);

        $this->previous_changesetvalue = mock('Tracker_Artifact_ChangesetValue_ArtifactLink');
        stub($this->previous_changesetvalue)->getArtifactIds()->returns(array(36));

        $this->user = aUser()->withId(101)->build();

        $this->artifact_link_usage_dao = mock('Tuleap\Tracker\Admin\ArtifactLinksUsageDao');

        $this->saver = new ArtifactLinkValueSaver(
            $this->artifact_factory,
            $this->dao,
            $this->reference_manager,
            \Mockery::spy(\EventManager::class),
            $this->artifact_link_usage_dao
        );

        Tracker_ArtifactFactory::setInstance($this->artifact_factory);
    }

    public function tearDown()
    {
        Tracker_ArtifactFactory::clearInstance();
        parent::tearDown();
    }

    public function itRemovesACrossReference()
    {
        $artifact = stub('Tracker_Artifact')->getTracker()->returns($this->tracker);

        $value = array(
            'list_of_artifactlinkinfo' => array(),
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
            $value
        );
    }

    public function itAddsACrossReference()
    {
        $artifact = stub('Tracker_Artifact')->getTracker()->returns($this->tracker);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->initial_linked_artifact, 'fixed_in')
            ),
            'removed_values' => array()
        );

        stub($this->dao)->create()->returns(true);
        stub($this->field)->getTracker()->returns($this->tracker);

        expect($this->reference_manager)->insertBetweenTwoArtifacts($artifact, $this->initial_linked_artifact, $this->user)->once();
        expect($this->dao)->create()->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function itCallsOnlyOneTimeCreateInDBIfAllArtifactsAreInTheSameTracker()
    {
        $artifact = mock('Tracker_Artifact');

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, 'fixed_in'),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, 'fixed_in')
            ),
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);

        expect($this->dao)->create()->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function itUsesArtifactLinkNature()
    {
        $artifact = mock('Tracker_Artifact');

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, 'fixed_in'),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, 'fixed_in')
            ),
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);

        expect($this->dao)->create('*', '_is_child', '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function itUsesDefaultArtifactLinkNature()
    {
        $artifact = mock('Tracker_Artifact');

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, '')
            ),
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker_child);

        expect($this->dao)->create('*', null, '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function itUsesIsChildArtifactLinkTypeIfAHierarchyIsDefined()
    {
        $artifact = mock('Tracker_Artifact');

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, '')
            ),
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);
        stub($this->artifact_link_usage_dao)->isTypeDisabledInProject(101, '_is_child')->returns(false);

        expect($this->dao)->create('*', '_is_child', '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function itDoesNotUseIsChildArtifactLinkTypeIfTargetTrackerIsNotChildInHierarchy()
    {
        $artifact = mock('Tracker_Artifact');

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->another_artifact, '_is_child')
            ),
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);

        expect($this->dao)->create('*', null, '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function itDoesNotUseIsChildArtifactLinkTypeIfTypeIsDisabled()
    {
        $artifact = mock('Tracker_Artifact');

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, '')
            ),
            'removed_values' => array()
        );

        stub($this->field)->getTracker()->returns($this->tracker);
        stub($this->artifact_link_usage_dao)->isTypeDisabledInProject(101, '_is_child')->returns(true);

        expect($this->dao)->create('*', null, '*', '*', '*')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }
}
