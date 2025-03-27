<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use LogicException;
use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Project;
use ReferenceManager;
use SimpleXMLElement;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_Changeset_CommentDao;
use Tracker_Artifact_Changeset_NewChangesetFieldsValidator;
use Tracker_Artifact_ChangesetDao;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_ArtifactFactory;
use Tracker_Exception;
use Tracker_FormElement_Field;
use Tracker_FormElement_Field_File;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use Tracker_HierarchyFactory;
use Tracker_NoChangeException;
use Tracker_Workflow_GlobalRulesViolationException;
use Tuleap\GlobalResponseMock;
use Tuleap\Notification\Mention\MentionedUserInTextRetriever;
use Tuleap\Project\XML\Export\ArchiveInterface;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\DBTransactionExecutorPassthrough;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\ProvideAndRetrieveUserStub;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetFieldValueSaver;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetPostProcessor;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetValidator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\XML\Exporter\ArtifactXMLExporterBuilder;
use Tuleap\Tracker\Artifact\XML\Exporter\InArchiveFilePathXMLExporter;
use Tuleap\Tracker\Artifact\XML\Exporter\NullChildrenCollector;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetCommentTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueDateTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueTextTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Artifact\Changeset\PostCreation\PostCreationActionsQueuerStub;
use Tuleap\Tracker\Test\Stub\RetrieveWorkflowStub;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use UserXMLExportedCollection;
use UserXMLExporter;
use Workflow;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_ArtifactTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use GlobalResponseMock;

    protected function tearDown(): void
    {
        Tracker_FormElementFactory::clearInstance();
    }

    public function testLastChangesetIsRetrieved(): void
    {
        $artifact = $this->createPartialMock(Artifact::class, ['getChangesetFactory']);

        $changeset = ChangesetTestBuilder::aChangeset(48164)->build();

        $changeset_factory = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $changeset_factory->expects($this->once())->method('getLastChangeset')->willReturn($changeset);

        $artifact->expects($this->once())->method('getChangesetFactory')->willReturn($changeset_factory);

        self::assertSame($changeset, $artifact->getLastChangeset());
        self::assertSame($changeset, $artifact->getLastChangeset());
    }

    public function testLastChangesetIsRetrievedWhenAllChangesetsHaveAlreadyBeenLoaded(): void
    {
        $last_changeset = ChangesetTestBuilder::aChangeset(3)->build();

        $changesets = [
            ChangesetTestBuilder::aChangeset(1)->build(),
            ChangesetTestBuilder::aChangeset(2)->build(),
        ];
        $artifact   = ArtifactTestBuilder::anArtifact(25)->withChangesets($last_changeset, ...$changesets)->build();

        self::assertSame($last_changeset, $artifact->getLastChangeset());
        self::assertSame($last_changeset, $artifact->getLastChangeset());
    }

    public function testGetValue(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(1534)->build();
        $field     = DateFieldBuilder::aDateField(1465)->build();
        $value     = ChangesetValueDateTestBuilder::aValue(1, $changeset, $field)->build();
        $changeset->setFieldValue($field, $value);

        $id       = $tracker_id = $use_artifact_permissions = $submitted_by = $submitted_on = '';
        $artifact = new Artifact($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions);

        self::assertEquals($value, $artifact->getValue($field, $changeset));
    }

    public function testGetValueWithoutChangeset(): void
    {
        $changeset = ChangesetTestBuilder::aChangeset(1534)->build();
        $field     = DateFieldBuilder::aDateField(1465)->build();
        $value     = ChangesetValueDateTestBuilder::aValue(1, $changeset, $field)->build();
        $changeset->setFieldValue($field, $value);

        $artifact = ArtifactTestBuilder::anArtifact(2684555)->withChangesets($changeset)->build();

        self::assertEquals($value, $artifact->getValue($field));
    }

    public function testCreateNewChangesetWithWorkflowAndNoPermsOnPostActionField(): void
    {
        $comment_dao = $this->createMock(Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->expects($this->once())->method('createNewVersion')->willReturn(true);

        $dao = $this->createMock(Tracker_Artifact_ChangesetDao::class);
        $dao->method('create')
            ->with(66, 1234, null, $_SERVER['REQUEST_TIME'], self::isInstanceOf(TrackerNoXMLImportLoggedConfig::class))
            ->willReturnOnConsecutiveCalls(1001, 1002);

        $user = UserTestBuilder::anActiveUser()->withId(1234)->build();

        $tracker = TrackerTestBuilder::aTracker()->withProject(new Project(['group_id' => 101]))->build();

        $factory = $this->createMock(Tracker_FormElementFactory::class);

        $artifact = $this->createPartialMock(Artifact::class, ['getWorkflow', 'getWorkflowUpdateChecker', 'getTracker', 'getId', 'getLastChangeset', 'getChangeset']);
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::exactly(2))->method('before');
        $workflow->method('after');
        $workflow->method('validate');
        $workflow->method('checkGlobalRules');
        $artifact->method('getWorkflow')->willReturn($workflow);

        $workflow_checker = $this->createMock(WorkflowUpdateChecker::class);
        $workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $artifact->method('getWorkflowUpdateChecker')->willReturn($workflow_checker);

        $fields_mock_methods = ['getId', 'isValid', 'userCanUpdate', 'saveNewChangeset'];
        $field1              = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field1->method('getId')->willReturn(101);
        $field1->method('isValid')->willReturn(true);
        $field1->method('userCanUpdate')->willReturn(true);
        $field1->expects($this->once())->method('saveNewChangeset')->willReturn(true);

        $field2 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field2->method('getId')->willReturn(102);
        $field2->method('isValid')->willReturn(true);
        $field2->method('userCanUpdate')->willReturn(false);
        $field2->expects($this->once())->method('saveNewChangeset')->with(
            self::anything(),
            self::anything(),
            self::anything(),
            self::anything(),
            $user,
            false,
            true,
            self::isInstanceOf(CreatedFileURLMapping::class)
        )->willReturn(true);
        $workflow->method('bypassPermissions')
            ->willReturnCallback(static fn(Tracker_FormElement_Field $field) => $field === $field2);
        $factory->method('getUsedFields')->willReturn([$field1, $field2]);
        $factory->method('getAllFormElementsForTracker')->willReturn([]);
        $factory->method('isFieldAFileField')
            ->willReturnCallback(static fn(Tracker_FormElement_Field $field) => $field::class === Tracker_FormElement_Field_File::class);

        $new_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $new_changeset->method('executePostCreationActions')->with([true]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('getValues')->willReturn([]);
        $changeset->method('hasChanges')->willReturn(true);
        $changeset_value1 = ChangesetValueTextTestBuilder::aValue(1, $changeset, $field1)->build();
        $changeset->method('getValue')->with($field1)->willReturn($changeset_value1);

        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager->method('extractCrossRef')->with(
            '',
            66,
            'plugin_tracker_artifact',
            101,
            $user->getId(),
            'irrelevant',
        );

        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(66);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $artifact->method('getChangeset')->willReturn($new_changeset);

        // Valid
        $fields_data = [
            101 => '123',
            102 => '456',
        ];

        $submitted_on       = $_SERVER['REQUEST_TIME'];
        $changeset_creation = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            $artifact,
            $fields_data,
            $user,
            $submitted_on,
        );

        $fields_validator = $this->createMock(Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        $fields_validator->method('validate')->willReturn(true);

        $artifact_saver = $this->createMock(ArtifactChangesetSaver::class);
        $artifact_saver->expects($this->once())->method('saveChangeset');
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($factory);

        $changeset_comment_indexer = $this->createStub(ChangesetCommentIndexer::class);
        $changeset_comment_indexer->method('indexNewChangesetComment');

        $creator = new NewChangesetCreator(
            new DBTransactionExecutorPassthrough(),
            $artifact_saver,
            new AfterNewChangesetHandler(
                SaveArtifactStub::withSuccess(),
                $fields_retriever,
            ),
            RetrieveWorkflowStub::withWorkflow($workflow),
            new CommentCreator(
                $comment_dao,
                $reference_manager,
                $this->createStub(TrackerPrivateCommentUGroupPermissionInserter::class),
                new TextValueValidator(),
            ),
            new NewChangesetFieldValueSaver(
                $fields_retriever,
                new ChangesetValueSaver(),
            ),
            new NewChangesetValidator(
                $fields_validator,
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($factory),
                $this->createStub(ParentLinkAction::class),
            ),
            new NewChangesetPostProcessor(
                EventDispatcherStub::withIdentityCallback(),
                PostCreationActionsQueuerStub::doNothing(),
                $changeset_comment_indexer,
                new MentionedUserInTextRetriever(ProvideAndRetrieveUserStub::build($user)),
            ),
        );
        $creator->create($changeset_creation, PostCreationContext::withNoConfig(false));
    }

    public function testDontCreateNewChangesetIfNoCommentOrNoChanges(): void
    {
        $comment_dao = $this->createMock(Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->expects(self::never())->method('createNewVersion');

        $user = UserTestBuilder::anActiveUser()->withId(1234)->build();

        $tracker = TrackerTestBuilder::aTracker()->build();
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($factory);

        $fields_mock_methods = ['getId', 'isValid', 'isValidRegardingRequiredProperty', 'userCanUpdate', 'saveNewChangeset'];
        $field1              = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field1->method('getId')->willReturn(101);
        $field1->method('isValid')->willReturn(true);
        $field1->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field1->method('userCanUpdate')->willReturn(true);
        $field1->expects(self::never())->method('saveNewChangeset');
        $field2 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field2->method('getId')->willReturn(102);
        $field2->method('isValid')->willReturn(true);
        $field2->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field2->method('userCanUpdate')->willReturn(true);
        $field2->expects(self::never())->method('saveNewChangeset');
        $field3 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field3->method('getId')->willReturn(103);
        $field3->method('isValid')->willReturn(true);
        $field3->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field3->method('userCanUpdate')->willReturn(true);
        $field3->expects(self::never())->method('saveNewChangeset');
        $factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);
        $factory->method('getAllFormElementsForTracker')->willReturn([]);
        $factory->method('getUsedArtifactLinkFields')->willReturn([]);
        $factory->method('getAnArtifactLinkField')->willReturn(null);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(false);
        $changeset->method('getValues')->willReturn([]);
        $changeset->method('getValue')->willReturnCallback(static fn(Tracker_FormElement_Field $field) => match ($field) {
            $field1 => ChangesetValueTextTestBuilder::aValue(1, $changeset, $field1)->build(),
            $field2 => ChangesetValueTextTestBuilder::aValue(2, $changeset, $field2)->build(),
            $field3 => ChangesetValueTextTestBuilder::aValue(3, $changeset, $field3)->build(),
        });

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->willReturn([]);

        $artifact = $this->createPartialMock(Artifact::class, ['getChangesetCommentDao', 'getFormElementFactory', 'getArtifactFactory', 'getHierarchyFactory', 'getReferenceManager', 'getTracker', 'getId', 'getLastChangeset', 'getActionsQueuer', 'getWorkflowUpdateChecker', 'getWorkflow', 'getWorkflowRetriever']);
        $artifact->setTransactionExecutorForTests(new DBTransactionExecutorPassthrough());
        $artifact->method('getChangesetCommentDao')->willReturn($comment_dao);
        $artifact->method('getFormElementFactory')->willReturn($factory);
        $artifact->method('getArtifactFactory')->willReturn($this->createStub(Tracker_ArtifactFactory::class));
        $artifact->method('getHierarchyFactory')->willReturn($hierarchy_factory);
        $artifact->method('getReferenceManager')->willReturn($this->createStub(ReferenceManager::class));
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(66);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $artifact->method('getActionsQueuer')->willReturn(PostCreationActionsQueuerStub::doNothing());

        $workflow_checker = $this->createMock(WorkflowUpdateChecker::class);
        $workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $artifact->method('getWorkflowUpdateChecker')->willReturn($workflow_checker);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::never())->method('before');
        $workflow->method('validate');
        $artifact->method('getWorkflow')->willReturn($workflow);
        $artifact->method('getWorkflowRetriever')->willReturn(RetrieveWorkflowStub::withWorkflow($workflow));

        $comment = ''; //empty comment

        // Valid
        $fields_data = [];
        $this->expectException(Tracker_NoChangeException::class);
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testCreateNewChangeset(): void
    {
        $comment = 'It did solve my problem, I let you close the artifact.';

        $comment_dao = $this->createMock(Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->expects($this->once())->method('createNewVersion')->willReturn(true);

        $changeset_saver = $this->createMock(ArtifactChangesetSaver::class);
        $changeset_saver->method('saveChangeset')->willReturn(1002);

        $user = UserTestBuilder::anActiveUser()->withId(1234)->build();

        $tracker = TrackerTestBuilder::aTracker()->withProject(new Project(['group_id' => 101]))->build();

        $factory = $this->createMock(Tracker_FormElementFactory::class);

        $fields_mock_methods = ['getId', 'isValid', 'isValidRegardingRequiredProperty', 'userCanUpdate', 'saveNewChangeset'];
        $field1              = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field1->method('getId')->willReturn(101);
        $field1->method('isValid')->willReturn(true);
        $field1->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field1->method('userCanUpdate')->willReturn(true);
        $field1->expects($this->once())->method('saveNewChangeset')->willReturn(true);
        $field2 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field2->method('getId')->willReturn(102);
        $field2->method('isValid')->willReturnCallback(static fn(Artifact $artifact, mixed $value) => $value === '123');
        $field2->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field2->method('userCanUpdate')->willReturn(true);
        $field2->expects($this->once())->method('saveNewChangeset')->willReturn(true);
        $field3 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field3->method('getId')->willReturn(103);
        $field3->method('isValid')->willReturn(true);
        $field3->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field3->expects($this->once())->method('saveNewChangeset')->willReturn(true);
        $field3->method('userCanUpdate')->willReturn(true);
        $factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);
        $factory->method('getAllFormElementsForTracker')->willReturn([]);
        $factory->method('isFieldAFileField')
            ->willReturnCallback(static fn(Tracker_FormElement_Field $field) => $field::class === Tracker_FormElement_Field_File::class);

        $new_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $new_changeset->method('executePostCreationActions')->with([true]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(true);
        $changeset->method('getValues')->willReturn([]);
        $changeset->method('getValue')->willReturnCallback(static fn(Tracker_FormElement_Field $field) => match ($field) {
            $field1 => ChangesetValueTextTestBuilder::aValue(1, $changeset, $field1)->build(),
            $field2 => ChangesetValueTextTestBuilder::aValue(2, $changeset, $field2)->build(),
            $field3 => ChangesetValueTextTestBuilder::aValue(3, $changeset, $field3)->build(),
        });

        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager->method('extractCrossRef')->with(
            $comment,
            66,
            'plugin_tracker_artifact',
            101,
            $user->getId(),
            'irrelevant',
        );

        $art_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->willReturn([]);

        $artifact = $this->createPartialMock(Artifact::class, ['getChangesetCommentDao', 'getFormElementFactory', 'getArtifactFactory', 'getHierarchyFactory', 'getReferenceManager', 'getTracker', 'getId', 'getLastChangeset', 'getActionsQueuer', 'getWorkflowUpdateChecker', 'getWorkflow', 'getWorkflowRetriever', 'getChangeset', 'getChangesetSaver', 'getUserManager']);
        $artifact->setTransactionExecutorForTests(new DBTransactionExecutorPassthrough());
        $artifact->method('getChangesetCommentDao')->willReturn($comment_dao);
        $artifact->method('getFormElementFactory')->willReturn($factory);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(66);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $artifact->method('getChangeset')->willReturn($new_changeset);
        $artifact->method('getReferenceManager')->willReturn($reference_manager);
        $artifact->method('getArtifactFactory')->willReturn($art_factory);
        $artifact->method('getHierarchyFactory')->willReturn($hierarchy_factory);
        $artifact->method('getChangesetSaver')->willReturn($changeset_saver);
        $artifact->method('getActionsQueuer')->willReturn(PostCreationActionsQueuerStub::doNothing());
        $artifact->method('getUserManager')->willReturn(ProvideAndRetrieveUserStub::build($user));

        $workflow_checker = $this->createMock(WorkflowUpdateChecker::class);
        $workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $artifact->method('getWorkflowUpdateChecker')->willReturn($workflow_checker);

        $GLOBALS['Response']->method('getFeedbackErrors')->willReturn([]);

        $art_factory->expects($this->once())->method('save')->willReturn(true);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::exactly(2))->method('before');
        $workflow->method('after');
        $workflow->method('validate');
        $workflow->method('checkGlobalRules');
        $artifact->method('getWorkflow')->willReturn($workflow);
        $artifact->method('getWorkflowRetriever')->willReturn(RetrieveWorkflowStub::withWorkflow($workflow));

        // Valid
        $fields_data = [
            102 => '123',
        ];

        $artifact->createNewChangeset($fields_data, $comment, $user);

        // Not valid
        $fields_data = [
            102 => '456',
        ];

        $this->expectException(Tracker_Exception::class);

        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testItCheckThatGlobalRulesAreValid(): void
    {
        $comment = 'It did solve my problem, I let you close the artifact.';

        $comment_dao = $this->createMock(Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->expects(self::never())->method('createNewVersion');

        $user = UserTestBuilder::anActiveUser()->withId(1234)->build();

        $project = ProjectTestBuilder::aProject()->withId(666)->build();
        $tracker = TrackerTestBuilder::aTracker()->withName('foobar')->withProject($project)->build();
        $factory = $this->createMock(Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($factory);

        $fields_mock_methods = ['getId', 'isValid', 'isValidRegardingRequiredProperty', 'userCanUpdate', 'saveNewChangeset'];
        $field1              = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field1->method('getId')->willReturn(101);
        $field1->method('isValid')->willReturn(true);
        $field1->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field1->method('userCanUpdate')->willReturn(true);
        $field1->expects(self::never())->method('saveNewChangeset');
        $field2 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field2->method('getId')->willReturn(102);
        $field2->method('isValid')->willReturnCallback(static fn(Artifact $artifact, mixed $value) => $value === '123');
        $field2->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field2->method('userCanUpdate')->willReturn(true);
        $field2->expects(self::never())->method('saveNewChangeset');
        $field3 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field3->method('getId')->willReturn(103);
        $field3->method('isValid')->willReturn(true);
        $field3->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field3->method('userCanUpdate')->willReturn(true);
        $field3->expects(self::never())->method('saveNewChangeset');
        $factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);
        $factory->method('getAllFormElementsForTracker')->willReturn([]);

        $new_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $new_changeset->method('executePostCreationActions')->with([false]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(true);
        $changeset->method('getValues')->willReturn([]);
        $changeset->method('getValue')->willReturnCallback(static fn(Tracker_FormElement_Field $field) => match ($field) {
            $field1 => ChangesetValueTextTestBuilder::aValue(1, $changeset, $field1)->build(),
            $field2 => ChangesetValueTextTestBuilder::aValue(2, $changeset, $field2)->build(),
            $field3 => ChangesetValueTextTestBuilder::aValue(3, $changeset, $field3)->build(),
        });

        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager->method('extractCrossRef')->with(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        );

        $art_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->willReturn([]);

        $artifact = $this->createPartialMock(Artifact::class, ['getChangesetCommentDao', 'getFormElementFactory', 'getArtifactFactory', 'getHierarchyFactory', 'getReferenceManager', 'getTracker', 'getId', 'getLastChangeset', 'getActionsQueuer', 'getWorkflowUpdateChecker', 'getWorkflow', 'getWorkflowRetriever', 'getChangeset']);
        $artifact->setTransactionExecutorForTests(new DBTransactionExecutorPassthrough());
        $artifact->method('getChangesetCommentDao')->willReturn($comment_dao);
        $artifact->method('getFormElementFactory')->willReturn($factory);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(66);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $artifact->method('getChangeset')->willReturn($new_changeset);
        $artifact->method('getReferenceManager')->willReturn($reference_manager);
        $artifact->method('getArtifactFactory')->willReturn($art_factory);
        $artifact->method('getHierarchyFactory')->willReturn($hierarchy_factory);
        $artifact->method('getActionsQueuer')->willReturn(PostCreationActionsQueuerStub::doNothing());

        $workflow_checker = $this->createMock(WorkflowUpdateChecker::class);
        $workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $artifact->method('getWorkflowUpdateChecker')->willReturn($workflow_checker);

        $workflow = $this->createMock(Workflow::class);
        $workflow->method('validate');
        $workflow->expects($this->once())->method('before')
            ->with(self::anything(), $user, $artifact)
            ->willReturnCallback(static function (&$fields_data) {
                if ($fields_data !== [101 => '123']) {
                    throw new LogicException('Bad data in Workflow::before');
                }
                $fields_data[102] = '456';
            });
        $artifact->method('getWorkflow')->willReturn($workflow);
        $art_factory->expects(self::never())->method('save');
        $artifact->method('getWorkflowRetriever')->willReturn(RetrieveWorkflowStub::withWorkflow($workflow));

        $fields_data = [
            101 => '123',
        ];

        $updated_fields_data_by_workflow = [
            101 => '123',
            102 => '456',
        ];
        $workflow->expects($this->once())->method('checkGlobalRules')->with($updated_fields_data_by_workflow)
            ->willThrowException(new Tracker_Workflow_GlobalRulesViolationException());

        $this->expectException(Tracker_Exception::class);
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testCreateNewChangesetWithoutNotification(): void
    {
        $comment = '';

        $comment_dao = $this->createMock(Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->expects($this->once())->method('createNewVersion')->willReturn(true);

        $changeset_saver = $this->createMock(ArtifactChangesetSaver::class);
        $changeset_saver->method('saveChangeset')->willReturn(1002);

        $user = UserTestBuilder::anActiveUser()->withId(1234)->build();

        $tracker = TrackerTestBuilder::aTracker()->withProject(new Project(['group_id' => 101]))->build();

        $factory = $this->createMock(Tracker_FormElementFactory::class);

        $fields_mock_methods = ['getId', 'isValid', 'isValidRegardingRequiredProperty', 'userCanUpdate', 'saveNewChangeset'];
        $field1              = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field1->method('getId')->willReturn(101);
        $field1->method('isValid')->willReturn(true);
        $field1->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field1->method('userCanUpdate')->willReturn(true);
        $field1->expects($this->once())->method('saveNewChangeset')->willReturn(true);
        $field2 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field2->method('getId')->willReturn(102);
        $field2->method('isValid')->willReturnCallback(static fn(Artifact $artifact, mixed $value) => $value === '123');
        $field2->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field2->method('userCanUpdate')->willReturn(true);
        $field2->expects($this->once())->method('saveNewChangeset')->willReturn(true);
        $field3 = $this->createPartialMock(Tracker_FormElement_Field_String::class, $fields_mock_methods);
        $field3->method('getId')->willReturn(103);
        $field3->method('isValid')->willReturn(true);
        $field3->method('isValidRegardingRequiredProperty')->willReturn(true);
        $field3->expects($this->once())->method('saveNewChangeset')->willReturn(true);
        $field3->method('userCanUpdate')->willReturn(true);
        $factory->method('getUsedFields')->willReturn([$field1, $field2, $field3]);
        $factory->method('getAllFormElementsForTracker')->willReturn([]);
        $factory->method('isFieldAFileField')
            ->willReturnCallback(static fn(Tracker_FormElement_Field $field) => $field::class === Tracker_FormElement_Field_File::class);

        $new_changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $new_changeset->method('executePostCreationActions')->with([false]);

        $changeset = $this->createMock(Tracker_Artifact_Changeset::class);
        $changeset->method('hasChanges')->willReturn(true);
        $changeset->method('getValues')->willReturn([]);
        $changeset->method('getValue')->willReturnCallback(static fn(Tracker_FormElement_Field $field) => match ($field) {
            $field1 => ChangesetValueTextTestBuilder::aValue(1, $changeset, $field1)->build(),
            $field2 => ChangesetValueTextTestBuilder::aValue(2, $changeset, $field2)->build(),
            $field3 => ChangesetValueTextTestBuilder::aValue(3, $changeset, $field3)->build(),
        });

        $reference_manager = $this->createMock(ReferenceManager::class);
        $reference_manager->method('extractCrossRef')->with(
            $comment,
            66,
            'plugin_tracker_artifact',
            101,
            $user->getId(),
            'irrelevant',
        );

        $art_factory = $this->createMock(Tracker_ArtifactFactory::class);

        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $hierarchy_factory->method('getChildren')->willReturn([]);

        $artifact = $this->createPartialMock(Artifact::class, ['getChangesetCommentDao', 'getFormElementFactory', 'getArtifactFactory', 'getHierarchyFactory', 'getReferenceManager', 'getTracker', 'getId', 'getLastChangeset', 'getActionsQueuer', 'getWorkflowUpdateChecker', 'getWorkflow', 'getWorkflowRetriever', 'getChangeset', 'getChangesetSaver', 'getUserManager']);
        $artifact->setTransactionExecutorForTests(new DBTransactionExecutorPassthrough());
        $artifact->method('getChangesetCommentDao')->willReturn($comment_dao);
        $artifact->method('getFormElementFactory')->willReturn($factory);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(66);
        $artifact->method('getLastChangeset')->willReturn($changeset);
        $artifact->method('getChangeset')->willReturn($new_changeset);
        $artifact->method('getReferenceManager')->willReturn($reference_manager);
        $artifact->method('getArtifactFactory')->willReturn($art_factory);
        $artifact->method('getHierarchyFactory')->willReturn($hierarchy_factory);
        $artifact->method('getChangesetSaver')->willReturn($changeset_saver);
        $artifact->method('getActionsQueuer')->willReturn(PostCreationActionsQueuerStub::doNothing());

        $GLOBALS['Response']->method('getFeedbackErrors')->willReturn([]);

        $art_factory->expects($this->once())->method('save')->willReturn(true);

        $workflow_checker = $this->createMock(WorkflowUpdateChecker::class);
        $workflow_checker->method('canFieldBeUpdated')->willReturn(true);
        $artifact->method('getWorkflowUpdateChecker')->willReturn($workflow_checker);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::exactly(2))->method('before');
        $workflow->method('after');
        $workflow->method('validate');
        $workflow->method('checkGlobalRules');
        $artifact->method('getWorkflow')->willReturn($workflow);
        $artifact->method('getWorkflowRetriever')->willReturn(RetrieveWorkflowStub::withWorkflow($workflow));
        $artifact->method('getUserManager')->willReturn(ProvideAndRetrieveUserStub::build($user));

        // Valid
        $fields_data = [
            102 => '123',
        ];

        $artifact->createNewChangeset($fields_data, $comment, $user, false);

        // Not valid
        $fields_data = [
            102 => '456',
        ];
        $this->expectException(Tracker_Exception::class);
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testGetCommentators(): void
    {
        $c1 = $this->createMock(Tracker_Artifact_Changeset::class);
        $c1->method('getSubmittedBy')->willReturn(101);
        $c2 = $this->createMock(Tracker_Artifact_Changeset::class);
        $c2->method('getSubmittedBy')->willReturn(102);
        $c2->method('getEmail')->willReturn('titi@example.com');
        $c3 = $this->createMock(Tracker_Artifact_Changeset::class);
        $c3->method('getSubmittedBy')->willReturn(null);
        $c3->method('getEmail')->willReturn('toto@example.com');
        $c4 = $this->createMock(Tracker_Artifact_Changeset::class);
        $c4->method('getSubmittedBy')->willReturn(null);
        $c4->method('getEmail')->willReturn('');

        $u1 = UserTestBuilder::aUser()->withId(101)->withUserName('sandrae')->build();
        $u2 = UserTestBuilder::aUser()->withId(102)->withUserName('marc')->build();

        $artifact = $this->createPartialMock(Artifact::class, ['getChangesets', 'getUserManager']);
        $artifact->method('getChangesets')->willReturn([$c1, $c2, $c3, $c4]);
        $artifact->method('getUserManager')->willReturn(RetrieveUserByIdStub::withUsers($u1, $u2));

        self::assertEquals(
            [
                'sandrae',
                'marc',
                'toto@example.com',
            ],
            $artifact->getCommentators()
        );
    }

    public function testItReturnsTheParentArtifactFromAncestors(): void
    {
        $release           = ArtifactTestBuilder::anArtifact(14564)->build();
        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $user              = UserTestBuilder::buildWithDefaults();

        $sprint = $this->createPartialMock(Artifact::class, ['getHierarchyFactory']);
        $sprint->method('getHierarchyFactory')->willReturn($hierarchy_factory);

        $hierarchy_factory->method('getParentArtifact')->with($user, $sprint)->willReturn($release);

        self::assertEquals($release, $sprint->getParent($user));
    }

    public function testItReturnsNullWhenNoAncestors(): void
    {
        $hierarchy_factory = $this->createMock(Tracker_HierarchyFactory::class);
        $user              = UserTestBuilder::buildWithDefaults();

        $sprint = $this->createPartialMock(Artifact::class, ['getHierarchyFactory']);
        $sprint->method('getHierarchyFactory')->willReturn($hierarchy_factory);
        $hierarchy_factory->method('getParentArtifact')->with($user, $sprint)->willReturn(null);

        self::assertEquals(null, $sprint->getParent($user));
    }

    public function testItGetsTheWorkflowFromTheTracker(): void
    {
        $workflow = $this->createStub(Workflow::class);
        $tracker  = TrackerTestBuilder::aTracker()->withWorkflow($workflow)->build();
        $artifact = ArtifactTestBuilder::anArtifact(186445)->inTracker($tracker)->build();

        self::assertEquals($workflow, $artifact->getWorkflow());
    }

    public function testItExportsTheArtifactToXML(): void
    {
        $user = new PFUser([
            'user_id'     => 101,
            'language_id' => 'en',
            'user_name'   => 'user_01',
            'ldap_id'     => 'ldap_O1',
        ]);

        $user_manager = RetrieveUserByIdStub::withUser($user);

        $changeset_01               = $this->createPartialMock(Tracker_Artifact_Changeset::class, ['forceFetchAllValues']);
        $changeset_01->id           = 1;
        $changeset_01->submitted_by = 101;
        $changeset_01->setLatestComment(ChangesetCommentTestBuilder::aComment()->build());
        $changeset_01->method('forceFetchAllValues');
        $changeset_02               = $this->createPartialMock(Tracker_Artifact_Changeset::class, ['forceFetchAllValues']);
        $changeset_02->id           = 2;
        $changeset_02->submitted_by = 101;
        $changeset_02->setLatestComment(ChangesetCommentTestBuilder::aComment()->build());
        $changeset_02->method('forceFetchAllValues');

        $form_element_factory = $this->createMock(Tracker_FormElementFactory::class);
        $form_element_factory->method('getUsedFileFields')->willReturn([]);

        $project = ProjectTestBuilder::aProject()->withId(101)->build();
        $tracker = TrackerTestBuilder::aTracker()->withId(101)->withProject($project)->build();

        $artifact = ArtifactTestBuilder::anArtifact(101)->inTracker($tracker)->submittedBy($user)->withSubmissionTimestamp(10)->build();
        $artifact->addChangeset($changeset_01);
        $artifact->addChangeset($changeset_02);
        $artifact->setFormElementFactory($form_element_factory);
        $artifact->setTracker($tracker);
        $changeset_01->artifact = $artifact;
        $changeset_02->artifact = $artifact;

        $artifacts_node = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                                             <artifacts/>'
        );

        $text_field_01 = TextFieldBuilder::aTextField(1456)->inTracker($tracker)->withName('text_01')->build();

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(
            1,
            $changeset_01,
            $text_field_01,
            true,
            'value_01',
            'text'
        );
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(
            2,
            $changeset_02,
            $text_field_01,
            true,
            'value_02',
            'text'
        );

        $changeset_01->setFieldValue($text_field_01, $value_01);
        $changeset_02->setFieldValue($text_field_01, $value_02);

        $archive = $this->createMock(ArchiveInterface::class);

        $collection = $this->createStub(UserXMLExportedCollection::class);
        $collection->method('add');
        $artifact_xml_exporter = (new ArtifactXMLExporterBuilder())->build(
            new NullChildrenCollector(),
            $this->createStub(InArchiveFilePathXMLExporter::class),
            $user,
            new UserXMLExporter($user_manager, $collection),
            false
        );

        $artifact->exportToXML($artifacts_node, $archive, $artifact_xml_exporter);

        self::assertEquals(101, (int) $artifacts_node->artifact['id']);
    }

    public function testGetOnlyChildrenOfArtifactInSameProject(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $visible_artifact_children = $this->createMock(Artifact::class);
        $visible_artifact_children
            ->method('getTracker')
            ->willReturn(TrackerTestBuilder::aTracker()->withProject($project)->build());
        $visible_artifact_children->method('userCanView')->willReturn(true);

        $children_not_in_project = $this->createMock(Artifact::class);
        $children_not_in_project
            ->method('getTracker')
            ->willReturn(
                TrackerTestBuilder::aTracker()
                    ->withProject(ProjectTestBuilder::aProject()->withId(666)->build())
                    ->build()
            );
        $children_not_in_project->method('userCanView')->willReturn(true);

        $children_not_visible_by_user = $this->createMock(Artifact::class);
        $children_not_visible_by_user
            ->method('getTracker')
            ->willReturn(TrackerTestBuilder::aTracker()->withProject($project)->build());
        $children_not_visible_by_user->method('userCanView')->willReturn(false);

        $artifact_factory = $this->createStub(Tracker_ArtifactFactory::class);
        $artifact_factory
            ->method('getChildren')
            ->willReturn([$visible_artifact_children, $children_not_in_project, $children_not_visible_by_user]);

        $user     = UserTestBuilder::aUser()->withId(5)->build();
        $artifact = $this->createPartialMock(Artifact::class, ['getArtifactFactory', 'getTracker']);
        $artifact->method('getArtifactFactory')->willReturn($artifact_factory);
        $artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withProject($project)->build());

        $children = $artifact->getChildrenForUserInSameProject($user);

        self::assertCount(1, $children);
        self::assertSame($children[0], $visible_artifact_children);
    }
}
