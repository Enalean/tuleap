<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact\Changeset;

use Mockery;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_Comment;
use Tracker_Artifact_Changeset_Null;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\ActionsRunner;
use Tuleap\Tracker\Artifact\Changeset\Value\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\Test\Stub\RetrieveWorkflowStub;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;

final class NewChangesetCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    private const NEW_CHANGESET_ID = 456;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ArtifactChangesetSaver
     */
    private $changeset_saver;

    /**
     * @var Mockery\Mock | \Tuleap\Tracker\Artifact\Artifact
     */
    private $artifact;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\Workflow
     */
    private $workflow;
    /**
     * @var array
     */
    private $fields_data;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ParentLinkAction
     */
    private $parent_link_action;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerPrivateCommentUGroupPermissionInserter
     */
    private $ugroup_private_comment_inserter;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker_Artifact_Changeset_CommentDao
     */
    private $comment_dao;
    private SaveArtifactStub $artifact_saver;
    /**
     * @var \ProjectUGroup[]
     */
    private array $ugroups_array;

    protected function setUp(): void
    {
        $this->fields_data = [];

        $changeset = new Tracker_Artifact_Changeset_Null();
        $factory   = \Mockery::spy(\Tracker_FormElementFactory::class);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturn([]);
        $factory->shouldReceive('getUsedFields')->andReturn([]);

        $this->workflow = \Mockery::spy(\Workflow::class);

        $tracker = Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getProject')->andReturn(new \Project(['group_id' => 101]));
        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $this->artifact->shouldReceive('getWorkflow')->andReturn($this->workflow);
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->comment_dao = Mockery::mock(\Tracker_Artifact_Changeset_CommentDao::class);
        $this->comment_dao->shouldReceive('createNewVersion')->andReturn(true)->byDefault();

        $this->parent_link_action = Mockery::mock(ParentLinkAction::class);
        $this->changeset_saver    = Mockery::mock(ArtifactChangesetSaver::class);
        $this->artifact_saver     = SaveArtifactStub::withSuccess();

        $this->ugroup_private_comment_inserter = Mockery::mock(TrackerPrivateCommentUGroupPermissionInserter::class);

        $this->ugroups_array = [];
    }

    private function create(): void
    {
        $submitter        = UserTestBuilder::aUser()->withId(120)->build();
        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        $fields_validator->shouldReceive('validate')->andReturn(true);
        $field_retriever = Mockery::mock(FieldsToBeSavedInSpecificOrderRetriever::class);
        $field_retriever->shouldReceive('getFields')->andReturn([]);
        $field_initializator = Mockery::mock(Tracker_Artifact_Changeset_ChangesetDataInitializator::class);
        $field_initializator->shouldReceive('process')->andReturn([]);

        $creator = new NewChangesetCreator(
            $fields_validator,
            $field_retriever,
            $this->comment_dao,
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(\ReferenceManager::class),
            $field_initializator,
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
            $this->changeset_saver,
            $this->parent_link_action,
            $this->ugroup_private_comment_inserter,
            new AfterNewChangesetHandler($this->artifact_saver, $field_retriever),
            Mockery::spy(ActionsRunner::class),
            new ChangesetValueSaver(),
            RetrieveWorkflowStub::withWorkflow($this->workflow)
        );

        $creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $submitter,
            1234567890,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class),
            new TrackerNoXMLImportLoggedConfig(),
            $this->ugroups_array
        );
    }

    public function testItCallsTheAfterMethodOnWorkflowWhenCreateNewChangeset(): void
    {
        $this->workflow->shouldReceive('after')->once();
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(self::NEW_CHANGESET_ID);

        $this->create();
    }

    public function testItCallsTheParentLinkActionIfNoChangesDetected(): void
    {
        $changeset = Mockery::mock(Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturnFalse();

        $tracker        = Mockery::spy(\Tracker::class);
        $this->artifact = Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->shouldReceive('getLastChangeset')->andReturn($changeset);
        $this->artifact->shouldReceive('getId')->andReturn(154);
        $this->artifact->shouldReceive('getXRef')->andReturn('xRef');
        $this->artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->workflow->shouldNotReceive('after');
        $this->changeset_saver->shouldNotReceive('saveChangeset');

        $this->parent_link_action->shouldReceive('linkParent')->once()->andReturnTrue();

        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFails(): void
    {
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(self::NEW_CHANGESET_ID);
        $this->artifact_saver = SaveArtifactStub::withFailure();
        $this->workflow->shouldReceive('after')->never();

        $this->expectException(\Tracker_AfterSaveException::class);
        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFailsOnNewChangeset(): void
    {
        $this->workflow->shouldReceive('after')->never();
        $this->changeset_saver->shouldReceive('saveChangeset')
            ->once()
            ->andThrows(new \Tracker_Artifact_Exception_CannotCreateNewChangeset());

        $this->expectException(\Tracker_ChangesetNotCreatedException::class);
        $this->create();
    }

    public function testItSavesUgroupPrivateComment(): void
    {
        $ugroup = Mockery::mock(\ProjectUGroup::class);
        $this->comment_dao->shouldReceive('createNewVersion')->once()->andReturn(15);

        $this->ugroup_private_comment_inserter
            ->shouldReceive('insertUGroupsOnPrivateComment')
            ->with(15, [$ugroup])
            ->once();

        $this->workflow->shouldReceive('after')->once();
        $this->changeset_saver->shouldReceive('saveChangeset')->once()->andReturns(self::NEW_CHANGESET_ID);
        $this->ugroups_array = [$ugroup];

        $this->create();
    }
}
