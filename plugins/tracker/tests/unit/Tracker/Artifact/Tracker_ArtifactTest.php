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

namespace Tuleap\Tracker\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use SimpleXMLElement;
use Tracker;
use Tracker_Artifact_Changeset_ChangesetDataInitializator;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_Workflow_GlobalRulesViolationException;
use Tracker_XML_Exporter_ArtifactXMLExporterBuilder;
use Tracker_XML_Exporter_InArchiveFilePathXMLExporter;
use Tracker_XML_Exporter_NullChildrenCollector;
use Tuleap\GlobalResponseMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\AfterNewChangesetHandler;
use Tuleap\Tracker\Artifact\Changeset\ArtifactChangesetSaver;
use Tuleap\Tracker\Artifact\Changeset\Comment\ChangesetCommentIndexer;
use Tuleap\Tracker\Artifact\Changeset\Comment\CommentCreator;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\TrackerPrivateCommentUGroupPermissionInserter;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\Changeset\NewChangeset;
use Tuleap\Tracker\Artifact\Changeset\NewChangesetCreator;
use Tuleap\Tracker\Artifact\Changeset\PostCreation\PostCreationContext;
use Tuleap\Tracker\Artifact\ChangesetValue\ChangesetValueSaver;
use Tuleap\Tracker\Artifact\XMLImport\TrackerNoXMLImportLoggedConfig;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ParentLinkAction;
use Tuleap\Tracker\FormElement\Field\Text\TextValueValidator;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveWorkflowStub;
use Tuleap\Tracker\Test\Stub\SaveArtifactStub;
use Tuleap\Tracker\Test\Stub\Tracker\Artifact\Changeset\PostCreation\PostCreationActionsQueuerStub;
use UserXMLExporter;
use Workflow;

final class Tracker_ArtifactTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;
    use GlobalResponseMock;

    public function testLastChangesetIsRetrieved(): void
    {
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changeset_factory = \Mockery::mock(Tracker_Artifact_ChangesetFactory::class);
        $changeset_factory->shouldReceive('getLastChangeset')->once()->andReturns($changeset);

        $artifact->shouldReceive('getChangesetFactory')->once()->andReturns($changeset_factory);

        $this->assertSame($changeset, $artifact->getLastChangeset());
        $this->assertSame($changeset, $artifact->getLastChangeset());
    }

    public function testLastChangesetIsRetrievedWhenAllChangesetsHaveAlreadyBeenLoaded(): void
    {
        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial();

        $last_changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);

        $changesets = [
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            \Mockery::mock(\Tracker_Artifact_Changeset::class),
            $last_changeset,
        ];

        $artifact->setChangesets($changesets);
        $artifact->shouldReceive('getChangesets')->once()->andReturns($changesets);

        $this->assertSame($last_changeset, $artifact->getLastChangeset());
        $this->assertSame($last_changeset, $artifact->getLastChangeset());
    }

    public function testGetValue(): void
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $field     = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value     = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);

        $changeset->shouldReceive('getValue')->andReturns($value);

        $id       = $tracker_id = $use_artifact_permissions = $submitted_by = $submitted_on = '';
        $artifact = new Artifact($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions);

        $this->assertEquals($value, $artifact->getValue($field, $changeset));
    }

    public function testGetValueWithoutChangeset(): void
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $field     = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value     = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);

        $changeset->shouldReceive('getValue')->andReturns($value);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $this->assertEquals($value, $artifact->getValue($field));
    }

    public function testCreateNewChangesetWithWorkflowAndNoPermsOnPostActionField(): void
    {
        $comment = '';

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->once()->andReturn(true);

        $dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $dao->shouldReceive('create')
            ->with(66, 1234, null, $_SERVER['REQUEST_TIME'], Mockery::type(TrackerNoXMLImportLoggedConfig::class))
            ->andReturn(1001);
        $dao->shouldReceive('create')
            ->with(66, 1234, null, $_SERVER['REQUEST_TIME'], Mockery::type(TrackerNoXMLImportLoggedConfig::class))
            ->andReturn(1002);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = TrackerTestBuilder::aTracker()->withProject(new \Project(["group_id" => 101]))->build();

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->times(2);
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $field1 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $workflow->shouldReceive('bypassPermissions')->with($field1)->andReturns(false);
        $field1->shouldReceive('saveNewChangeset')->once()->andReturns(true);

        $field2 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(false);
        $workflow->shouldReceive('bypassPermissions')->with($field2)->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->with(
            Mockery::any(),
            Mockery::any(),
            Mockery::any(),
            Mockery::any(),
            $user,
            false,
            true,
            Mockery::type(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class)
        )->once()->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2]);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->with([true]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValues')->andReturns([]);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with(
            [
                $comment,
                66,
                'plugin_tracker_artifact',
                666,
                $user->getId(),
                'foobar',
            ]
        );

        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);

        // Valid
        $fields_data = [
            101 => '123',
            102 => '456',
        ];

        $submitted_on      = $_SERVER['REQUEST_TIME'];
        $send_notification = false;

        $changeset_creation = NewChangeset::fromFieldsDataArrayWithEmptyComment(
            $artifact,
            $fields_data,
            $user,
            $submitted_on,
        );

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        $fields_validator->shouldReceive('validate')->andReturns(true);

        $artifact_saver = Mockery::mock(ArtifactChangesetSaver::class);
        $artifact_saver->shouldReceive('saveChangeset')->once();
        $fields_retriever = new FieldsToBeSavedInSpecificOrderRetriever($factory);

        $changeset_comment_indexer = $this->createStub(ChangesetCommentIndexer::class);
        $changeset_comment_indexer->method('indexNewChangesetComment');

        $creator = new NewChangesetCreator(
            $fields_validator,
            $fields_retriever,
            \Mockery::spy(\EventManager::class),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($factory),
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
            $artifact_saver,
            Mockery::mock(ParentLinkAction::class),
            new AfterNewChangesetHandler(
                SaveArtifactStub::withSuccess(),
                $fields_retriever,
            ),
            PostCreationActionsQueuerStub::doNothing(),
            new ChangesetValueSaver(),
            RetrieveWorkflowStub::withWorkflow($workflow),
            new CommentCreator(
                $comment_dao,
                $reference_manager,
                Mockery::spy(TrackerPrivateCommentUGroupPermissionInserter::class),
                $changeset_comment_indexer,
                new TextValueValidator(),
            ),
        );
        $creator->create($changeset_creation, PostCreationContext::withNoConfig(false));
    }

    public function testDontCreateNewChangesetIfNoCommentOrNoChanges(): void
    {
        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->never();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getFormElements')->andReturns([]);
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->never();
        $field2 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->andReturns(true);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->never();
        $field3 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->never();
        $factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);
        $factory->shouldReceive('getUsedArtifactLinkFields')->andReturns([]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(false);
        $changeset->shouldReceive('getValues')->andReturns([]);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->andReturns([]);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getArtifactFactory')->andReturns(\Mockery::spy(\Tracker_ArtifactFactory::class));
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);
        $artifact->shouldReceive('getReferenceManager')->andReturns(\Mockery::spy(\ReferenceManager::class));
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getActionsQueuer')->andReturns(PostCreationActionsQueuerStub::doNothing());

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->never();
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $artifact->shouldReceive('getWorkflowRetriever')->andReturns(RetrieveWorkflowStub::withWorkflow($workflow));

        $email   = null; //not annonymous user
        $comment = ''; //empty comment

        // Valid
        $fields_data = [];
        $this->expectException(\Tracker_NoChangeException::class);
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testCreateNewChangeset(): void
    {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->andReturns(true)->once();

        $changeset_saver = Mockery::mock(ArtifactChangesetSaver::class);
        $changeset_saver->shouldReceive('saveChangeset')->andReturn(1002);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = TrackerTestBuilder::aTracker()->withProject(new \Project(["group_id" => 101]))->build();

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field2 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '123')->andReturns(true);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '456')->andReturns(false);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->with([true]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset->shouldReceive('getValues')->andReturns([]);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with(
            [
                $comment,
                66,
                'plugin_tracker_artifact',
                666,
                $user->getId(),
                'foobar',
            ]
        );

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->andReturns([]);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);
        $artifact->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $artifact->shouldReceive('getArtifactFactory')->andReturns($art_factory);
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);
        $artifact->shouldReceive('getChangesetSaver')->andReturns($changeset_saver);
        $artifact->shouldReceive('getActionsQueuer')->andReturns(PostCreationActionsQueuerStub::doNothing());

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $GLOBALS['Response']->method('getFeedbackErrors')->willReturn([]);

        $art_factory->shouldReceive('save')->andReturns(true)->once();

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->times(2);
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $artifact->shouldReceive('getWorkflowRetriever')->andReturns(RetrieveWorkflowStub::withWorkflow($workflow));

        // Valid
        $fields_data = [
            102 => '123',
        ];

        $artifact->createNewChangeset($fields_data, $comment, $user);

        // Not valid
        $fields_data = [
            102 => '456',
        ];

        $this->expectException(\Tracker_Exception::class);

        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testItCheckThatGlobalRulesAreValid(): void
    {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->never();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturns(666);
        $tracker->shouldReceive('getItemName')->andReturns('foobar');
        $tracker->shouldReceive('getFormElements')->andReturns([]);

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->never();
        $field2 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '123')->andReturns(true);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '456')->andReturns(false);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->never();
        $field3 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->never();
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->with([false]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);
        $changeset->shouldReceive('getValues')->andReturns([]);

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with(
            [
                $comment,
                66,
                'plugin_tracker_artifact',
                666,
                $user->getId(),
                'foobar',
            ]
        );

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->andReturns([]);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        assert($artifact instanceof Artifact);
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);
        $artifact->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $artifact->shouldReceive('getArtifactFactory')->andReturns($art_factory);
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);
        $artifact->shouldReceive('getActionsQueuer')->andReturns(PostCreationActionsQueuerStub::doNothing());

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('validate')->andReturns(true);
        $workflow->shouldReceive('before')->with(
            Mockery::on(
                static function (&$fields_data): bool {
                    if ($fields_data !== [101 => '123']) {
                        return false;
                    }
                    $fields_data[102] = '456';

                    return true;
                }
            ),
            $user,
            $artifact
        )->once();
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $art_factory->shouldReceive('save')->never();
        $artifact->shouldReceive('getWorkflowRetriever')->andReturns(RetrieveWorkflowStub::withWorkflow($workflow));

        $email = null; //not annonymous user

        $fields_data = [
            101 => '123',
        ];

        $updated_fields_data_by_workflow = [
            101 => '123',
            102 => '456',
        ];
        $workflow->shouldReceive('checkGlobalRules')->with($updated_fields_data_by_workflow)->once()->andThrows(
            new Tracker_Workflow_GlobalRulesViolationException()
        );

        $this->expectException(\Tracker_Exception::class);
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testCreateNewChangesetWithoutNotification(): void
    {
        $email   = null; //not anonymous user
        $comment = '';

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->andReturns(true)->once();

        $changeset_saver = Mockery::mock(ArtifactChangesetSaver::class);
        $changeset_saver->shouldReceive('saveChangeset')->andReturn(1002);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = TrackerTestBuilder::aTracker()->withProject(new \Project(["group_id" => 101]))->build();

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field2 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '123')->andReturns(true);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '456')->andReturns(false);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3 = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods(
        );
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns([$field1, $field2, $field3]);
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns([]);

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->with([false]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);
        $changeset->shouldReceive('getValues')->andReturns([]);

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with(
            [
                $comment,
                66,
                'plugin_tracker_artifact',
                666,
                $user->getId(),
                'foobar',
            ]
        );

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $hierarchy_factory->shouldReceive('getChildren')->andReturns([]);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);
        $artifact->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $artifact->shouldReceive('getArtifactFactory')->andReturns($art_factory);
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);
        $artifact->shouldReceive('getChangesetSaver')->andReturns($changeset_saver);
        $artifact->shouldReceive('getActionsQueuer')->andReturns(PostCreationActionsQueuerStub::doNothing());

        $GLOBALS['Response']->method('getFeedbackErrors')->willReturn([]);

        $art_factory->shouldReceive('save')->andReturns(true)->once();

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->times(2);
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $artifact->shouldReceive('getWorkflowRetriever')->andReturns(RetrieveWorkflowStub::withWorkflow($workflow));

        // Valid
        $fields_data = [
            102 => '123',
        ];

        $artifact->createNewChangeset($fields_data, $comment, $user, false);

        // Not valid
        $fields_data = [
            102 => '456',
        ];
        $this->expectException(\Tracker_Exception::class);
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testGetCommentators(): void
    {
        $c1 = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $c2 = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $c3 = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $c4 = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $u1 = \Mockery::spy(\PFUser::class);
        $u1->shouldReceive('getUserName')->andReturns('sandrae');
        $u2 = \Mockery::spy(\PFUser::class);
        $u2->shouldReceive('getUserName')->andReturns('marc');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getUserById')->with(101)->andReturns($u1);
        $um->shouldReceive('getUserById')->with(102)->andReturns($u2);

        $artifact = \Mockery::mock(\Tuleap\Tracker\Artifact\Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->shouldReceive('getChangesets')->andReturns([$c1, $c2, $c3, $c4]);
        $artifact->shouldReceive('getUserManager')->andReturns($um);

        $c1->shouldReceive('getSubmittedBy')->andReturns(101);
        $c2->shouldReceive('getSubmittedBy')->andReturns(102);
        $c2->shouldReceive('getEmail')->andReturns('titi@example.com');
        $c3->shouldReceive('getSubmittedBy')->andReturns(null);
        $c3->shouldReceive('getEmail')->andReturns('toto@example.com');
        $c4->shouldReceive('getSubmittedBy')->andReturns(null);
        $c4->shouldReceive('getEmail')->andReturns('');

        $this->assertEquals(
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
        $release           = Mockery::mock(Artifact::class);
        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $user              = Mockery::mock(\PFUser::class);

        $sprint = Mockery::mock(Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $sprint->shouldReceive("getHierarchyFactory")->andReturn($hierarchy_factory);

        $hierarchy_factory->shouldReceive('getParentArtifact')->with($user, $sprint)->andReturn($release);

        $this->assertEquals($release, $sprint->getParent($user));
    }

    public function testItReturnsNullWhenNoAncestors(): void
    {
        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        $user              = Mockery::mock(\PFUser::class);

        $sprint = Mockery::mock(Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $sprint->shouldReceive("getHierarchyFactory")->andReturn($hierarchy_factory);
        $hierarchy_factory->shouldReceive('getParentArtifact')->with($user, $sprint)->andReturn(null);

        $this->assertEquals(null, $sprint->getParent($user));
    }

    public function testItGetsTheWorkflowFromTheTracker(): void
    {
        $workflow = Mockery::mock(Workflow::class);
        $tracker  = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getWorkflow')->andReturn($workflow);
        $artifact = Mockery::mock(Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->shouldReceive('getTracker')->andReturn($tracker);

        $this->assertEquals($workflow, $artifact->getWorkflow());
    }

    public function testItExportsTheArtifactToXML(): void
    {
        $user = new PFUser(
            [
                'user_id'     => 101,
                'language_id' => 'en',
                'user_name'   => 'user_01',
                'ldap_id'     => 'ldap_O1',
            ]
        );

        $user_manager = Mockery::mock(\UserManager::class);
        $user_manager->shouldReceive('getUserById')->with(101)->andReturns($user);

        $form_element_factory = Mockery::mock(\Tracker_FormElementFactory::class);
        $form_element_factory->shouldReceive('getUsedFileFields')->andReturns([]);

        $changeset_01 = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_01->shouldReceive('getsubmittedBy')->andReturns(101);
        $changeset_02 = Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset_02->shouldReceive('getsubmittedBy')->andReturns(101);

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(101);
        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->andReturn(101);
        $tracker->shouldReceive('getProject')->andReturn($project);

        $artifact = new Artifact(101, $tracker->getId(), $user->getId(), 10, null);
        $artifact->addChangeset($changeset_01);
        $artifact->addChangeset($changeset_02);
        $artifact->setFormElementFactory($form_element_factory);
        $artifact->setTracker($tracker);

        $artifacts_node = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
                                             <artifacts/>'
        );

        $text_field_01 = Mockery::mock(\Tracker_FormElement_Field_Text::class);
        $text_field_01->shouldReceive('getName')->andReturns('text_01');
        $text_field_01->shouldReceive('getTracker')->andReturns($tracker);

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(
            1,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $text_field_01,
            true,
            'value_01',
            'text'
        );
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(
            2,
            \Mockery::spy(\Tracker_Artifact_Changeset::class),
            $text_field_01,
            true,
            'value_02',
            'text'
        );

        $changeset_01->shouldReceive('getArtifact')->andReturns($artifact);
        $changeset_01->shouldReceive('getValues')->andReturns([$value_01]);

        $changeset_02->shouldReceive('getArtifact')->andReturns($artifact);
        $changeset_02->shouldReceive('getValues')->andReturns([$value_02]);

        $archive = \Mockery::spy(\Tuleap\Project\XML\Export\ArchiveInterface::class);

        $user_xml_exporter      = new UserXMLExporter($user_manager, \Mockery::spy(\UserXMLExportedCollection::class));
        $builder                = new Tracker_XML_Exporter_ArtifactXMLExporterBuilder();
        $children_collector     = new Tracker_XML_Exporter_NullChildrenCollector();
        $file_path_xml_exporter = Mockery::mock(Tracker_XML_Exporter_InArchiveFilePathXMLExporter::class);
        //        $file_path_xml_exporter->shouldReceive('exportAttachmentsInArchive')->once();

        $artifact_xml_exporter = $builder->build(
            $children_collector,
            $file_path_xml_exporter,
            $user,
            $user_xml_exporter,
            false
        );

        $artifact->exportToXML($artifacts_node, $archive, $artifact_xml_exporter);

        $this->assertEquals(101, (int) $artifacts_node->artifact['id']);
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

        $artifact_factory = $this->createStub(\Tracker_ArtifactFactory::class);
        $artifact_factory
            ->method('getChildren')
            ->willReturn([$visible_artifact_children, $children_not_in_project, $children_not_visible_by_user]);

        $user     = UserTestBuilder::aUser()->withId(5)->build();
        $artifact = $this->createPartialMock(Artifact::class, ['getArtifactFactory', 'getTracker']);
        $artifact->method('getArtifactFactory')->willReturn($artifact_factory);
        $artifact->method('getTracker')->willReturn(TrackerTestBuilder::aTracker()->withProject($project)->build());

        $children = $artifact->getChildrenForUserInSameProject($user);

        $this->assertCount(1, $children);
        $this->assertSame($children[0], $visible_artifact_children);
    }
}
