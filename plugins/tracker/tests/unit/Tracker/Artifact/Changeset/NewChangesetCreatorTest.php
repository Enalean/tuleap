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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_Null;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentFormatIdentifier;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveWorkflowStub;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuerStub;

final class NewChangesetCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;

    private const NEW_CHANGESET_ID     = 456;
    private const SUBMISSION_TIMESTAMP = 1234567890;

    private ArtifactChangesetSaver&MockObject $changeset_saver;
    private Artifact $artifact;
    private \Workflow&MockObject $workflow;
    private array $fields_data;
    private ParentLinkAction&MockObject $parent_link_action;
    private TrackerPrivateCommentUGroupPermissionInserter&MockObject $ugroup_private_comment_inserter;
    private \Tracker_Artifact_Changeset_CommentDao&MockObject $comment_dao;
    private SaveArtifactStub $artifact_saver;
    /**
     * @var \ProjectUGroup[]
     */
    private array $ugroups_array;

    protected function setUp(): void
    {
        $this->fields_data = [];

        $changeset = new Tracker_Artifact_Changeset_Null();
        $factory   = $this->createMock(\Tracker_FormElementFactory::class);
        $factory->method('getAllFormElementsForTracker')->willReturn([]);
        $factory->method('getUsedFields')->willReturn([]);

        $this->workflow = $this->createMock(\Workflow::class);
        $this->workflow->method('validate');
        $this->workflow->method('before');
        $this->workflow->method('checkGlobalRules');

        $tracker        = TrackerTestBuilder::aTracker()
            ->withProject(ProjectTestBuilder::aProject()->withId(101)->build())
            ->withWorkflow($this->workflow)
            ->build();
        $this->artifact = ArtifactTestBuilder::anArtifact(15)
            ->withChangesets($changeset)
            ->inTracker($tracker)
            ->build();

        $this->comment_dao = $this->createMock(\Tracker_Artifact_Changeset_CommentDao::class);

        $this->parent_link_action = $this->createMock(ParentLinkAction::class);
        $this->changeset_saver    = $this->createMock(ArtifactChangesetSaver::class);
        $this->artifact_saver     = SaveArtifactStub::withSuccess();

        $this->ugroup_private_comment_inserter = $this->createMock(TrackerPrivateCommentUGroupPermissionInserter::class);

        $this->ugroups_array = [];
    }

    private function create(): void
    {
        $submitter     = UserTestBuilder::aUser()->withId(120)->build();
        $new_changeset = NewChangeset::fromFieldsDataArray(
            $this->artifact,
            $this->fields_data,
            '',
            CommentFormatIdentifier::buildText(),
            $this->ugroups_array,
            $submitter,
            self::SUBMISSION_TIMESTAMP,
            new CreatedFileURLMapping()
        );

        $fields_validator = $this->createMock(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        $fields_validator->method('validate')->willReturn(true);
        $field_retriever = $this->createMock(FieldsToBeSavedInSpecificOrderRetriever::class);
        $field_retriever->method('getFields')->willReturn([]);
        $field_initializator = $this->createMock(Tracker_Artifact_Changeset_ChangesetDataInitializator::class);
        $field_initializator->method('process')->willReturn([]);

        $changeset_comment_indexer = $this->createStub(ChangesetCommentIndexer::class);
        $changeset_comment_indexer->method('indexNewChangesetComment');

        $reference_manager = $this->createMock(\ReferenceManager::class);
        $reference_manager->method('extractCrossRef');
        $event_manager = $this->createMock(\EventManager::class);
        $event_manager->method('processEvent');

        $creator = new NewChangesetCreator(
            $fields_validator,
            $field_retriever,
            $event_manager,
            $field_initializator,
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
            $this->changeset_saver,
            $this->parent_link_action,
            new AfterNewChangesetHandler($this->artifact_saver, $field_retriever),
            PostCreationActionsQueuerStub::doNothing(),
            new ChangesetValueSaver(),
            RetrieveWorkflowStub::withWorkflow($this->workflow),
            new CommentCreator(
                $this->comment_dao,
                $reference_manager,
                $this->ugroup_private_comment_inserter,
                $changeset_comment_indexer,
                new TextValueValidator(),
            ),
        );
        $creator->create($new_changeset, PostCreationContext::withNoConfig(false));
    }

    public function testItCallsTheAfterMethodOnWorkflowWhenCreateNewChangeset(): void
    {
        $this->workflow->expects(self::once())->method('after');
        $this->changeset_saver->expects(self::once())->method('saveChangeset')->willReturn(self::NEW_CHANGESET_ID);
        $this->comment_dao->method('createNewVersion')->willReturn(true);

        $this->create();
    }

    public function testItCallsTheParentLinkActionIfNoChangesDetected(): void
    {
        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(false);

        $tracker        = TrackerTestBuilder::aTracker()->build();
        $this->artifact = $this->createMock(\Tuleap\Tracker\Artifact\Artifact::class);
        $this->artifact->method('getLastChangeset')->willReturn($changeset);
        $this->artifact->method('getId')->willReturn(154);
        $this->artifact->method('getXRef')->willReturn('xRef');
        $this->artifact->method('getTracker')->willReturn($tracker);

        $this->workflow->expects(self::never())->method('after');
        $this->changeset_saver->expects(self::never())->method('saveChangeset');

        $this->parent_link_action->expects(self::once())->method('linkParent')->willReturn(true);

        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFails(): void
    {
        $this->changeset_saver->expects(self::once())->method('saveChangeset')->willReturn(self::NEW_CHANGESET_ID);
        $this->artifact_saver = SaveArtifactStub::withFailure();
        $this->workflow->expects(self::never())->method('after');
        $this->comment_dao->method('createNewVersion')->willReturn(true);

        self::expectException(\Tracker_AfterSaveException::class);
        $this->create();
    }

    public function testItDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFailsOnNewChangeset(): void
    {
        $this->workflow->expects(self::never())->method('after');
        $this->changeset_saver->expects(self::once())->method('saveChangeset')
            ->willThrowException(new \Tracker_Artifact_Exception_CannotCreateNewChangeset());

        self::expectException(\Tracker_ChangesetNotCreatedException::class);
        $this->create();
    }

    public function testItSavesUgroupPrivateComment(): void
    {
        $ugroup = $this->createMock(\ProjectUGroup::class);
        $this->comment_dao->expects(self::once())->method('createNewVersion')->willReturn(15);

        $this->ugroup_private_comment_inserter
            ->expects(self::once())
            ->method('insertUGroupsOnPrivateComment')
            ->with(15, [$ugroup]);

        $this->workflow->expects(self::once())->method('after');
        $this->changeset_saver->expects(self::once())->method('saveChangeset')->willReturn(self::NEW_CHANGESET_ID);
        $this->ugroups_array = [$ugroup];

        $this->create();
    }
}
