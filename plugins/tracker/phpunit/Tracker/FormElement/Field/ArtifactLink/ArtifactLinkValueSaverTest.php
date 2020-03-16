<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactLinkInfo;
use Tracker_ArtifactFactory;

require_once __DIR__ . '/../../../../bootstrap.php';

class ArtifactLinkValueSaverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->field             = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);
        $this->reference_manager = \Mockery::spy(\Tracker_ReferenceManager::class);
        $this->artifact_factory  = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->dao               = \Mockery::spy(\Tracker_FormElement_Field_Value_ArtifactLinkDao::class);

        $project = \Mockery::spy(\Project::class, ['getID' => 101, 'getUnixName' => false, 'isPublic' => false]);

        $this->tracker       = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $this->tracker_child = \Mockery::spy(\Tracker::class)->shouldReceive('getId')->andReturns(101)->getMock();

        $this->tracker->shouldReceive('getChildren')->andReturns(array($this->tracker_child));
        $this->tracker_child->shouldReceive('getChildren')->andReturns(array());

        $this->tracker->shouldReceive('getProject')->andReturns($project);
        $this->tracker_child->shouldReceive('getProject')->andReturns($project);

        $this->initial_linked_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->initial_linked_artifact->shouldReceive('getId')->andReturns(36);
        $this->initial_linked_artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->initial_linked_artifact->shouldReceive('getLastChangeset')->andReturns(\Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(361)->getMock());
        $this->artifact_factory->shouldReceive('getArtifactById')->with(36)->andReturns($this->initial_linked_artifact);

        $this->some_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->some_artifact->shouldReceive('getId')->andReturns(456);
        $this->some_artifact->shouldReceive('getTracker')->andReturns($this->tracker_child);
        $this->some_artifact->shouldReceive('getLastChangeset')->andReturns(\Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(4561)->getMock());
        $this->artifact_factory->shouldReceive('getArtifactById')->with(456)->andReturns($this->some_artifact);

        $this->other_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->other_artifact->shouldReceive('getId')->andReturns(457);
        $this->other_artifact->shouldReceive('getTracker')->andReturns($this->tracker_child);
        $this->other_artifact->shouldReceive('getLastChangeset')->andReturns(\Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(4571)->getMock());
        $this->artifact_factory->shouldReceive('getArtifactById')->with(457)->andReturns($this->other_artifact);

        $this->another_artifact = \Mockery::spy(\Tracker_Artifact::class);
        $this->another_artifact->shouldReceive('getId')->andReturns(458);
        $this->another_artifact->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->another_artifact->shouldReceive('getLastChangeset')->andReturns(\Mockery::spy(\Tracker_Artifact_Changeset::class)->shouldReceive('getId')->andReturns(4581)->getMock());
        $this->artifact_factory->shouldReceive('getArtifactById')->with(458)->andReturns($this->another_artifact);

        $this->previous_changesetvalue = \Mockery::spy(\Tracker_Artifact_ChangesetValue_ArtifactLink::class);
        $this->previous_changesetvalue->shouldReceive('getArtifactIds')->andReturns(array(36));

        $this->user = new PFUser([
            'language_id' => 'en',
            'user_id' => 101
        ]);

        $this->artifact_link_usage_dao = \Mockery::spy(\Tuleap\Tracker\Admin\ArtifactLinksUsageDao::class);

        $this->saver = new ArtifactLinkValueSaver(
            $this->artifact_factory,
            $this->dao,
            $this->reference_manager,
            \Mockery::spy(\EventManager::class),
            $this->artifact_link_usage_dao
        );

        Tracker_ArtifactFactory::setInstance($this->artifact_factory);
    }

    protected function tearDown(): void
    {
        Tracker_ArtifactFactory::clearInstance();
        parent::tearDown();
    }

    public function testItRemovesACrossReference(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getTracker')->andReturns($this->tracker)->getMock();

        $value = array(
            'list_of_artifactlinkinfo' => array(),
            'removed_values' => array(
                36 => 1
            )
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->artifact_factory->shouldReceive('getArtifactsByArtifactIdList')->with(array())->ordered()->andReturns(array());
        $this->artifact_factory->shouldReceive('getArtifactsByArtifactIdList')->with(array(36))->ordered()->andReturns(array($this->initial_linked_artifact));

        $this->reference_manager->shouldReceive('removeBetweenTwoArtifacts')->with($artifact, $this->initial_linked_artifact, $this->user)->once();
        $this->dao->shouldReceive('create')->never();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItAddsACrossReference(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class)->shouldReceive('getTracker')->andReturns($this->tracker)->getMock();

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->initial_linked_artifact, 'fixed_in')
            ),
            'removed_values' => array()
        );

        $this->dao->shouldReceive('create')->once()->andReturns(true);
        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->reference_manager->shouldReceive('insertBetweenTwoArtifacts')->with($artifact, $this->initial_linked_artifact, $this->user)->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItCallsOnlyOneTimeCreateInDBIfAllArtifactsAreInTheSameTracker(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, 'fixed_in'),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, 'fixed_in')
            ),
            'removed_values' => array()
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->dao->shouldReceive('create')->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItUsesArtifactLinkNature(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, 'fixed_in'),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, 'fixed_in')
            ),
            'removed_values' => array()
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->dao->shouldReceive('create')->with(\Mockery::any(), '_is_child', \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItUsesDefaultArtifactLinkNature(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, '')
            ),
            'removed_values' => array()
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker_child);

        $this->dao->shouldReceive('create')->with(\Mockery::any(), null, \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItUsesIsChildArtifactLinkTypeIfAHierarchyIsDefined(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, '')
            ),
            'removed_values' => array()
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->artifact_link_usage_dao->shouldReceive('isTypeDisabledInProject')->with(101, '_is_child')->andReturns(false);

        $this->dao->shouldReceive('create')->with(\Mockery::any(), '_is_child', \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItDoesNotUseIsChildArtifactLinkTypeIfTargetTrackerIsNotChildInHierarchy(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->another_artifact, '_is_child')
            ),
            'removed_values' => array()
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);

        $this->dao->shouldReceive('create')->with(\Mockery::any(), null, \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }

    public function testItDoesNotUseIsChildArtifactLinkTypeIfTypeIsDisabled(): void
    {
        $artifact = \Mockery::spy(\Tracker_Artifact::class);

        $value = array(
            'list_of_artifactlinkinfo' => array(
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->some_artifact, ''),
                Tracker_ArtifactLinkInfo::buildFromArtifact($this->other_artifact, '')
            ),
            'removed_values' => array()
        );

        $this->field->shouldReceive('getTracker')->andReturns($this->tracker);
        $this->artifact_link_usage_dao->shouldReceive('isTypeDisabledInProject')->with(101, '_is_child')->andReturns(true);

        $this->dao->shouldReceive('create')->with(\Mockery::any(), null, \Mockery::any(), \Mockery::any(), \Mockery::any())->once();

        $this->saver->saveValue(
            $this->field,
            $this->user,
            $artifact,
            $this->changeset_value_id,
            $value
        );
    }
}
