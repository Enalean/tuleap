<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;

require_once __DIR__ . '/bootstrap.php';

class Tracker_ArtifactTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->response = $GLOBALS['Response'];
        $this->language = $GLOBALS['Language'];

        $this->setText('fields not valid', array('plugin_tracker_artifact', 'fields_not_valid'));

        $tracker     = \Mockery::spy(\Tracker::class);
        $factory     = \Mockery::spy(\Tracker_FormElementFactory::class);
        $this->field = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->field->shouldReceive('getId')->andReturns(101);
        $this->field->shouldReceive('getLabel')->andReturns('Summary');
        $this->field->shouldReceive('getName')->andReturns('summary');
        $factory->shouldReceive('getUsedFields')->andReturns(array($this->field));
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns(array());

        $this->artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $this->artifact->shouldReceive('getTracker')->andReturns($tracker);
        $this->artifact->shouldReceive('getLastChangeset')->andReturns(false); // no changeset => artifact submission

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('validate')->andReturns(true);

        $this->artifact->shouldReceive('getWorkflow')->andReturns($workflow);
        $this->artifact_update = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->artifact_update->shouldReceive('getFormElementFactory')->andReturns($factory);
        $this->artifact_update->shouldReceive('getTracker')->andReturns($tracker);
        $this->artifact_update->shouldReceive('getWorkflow')->andReturns($workflow);
        $this->changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $this->changeset->shouldReceive('getValue')->with($this->field)->andReturns($this->changeset_value);
        $this->artifact_update->shouldReceive('getLastChangeset')->andReturns($this->changeset); // changeset => artifact modification
    }

    public function tearDown()
    {
        unset($this->field);
        unset($this->artifact);
        parent::tearDown();
    }

    public function testGetValue()
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $field     = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value     = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);

        $changeset->shouldReceive('getValue')->andReturns($value);

        $id = $tracker_id = $use_artifact_permissions = $submitted_by = $submitted_on = '';
        $artifact = new Tracker_Artifact($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions);

        $this->assertEqual($artifact->getValue($field, $changeset), $value);
    }

    public function testGetValue_without_changeset()
    {
        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $field     = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $value     = \Mockery::spy(\Tracker_Artifact_ChangesetValue_Date::class);

        $changeset->shouldReceive('getValue')->andReturns($value);

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $this->assertEqual($artifact->getValue($field), $value);
    }
}

class Tracker_Artifact_delegatedCreateNewChangesetTest extends Tracker_ArtifactTest
{
    public function testCreateNewChangesetWithWorkflowAndNoPermsOnPostActionField()
    {
        $email   = null; //not anonymous user
        $comment = '';

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->once()->andReturn(true);

        $dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1001);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1002);

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturns(666);
        $tracker->shouldReceive('getItemName')->andReturns('foobar');
        $tracker->shouldReceive('getFormElements')->andReturns(array());

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->times(2);
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $field1  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $workflow->shouldReceive('bypassPermissions')->with($field1)->andReturns(false);
        $field1->shouldReceive('saveNewChangeset')->once()->andReturns(true);

        $field2  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
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
        $factory->shouldReceive('getUsedFields')->andReturns(array($field1, $field2));
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns(array());

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->with([]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('getValues')->andReturns(array());
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with([
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ]);

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        stub($art_factory)->save()->once()->returns(true);

        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        // Valid
        $fields_data = array(
            101 => '123',
            102 => '456'
        );

        $submitted_on      = $_SERVER['REQUEST_TIME'];
        $send_notification = false;
        $comment_format    = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        stub($fields_validator)->validate()->returns(true);

        $creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($factory),
            $dao,
            $comment_dao,
            $art_factory,
            \Mockery::spy(\EventManager::class),
            $reference_manager,
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder::class),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($factory),
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
        );

        $creator->create(
            $artifact,
            $fields_data,
            $comment,
            $user,
            $submitted_on,
            $send_notification,
            $comment_format,
            \Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class)
        );
    }

    public function testDontCreateNewChangesetIfNoCommentOrNoChanges()
    {
        $this->language->shouldReceive('getText')->with('plugin_tracker_artifact', 'no_changes', Mockery::any())->andReturns('no changes');
        $this->response->shouldReceive('addFeedback')->never();

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->never();

        $dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $dao->shouldReceive('create')->never();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getFormElements')->andReturns(array());
        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->never();
        $field2  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->andReturns(true);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->never();
        $field3  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->never();
        $factory->shouldReceive('getUsedFields')->andReturns(array($field1, $field2, $field3));
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns(array());
        $factory->shouldReceive('getUsedArtifactLinkFields')->andReturns(array());

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(false);
        $changeset->shouldReceive('getValues')->andReturns(array());
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetDao')->andReturns($dao);
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getArtifactFactory')->andReturns(\Mockery::spy(\Tracker_ArtifactFactory::class));
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);
        $artifact->shouldReceive('getReferenceManager')->andReturns(\Mockery::spy(\ReferenceManager::class));
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->never();
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        $email   = null; //not annonymous user
        $comment = ''; //empty comment

        // Valid
        $fields_data = array();
        $this->expectException('Tracker_NoChangeException');
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }
}

class Tracker_Artifact_createNewChangesetTest extends Tracker_ArtifactTest
{

    public function testCreateNewChangeset()
    {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->shouldReceive('addFeedback')->never();

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        stub($comment_dao)->createNewVersion()->returns(true);
        $comment_dao->shouldReceive('createNewVersion')->once();

        $dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1001);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1002);
        $dao->shouldReceive('create')->once();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturns(666);
        $tracker->shouldReceive('getItemName')->andReturns('foobar');
        $tracker->shouldReceive('getFormElements')->andReturns(array());

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field2  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '123')->andReturns(true);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '456')->andReturns(false);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns(array($field1, $field2, $field3));
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns(array());

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->with([]);

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset->shouldReceive('getValues')->andReturns(array());
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with([
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ]);

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetDao')->andReturns($dao);
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);
        $artifact->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $artifact->shouldReceive('getArtifactFactory')->andReturns($art_factory);
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        stub($GLOBALS['Response'])->getFeedbackErrors()->returns(array());

        stub($art_factory)->save()->returns(true);
        $art_factory->shouldReceive('save')->once();

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->times(2);
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        // Valid
        $fields_data = array(
            102 => '123',
        );

        $artifact->createNewChangeset($fields_data, $comment, $user);

        // Not valid
        $fields_data = array(
            102 => '456',
        );

        $this->expectException('Tracker_Exception');

        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function itCheckThatGlobalRulesAreValid()
    {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->shouldReceive('addFeedback')->never();

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        $comment_dao->shouldReceive('createNewVersion')->never();

        $dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1001);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1002);
        $dao->shouldReceive('create')->never();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturns(666);
        $tracker->shouldReceive('getItemName')->andReturns('foobar');
        $tracker->shouldReceive('getFormElements')->andReturns(array());

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->never();
        $field2  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '123')->andReturns(true);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '456')->andReturns(false);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->never();
        $field3  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->never();
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns(array($field1, $field2, $field3));
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns(array());

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->never();

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);
        $changeset->shouldReceive('getValues')->andReturns(array());

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with([
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ]);

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        assert($artifact instanceof Tracker_Artifact);
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetDao')->andReturns($dao);
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);
        $artifact->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $artifact->shouldReceive('getArtifactFactory')->andReturns($art_factory);
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $workflow = Mockery::mock(Workflow::class);
        $workflow->shouldReceive('validate')->andReturns(true);
        $workflow->shouldReceive('before')->with(
            Mockery::on(
                static function (&$fields_data) : bool {
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

        $email = null; //not annonymous user

        $fields_data = array(
            101 => '123',
        );

        $updated_fields_data_by_workflow = array(
            101 => '123',
            102 => '456'
        );
        stub($workflow)->checkGlobalRules($updated_fields_data_by_workflow)->once()->throws(new Tracker_Workflow_GlobalRulesViolationException());

        $this->expectException('Tracker_Exception');
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testCreateNewChangesetWithoutNotification()
    {
        $email   = null; //not anonymous user
        $comment = '';

        $this->response->shouldReceive('addFeedback')->never();

        $comment_dao = \Mockery::spy(\Tracker_Artifact_Changeset_CommentDao::class);
        stub($comment_dao)->createNewVersion()->returns(true);
        $comment_dao->shouldReceive('createNewVersion')->once();

        $dao = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1001);
        $dao->shouldReceive('create')->with(66, 1234, null, $_SERVER['REQUEST_TIME'])->andReturn(1002);
        $dao->shouldReceive('create')->once();

        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(1234);
        $user->shouldReceive('isAnonymous')->andReturns(false);

        $tracker = \Mockery::spy(\Tracker::class);
        $tracker->shouldReceive('getGroupId')->andReturns(666);
        $tracker->shouldReceive('getItemName')->andReturns('foobar');
        $tracker->shouldReceive('getFormElements')->andReturns(array());

        $factory = \Mockery::spy(\Tracker_FormElementFactory::class);

        $field1  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field1->shouldReceive('getId')->andReturns(101);
        $field1->shouldReceive('isValid')->andReturns(true);
        $field1->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field1->shouldReceive('userCanUpdate')->andReturns(true);
        $field1->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field2  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field2->shouldReceive('getId')->andReturns(102);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '123')->andReturns(true);
        $field2->shouldReceive('isValid')->with(Mockery::any(), '456')->andReturns(false);
        $field2->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field2->shouldReceive('userCanUpdate')->andReturns(true);
        $field2->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3  = \Mockery::mock(\Tracker_FormElement_Field::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $field3->shouldReceive('getId')->andReturns(103);
        $field3->shouldReceive('isValid')->andReturns(true);
        $field3->shouldReceive('isValidRegardingRequiredProperty')->andReturns(true);
        $field3->shouldReceive('saveNewChangeset')->once()->andReturns(true);
        $field3->shouldReceive('userCanUpdate')->andReturns(true);
        $factory->shouldReceive('getUsedFields')->andReturns(array($field1, $field2, $field3));
        $factory->shouldReceive('getAllFormElementsForTracker')->andReturns(array());

        $new_changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $new_changeset->shouldReceive('executePostCreationActions')->never();

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);
        $changeset->shouldReceive('hasChanges')->andReturns(true);
        $changeset_value1 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value2 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset_value3 = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);
        $changeset->shouldReceive('getValue')->with($field1)->andReturns($changeset_value1);
        $changeset->shouldReceive('getValue')->with($field2)->andReturns($changeset_value2);
        $changeset->shouldReceive('getValue')->with($field3)->andReturns($changeset_value3);
        $changeset->shouldReceive('getValues')->andReturns(array());

        $reference_manager = \Mockery::spy(\ReferenceManager::class);
        $reference_manager->shouldReceive('extractCrossRef')->with([
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ]);

        $art_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);

        $hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->setTransactionExecutorForTests(new \Tuleap\Test\DB\DBTransactionExecutorPassthrough());
        $artifact->shouldReceive('getChangesetDao')->andReturns($dao);
        $artifact->shouldReceive('getChangesetCommentDao')->andReturns($comment_dao);
        $artifact->shouldReceive('getFormElementFactory')->andReturns($factory);
        $artifact->shouldReceive('getTracker')->andReturns($tracker);
        $artifact->shouldReceive('getId')->andReturns(66);
        $artifact->shouldReceive('getLastChangeset')->andReturns($changeset);
        $artifact->shouldReceive('getChangeset')->andReturns($new_changeset);
        $artifact->shouldReceive('getReferenceManager')->andReturns($reference_manager);
        $artifact->shouldReceive('getArtifactFactory')->andReturns($art_factory);
        $artifact->shouldReceive('getHierarchyFactory')->andReturns($hierarchy_factory);

        stub($GLOBALS['Response'])->getFeedbackErrors()->returns(array());

        stub($art_factory)->save()->returns(true);
        $art_factory->shouldReceive('save')->once();

        $workflow_checker = \Mockery::mock(\Tuleap\Tracker\Workflow\WorkflowUpdateChecker::class);
        $workflow_checker->shouldReceive('canFieldBeUpdated')->andReturnTrue();
        $artifact->shouldReceive('getWorkflowUpdateChecker')->andReturns($workflow_checker);

        $workflow = \Mockery::spy(\Workflow::class);
        $workflow->shouldReceive('before')->times(2);
        $workflow->shouldReceive('validate')->andReturns(true);
        $artifact->shouldReceive('getWorkflow')->andReturns($workflow);

        // Valid
        $fields_data = array(
            102 => '123',
        );

        $artifact->createNewChangeset($fields_data, $comment, $user, false);

        // Not valid
        $fields_data = array(
            102 => '456',
        );
        $this->expectException('Tracker_Exception');
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    public function testGetCommentators()
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

        $artifact = \Mockery::mock(\Tracker_Artifact::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $artifact->shouldReceive('getChangesets')->andReturns(array($c1, $c2, $c3, $c4));
        $artifact->shouldReceive('getUserManager')->andReturns($um);

        $c1->shouldReceive('getSubmittedBy')->andReturns(101);
        $c2->shouldReceive('getSubmittedBy')->andReturns(102);
        $c2->shouldReceive('getEmail')->andReturns('titi@example.com');
        $c3->shouldReceive('getSubmittedBy')->andReturns(null);
        $c3->shouldReceive('getEmail')->andReturns('toto@example.com');
        $c4->shouldReceive('getSubmittedBy')->andReturns(null);
        $c4->shouldReceive('getEmail')->andReturns('');

        $this->assertEqual($artifact->getCommentators(), array(
            'sandrae',
            'marc',
            'toto@example.com',
        ));
    }
}

class Tracker_Artifact_ParentAndAncestorsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->hierarchy_factory = \Mockery::spy(\Tracker_HierarchyFactory::class);

        $this->sprint = anArtifact()->build();
        $this->sprint->setHierarchyFactory($this->hierarchy_factory);

        $this->user = new PFUser(['language_id' => 'en']);
    }

    public function itReturnsTheParentArtifactFromAncestors()
    {
        $release = anArtifact()->withId(1)->build();

        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint)->returns($release);

        $this->assertEqual($release, $this->sprint->getParent($this->user));
    }

    public function itReturnsNullWhenNoAncestors()
    {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint)->returns(null);

        $this->assertEqual(null, $this->sprint->getParent($this->user));
    }
}

class Tracker_Artifact_getWorkflowTest extends TuleapTestCase
{

    private $workflow;
    private $artifact;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $tracker_id = 123;
        $this->workflow = aWorkflow()->withTrackerId($tracker_id)->build();
        $tracker = aMockeryTracker()->withId($tracker_id)->build();
        stub($tracker)->getWorkflow()->returns($this->workflow);
        $this->artifact = anArtifact()->build();
        $this->artifact->setTracker($tracker);
    }

    public function itGetsTheWorkflowFromTheTracker()
    {
        $workflow = $this->artifact->getWorkflow();
        $this->assertEqual($workflow, $this->workflow);
    }

    public function itInjectsItselfInTheWorkflow()
    {
        $workflow = $this->artifact->getWorkflow();
        $this->assertEqual($workflow->getArtifact(), $this->artifact);
    }
}

class Tracker_Artifact_PostActionsTest extends TuleapTestCase
{
    private $changeset_dao;
    /**
     * @var Tracker_Artifact_Changeset_NewChangesetCreator
     */
    private $creator;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->fields_data = array();
        $this->submitter   = new PFUser(['user_id' => 74, 'language_id' => 'en']);

        $this->changeset_dao  = \Mockery::spy(\Tracker_Artifact_ChangesetDao::class);
        $this->changesets  = array(new Tracker_Artifact_Changeset_Null());
        $factory     = \Mockery::spy(\Tracker_FormElementFactory::class);
        stub($factory)->getAllFormElementsForTracker()->returns(array());
        stub($factory)->getUsedFields()->returns(array());

        $this->artifact_factory = \Mockery::spy(\Tracker_ArtifactFactory::class);
        $this->workflow = \Mockery::spy(\Workflow::class);
        $this->changeset_factory  = \Mockery::spy(\Tracker_Artifact_ChangesetFactory::class);
        stub($this->changeset_factory)->getChangeset()->returns(new Tracker_Artifact_Changeset(
            123,
            aMockArtifact()->build(),
            12,
            21,
            ''
        ));
        $tracker        = mockery_stub(\Tracker::class)->getWorkflow()->returns($this->workflow);
        $this->artifact = anArtifact()
            ->withId(42)
            ->withChangesets($this->changesets)
            ->withTracker($tracker)
            ->build();

        $this->submitted_on = $_SERVER['REQUEST_TIME'];

        $fields_validator = \Mockery::spy(\Tracker_Artifact_Changeset_NewChangesetFieldsValidator::class);
        stub($fields_validator)->validate()->returns(true);

        $comment_dao = mockery_stub(\Tracker_Artifact_Changeset_CommentDao::class)->createNewVersion()->returns(true);

        $this->creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($factory),
            $this->changeset_dao,
            $comment_dao,
            $this->artifact_factory,
            \Mockery::spy(\EventManager::class),
            \Mockery::spy(\ReferenceManager::class),
            \Mockery::spy(\Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder::class),
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($factory),
            new \Tuleap\Test\DB\DBTransactionExecutorPassthrough(),
        );
    }

    public function itCallsTheAfterMethodOnWorkflowWhenCreateNewChangeset()
    {
        stub($this->changeset_dao)->create()->returns(true);
        stub($this->artifact_factory)->save()->returns(true);
        expect($this->workflow)->after(
            $this->fields_data,
            Mockery::on(function ($element) {
                return is_a($element, Tracker_Artifact_Changeset::class);
            }),
            end($this->changesets)
        )->once();

        $this->creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class)
        );
    }

    public function itDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFailsOnNewChangeset()
    {
        stub($this->changeset_dao)->create()->returns(true);
        stub($this->artifact_factory)->save()->returns(false);
        expect($this->workflow)->after()->never();

        $this->expectException('Tracker_AfterSaveException');

        $this->creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT,
            Mockery::mock(\Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping::class)
        );
    }
}

class Tracker_Artifact_ExportToXMLTest extends TuleapTestCase
{

    private $user_manager;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($this->user_manager);

        $this->formelement_factory = \Mockery::spy(\Tracker_FormElementFactory::class);
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();

        parent::tearDown();
    }

    public function itExportsTheArtifactToXML()
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_O1'
        ]);
        stub($this->user_manager)->getUserById(101)->returns($user);
        stub($this->formelement_factory)->getUsedFileFields()->returns(array());

        $changeset_01 = mockery_stub(\Tracker_Artifact_Changeset::class)->getsubmittedBy()->returns(101);
        $changeset_02 = mockery_stub(\Tracker_Artifact_Changeset::class)->getsubmittedBy()->returns(101);

        $project = mockery_stub(\Project::class)->getID()->returns(101);
        $tracker = aTracker()->withId(101)->withProject($project)->build();

        $artifact = anArtifact()->withTracker($tracker)
                                      ->withId(101)
                                      ->withChangesets(array($changeset_01, $changeset_02))
                                      ->build();

        $artifacts_node = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <artifacts/>');

        $text_field_01 = mockery_stub(\Tracker_FormElement_Field_Text::class)->getName()->returns('text_01');
        stub($text_field_01)->getTracker()->returns($tracker);

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(1, \Mockery::spy(\Tracker_Artifact_Changeset::class), $text_field_01, true, 'value_01', 'text');
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(2, \Mockery::spy(\Tracker_Artifact_Changeset::class), $text_field_01, true, 'value_02', 'text');

        stub($changeset_01)->getArtifact()->returns($artifact);
        stub($changeset_01)->getValues()->returns(array($value_01));

        stub($changeset_02)->getArtifact()->returns($artifact);
        stub($changeset_02)->getValues()->returns(array($value_02));

        $archive = \Mockery::spy(\Tuleap\Project\XML\Export\ArchiveInterface::class);

        $user_xml_exporter      = new UserXMLExporter($this->user_manager, \Mockery::spy(\UserXMLExportedCollection::class));
        $builder                = new Tracker_XML_Exporter_ArtifactXMLExporterBuilder();
        $children_collector     = new Tracker_XML_Exporter_NullChildrenCollector();
        $file_path_xml_exporter = new Tracker_XML_Exporter_InArchiveFilePathXMLExporter();

        $artifact_xml_exporter =  $builder->build(
            $children_collector,
            $file_path_xml_exporter,
            $user,
            $user_xml_exporter,
            false
        );

        $artifact->exportToXML($artifacts_node, $archive, $artifact_xml_exporter);

        $this->assertEqual($artifacts_node->artifact['id'], 101);
    }
}
