<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

require_once('bootstrap.php');
Mock::generatePartial(
    'Tracker_Artifact',
    'Tracker_ArtifactTestVersion',
    array(
        'getChangesetDao',
        'getChangesetCommentDao',
        'getFormElementFactory',
        'getTracker',
        'getId',
        'getLastChangeset',
        'getReferenceManager',
        'getChangesets',
        'getChangeset',
        'getUserManager',
        'getArtifactFactory',
        'getHierarchyFactory',
        'getWorkflow',
    )
);

Mock::generatePartial(
    'Tracker_Artifact',
    'Tracker_ArtifactTestPermissions',
    array(
        'getTracker',
        'getId',
        'getUserManager',
        'useArtifactPermissions',
        'permission_db_authorized_ugroups',
        'getValue',
        'getSubmittedBy'
    )
);

Mock::generate('Tracker_Artifact_Changeset');
Mock::generate('Tracker_Artifact_ChangesetValue');
Mock::generate('Tracker_FormElement_Field_Date');
Mock::generate('Tracker_Artifact_ChangesetValue_Date');
Mock::generate('Tracker_Artifact_ChangesetDao');
Mock::generate('Tracker_Artifact_Changeset_CommentDao');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElementFactory');
Mock::generatePartial('Tracker_FormElement_Field', 'MockTracker_FormElement_Field', array(
        'getId',
        'getLabel',
        'getName',
        'isValid',
        'isValidRegardingRequiredProperty',
        'fetchCriteriaValue',
        'fetchChangesetValue',
        'fetchRawValue',
        'getCriteriaFrom',
        'getCriteriaWhere',
        'getCriteriaDao',
        'fetchArtifactValue',
        'fetchArtifactValueReadOnly',
        'fetchSubmitValue',
        'fetchTooltipValue',
        'getValueDao',
        'fetchFollowUp',
        'fetchRawValueFromChangeset',
        'saveValue',
        'fetchAdminFormElement',
        'getFactoryLabel',
        'getFactoryDescription',
        'getFactoryIconUseIt',
        'getFactoryIconCreate',
        'getChangesetValue',
        'saveNewChangeset',
        'validate',
        'getSoapAvailableValues',
        'hasDefaultValue',
        'getDefaultValue',
        'isRequired',
        'userCanUpdate',
        'userCanSubmit',
        'setHasErrors',
        'fetchSubmitValueMasschange',
        'accept'
    )
);
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
require_once('common/user/User.class.php');
Mock::generate('PFUser');
require_once('common/include/Response.class.php');
Mock::generate('Response');
require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');
require_once('common/reference/ReferenceManager.class.php');
Mock::generate('ReferenceManager');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');
Mock::generate('Tracker_ArtifactFactory');

Mock::generate('Tracker_FormElement_Field_ArtifactLink');

Mock::generate('TrackerManager');

Mock::generate('Workflow');

class MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields extends MockWorkflow {
    function before(&$fields_data, $submitter, $artifact) {
        $fields_data[102] = '456';
        return parent::before($fields_data, $submitter, $artifact);
    }
}

class Tracker_ArtifactTest extends TuleapTestCase {

    function setUp() {
        parent::setUp();
        $this->response = $GLOBALS['Response'];
        $this->language = $GLOBALS['Language'];

        $this->setText('fields not valid', array('plugin_tracker_artifact', 'fields_not_valid'));

        $tracker     = new MockTracker();
        $factory     = new MockTracker_FormElementFactory();
        $this->field = new MockTracker_FormElement_Field();
        $this->field->setReturnValue('getId', 101);
        $this->field->setReturnValue('getLabel', 'Summary');
        $this->field->setReturnValue('getName', 'summary');
        $factory->setReturnValue('getUsedFields', array($this->field));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $this->artifact = new Tracker_ArtifactTestVersion();
        $this->artifact->setReturnReference('getFormElementFactory', $factory);
        $this->artifact->setReturnReference('getTracker', $tracker);
        $this->artifact->setReturnValue('getLastChangeset', false); // no changeset => artifact submission

        $workflow = new MockWorkflow();
        $workflow->setReturnValue('validate', true);

        $this->artifact->setReturnReference('getWorkflow', $workflow);
        $this->artifact_update = new Tracker_ArtifactTestVersion();
        $this->artifact_update->setReturnReference('getFormElementFactory', $factory);
        $this->artifact_update->setReturnReference('getTracker', $tracker);
        $this->artifact_update->setReturnReference('getWorkflow', $workflow);
        $this->changeset = new MockTracker_Artifact_Changeset();
        $this->changeset_value = new MockTracker_Artifact_ChangesetValue();
        $this->changeset->setReturnReference('getValue', $this->changeset_value, array($this->field));
        $this->artifact_update->setReturnReference('getLastChangeset', $this->changeset); // changeset => artifact modification
    }

    function tearDown() {
        unset($this->field);
        unset($this->artifact);
        parent::tearDown();
    }

    function testGetValue() {
        $changeset = new MockTracker_Artifact_Changeset();
        $field     = new MockTracker_FormElement_Field_Date();
        $value     = new MockTracker_Artifact_ChangesetValue_Date();

        $changeset->setReturnReference('getValue', $value);

        $id = $tracker_id = $use_artifact_permissions = $submitted_by = $submitted_on = '';
        $artifact = new Tracker_Artifact($id, $tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions);

        $this->assertEqual($artifact->getValue($field, $changeset), $value);
    }

    function testGetValue_without_changeset() {
        $changeset = new MockTracker_Artifact_Changeset();
        $field     = new MockTracker_FormElement_Field_Date();
        $value     = new MockTracker_Artifact_ChangesetValue_Date();

        $changeset->setReturnReference('getValue', $value);

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getLastChangeset', $changeset);

        $this->assertEqual($artifact->getValue($field), $value);
    }
}

class Tracker_Artifact_delegatedCreateNewChangesetTest extends Tracker_ArtifactTest {

    function testCreateNewChangesetWithWorkflowAndNoPermsOnPostActionField() {
        $email   = null; //not anonymous user
        $comment = '';

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        stub($comment_dao)->createNewVersion()->returns(true);

        $comment_dao->expectCallCount('createNewVersion', 1);

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->expectCallCount('create', 1);

        $user = mock('PFUser');
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $artifact = partial_mock('Tracker_Artifact', array(
            'getChangesetDao',
            'getChangesetCommentDao',
            'getFormElementFactory',
            'getTracker',
            'getId',
            'getLastChangeset',
            'getReferenceManager',
            'getChangesets',
            'getChangeset',
            'getUserManager',
            'getArtifactFactory',
            'getWorkflow',
            )
        );
        $workflow = new MockWorkflow();
        $workflow->expectCallCount('before', 2);
        $workflow->setReturnValue('validate', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->setReturnValue('saveNewChangeset', true);
        $workflow->setReturnValue('bypassPermissions', false, array($field1));
        $field1->expectOnce('saveNewChangeset');

        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('userCanUpdate', false);
        $field2->setReturnValue('saveNewChangeset', true);
        $workflow->setReturnValue('bypassPermissions', true, array($field2));
        $field2->expectOnce('saveNewChangeset', array('*', '*', '*', '*', $user, false, true));
        $factory->setReturnValue('getUsedFields', array($field1, $field2));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expect('notify', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('getValues', array());
        $changeset->setReturnValue('hasChanges', true);
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();
        stub($art_factory)->save()->returns(true);

        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);

        $art_factory->expectOnce('save');

        // Valid
        $fields_data = array(
            101 => '123',
            102 => '456'
        );

        $submitted_on      = $_SERVER['REQUEST_TIME'];
        $send_notification = false;
        $comment_format    = Tracker_Artifact_Changeset_Comment::TEXT_COMMENT;

        $fields_validator = mock('Tracker_Artifact_Changeset_NewChangesetFieldsValidator');
        stub($fields_validator)->validate()->returns(true);

        $creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            $factory,
            $dao,
            $comment_dao,
            $art_factory,
            mock('EventManager'),
            $reference_manager,
            mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder')
        );

        $creator->create($artifact, $fields_data, $comment, $user, $submitted_on, $send_notification, $comment_format);
    }

    function testDontCreateNewChangesetIfNoCommentOrNoChanges() {
        $this->language->setReturnValue('getText', 'no changes', array('plugin_tracker_artifact', 'no_changes', '*'));
        $this->response->expectNever('addFeedback');

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectNever('createNewVersion');

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->expectNever('create');

        $user = mock('PFUser');
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getFormElements', array());
        $factory = new MockTracker_FormElementFactory();

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('isValidRegardingRequiredProperty', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->expectNever('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true);
        $field2->setReturnValue('isValidRegardingRequiredProperty', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectNever('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('isValidRegardingRequiredProperty', true);
        $field3->setReturnValue('userCanUpdate', true);
        $field3->expectNever('saveNewChangeset');
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());
        $factory->setReturnValue('getUsedArtifactLinkFields', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', false);
        $changeset->setReturnValue('getValues', array());
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));

        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getArtifactFactory', mock('Tracker_ArtifactFactory'));
        $artifact->setReturnReference('getHierarchyFactory', $hierarchy_factory);
        $artifact->setReturnReference('getReferenceManager', mock('ReferenceManager'));
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);

        $workflow = new MockWorkflow();
        $workflow->expectNever('before');
        $workflow->setReturnValue('validate', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $email   = null; //not annonymous user
        $comment = ''; //empty comment

        // Valid
        $fields_data = array();
        $this->expectException('Tracker_NoChangeException');
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }
}

class Tracker_Artifact_createNewChangesetTest extends Tracker_ArtifactTest {

    function testCreateNewChangeset() {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->expectCallCount('addFeedback', 0);

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        stub($comment_dao)->createNewVersion()->returns(true);
        $comment_dao->expectCallCount('createNewVersion', 1);

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->expectCallCount('create', 1);

        $user = mock('PFUser');
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('isValidRegardingRequiredProperty', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->setReturnValue('saveNewChangeset', true);
        $field1->expectOnce('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('isValidRegardingRequiredProperty', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->setReturnValue('saveNewChangeset', true);
        $field2->expectOnce('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('isValidRegardingRequiredProperty', true);
        $field3->setReturnValue('saveNewChangeset', true);
        $field3->expectOnce('saveNewChangeset');
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expect('notify', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
        $changeset->setReturnValue('getValues', array());
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        $artifact->setReturnReference('getHierarchyFactory', $hierarchy_factory);

        stub($GLOBALS['Response'])->getFeedbackErrors()->returns(array());

        stub($art_factory)->save()->returns(true);
        $art_factory->expectOnce('save');

        $workflow = new MockWorkflow();
        $workflow->expectCallCount('before', 2);
        $workflow->setReturnValue('validate', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

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

    public function itCheckThatGlobalRulesAreValid() {
        $email   = null; //not annonymous user
        $comment = 'It did solve my problem, I let you close the artifact.';

        $this->response->expectNever('addFeedback');

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        $comment_dao->expectNever('createNewVersion');

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->expectNever('create');

        $user = mock('PFUser');
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('isValidRegardingRequiredProperty', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->expectNever('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('isValidRegardingRequiredProperty', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->expectNever('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('isValidRegardingRequiredProperty', true);
        $field3->expectNever('saveNewChangeset');
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expectNever('notify');

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));
        $changeset->setReturnValue('getValues', array());

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        $artifact->setReturnReference('getHierarchyFactory', $hierarchy_factory);

        $workflow = new MockWorkflow_Tracker_ArtifactTest_WorkflowNoPermsOnPostActionFields();
        $workflow->setReturnValue('validate', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

        $art_factory->expectNever('save');

        $email = null; //not annonymous user

        $fields_data = array(
            101 => '123',
        );

        $updated_fields_data_by_workflow = array(
            101 => '123',
            102 => '456'
        );
        stub($workflow)->checkGlobalRules($updated_fields_data_by_workflow, $factory)->once()->throws(new Tracker_Workflow_GlobalRulesViolationException());

        $this->expectException('Tracker_Exception');
        $artifact->createNewChangeset($fields_data, $comment, $user);
    }

    function testCreateNewChangesetWithoutNotification() {
        $email   = null; //not anonymous user
        $comment = '';

        $this->response->expectCallCount('addFeedback', 0);

        $comment_dao = new MockTracker_Artifact_Changeset_CommentDao();
        stub($comment_dao)->createNewVersion()->returns(true);
        $comment_dao->expectCallCount('createNewVersion', 1);

        $dao = new MockTracker_Artifact_ChangesetDao();
        $dao->setReturnValueAt(0, 'create', 1001, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->setReturnValueAt(1, 'create', 1002, array(66, 1234, null, $_SERVER['REQUEST_TIME']));
        $dao->expectCallCount('create', 1);

        $user = mock('PFUser');
        $user->setReturnValue('getId', 1234);
        $user->setReturnValue('isAnonymous', false);

        $tracker = new MockTracker();
        $tracker->setReturnValue('getGroupId', 666);
        $tracker->setReturnValue('getItemName', 'foobar');
        $tracker->setReturnValue('getFormElements', array());

        $factory = new MockTracker_FormElementFactory();

        $field1  = new MockTracker_FormElement_Field();
        $field1->setReturnValue('getId', 101);
        $field1->setReturnValue('isValid', true);
        $field1->setReturnValue('isValidRegardingRequiredProperty', true);
        $field1->setReturnValue('userCanUpdate', true);
        $field1->setReturnValue('saveNewChangeset', true);
        $field1->expectOnce('saveNewChangeset');
        $field2  = new MockTracker_FormElement_Field();
        $field2->setReturnValue('getId', 102);
        $field2->setReturnValue('isValid', true, array('*', '123'));
        $field2->setReturnValue('isValid', false, array('*', '456'));
        $field2->setReturnValue('isValidRegardingRequiredProperty', true);
        $field2->setReturnValue('userCanUpdate', true);
        $field2->setReturnValue('saveNewChangeset', true);
        $field2->expectOnce('saveNewChangeset');
        $field3  = new MockTracker_FormElement_Field();
        $field3->setReturnValue('getId', 103);
        $field3->setReturnValue('isValid', true);
        $field3->setReturnValue('isValidRegardingRequiredProperty', true);
        $field3->setReturnValue('saveNewChangeset', true);
        $field3->expectOnce('saveNewChangeset');
        $field3->setReturnValue('userCanUpdate', true);
        $factory->setReturnValue('getUsedFields', array($field1, $field2, $field3));
        $factory->setReturnValue('getAllFormElementsForTracker', array());

        $new_changeset = new MockTracker_Artifact_Changeset();
        $new_changeset->expectNever('notify', array());

        $changeset = new MockTracker_Artifact_Changeset();
        $changeset->setReturnValue('hasChanges', true);
        $changeset_value1 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value2 = new MockTracker_Artifact_ChangesetValue();
        $changeset_value3 = new MockTracker_Artifact_ChangesetValue();
        $changeset->setReturnReference('getValue', $changeset_value1, array($field1));
        $changeset->setReturnReference('getValue', $changeset_value2, array($field2));
        $changeset->setReturnReference('getValue', $changeset_value3, array($field3));
        $changeset->setReturnValue('getValues', array());

        $reference_manager = new MockReferenceManager();
        $reference_manager->expect('extractCrossRef', array(
            $comment,
            66,
            'plugin_tracker_artifact',
            666,
            $user->getId(),
            'foobar',
        ));

        $art_factory = new MockTracker_ArtifactFactory();

        $hierarchy_factory = mock('Tracker_HierarchyFactory');
        stub($hierarchy_factory)->getChildren()->returns(array());

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnReference('getChangesetDao', $dao);
        $artifact->setReturnReference('getChangesetCommentDao', $comment_dao);
        $artifact->setReturnReference('getFormElementFactory', $factory);
        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 66);
        $artifact->setReturnReference('getLastChangeset', $changeset);
        $artifact->setReturnReference('getChangeset', $new_changeset);
        $artifact->setReturnReference('getReferenceManager', $reference_manager);
        $artifact->setReturnReference('getArtifactFactory', $art_factory);
        $artifact->setReturnReference('getHierarchyFactory', $hierarchy_factory);

        stub($GLOBALS['Response'])->getFeedbackErrors()->returns(array());

        stub($art_factory)->save()->returns(true);
        $art_factory->expectOnce('save');

        $workflow = new MockWorkflow();
        $workflow->expectCallCount('before', 2);
        $workflow->setReturnValue('validate', true);
        $artifact->setReturnValue('getWorkflow', $workflow);

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

    function testGetCommentators() {
        $c1 = new MockTracker_Artifact_Changeset();
        $c2 = new MockTracker_Artifact_Changeset();
        $c3 = new MockTracker_Artifact_Changeset();
        $c4 = new MockTracker_Artifact_Changeset();

        $u1 = mock('PFUser'); $u1->setReturnValue('getUserName', 'sandrae');
        $u2 = mock('PFUser'); $u2->setReturnValue('getUserName', 'marc');

        $um = new MockUserManager();
        $um->setReturnReference('getUserById', $u1, array(101));
        $um->setReturnReference('getUserById', $u2, array(102));

        $artifact = new Tracker_ArtifactTestVersion();
        $artifact->setReturnValue('getChangesets', array($c1, $c2, $c3, $c4));
        $artifact->setReturnValue('getUserManager', $um);

        $c1->setReturnValue('getSubmittedBy', 101);
        $c2->setReturnValue('getSubmittedBy', 102);
        $c2->setReturnValue('getEmail', 'titi@example.com');
        $c3->setReturnValue('getSubmittedBy', null);
        $c3->setReturnValue('getEmail', 'toto@example.com');
        $c4->setReturnValue('getSubmittedBy', null);
        $c4->setReturnValue('getEmail', '');

        $this->assertEqual($artifact->getCommentators(), array(
            'sandrae',
            'marc',
            'toto@example.com',
        ));
    }
}

class Tracker_Artifact_ParentAndAncestorsTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->hierarchy_factory = mock('Tracker_HierarchyFactory');

        $this->sprint = anArtifact()->build();
        $this->sprint->setHierarchyFactory($this->hierarchy_factory);

        $this->user = aUser()->build();
    }

    public function itReturnsTheParentArtifactFromAncestors() {
        $release = anArtifact()->withId(1)->build();

        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint)->returns($release);

        $this->assertEqual($release, $this->sprint->getParent($this->user));
    }

    public function itReturnsNullWhenNoAncestors() {
        stub($this->hierarchy_factory)->getParentArtifact($this->user, $this->sprint)->returns(null);

        $this->assertEqual(null, $this->sprint->getParent($this->user));
    }
}

class Tracker_Artifact_getWorkflowTest extends TuleapTestCase {

    private $workflow;
    private $artifact;

    public function setUp() {
        $tracker_id = 123;
        $this->workflow = aWorkflow()->withTrackerId($tracker_id)->build();
        $tracker = aMockTracker()->withId($tracker_id)->build();
        stub($tracker)->getWorkflow()->returns($this->workflow);
        $this->artifact = anArtifact()->build();
        $this->artifact->setTracker($tracker);
    }

    public function itGetsTheWorkflowFromTheTracker() {
        $workflow = $this->artifact->getWorkflow();
        $this->assertEqual($workflow, $this->workflow);
    }

    public function itInjectsItselfInTheWorkflow() {
        $workflow = $this->artifact->getWorkflow();
        $this->assertEqual($workflow->getArtifact(), $this->artifact);
    }
}

class Tracker_Artifact_SOAPTest extends TuleapTestCase {

    private $changeset_without_comments;
    private $changeset_with_submitted_by1;
    private $changeset_with_submitted_by2;
    private $changeset_without_submitted_by;
    private $changeset_which_has_been_modified_by_another_user;

    private $tracker_id;
    private $email;

    private $timestamp1;
    private $timestamp2;
    private $timestamp3;

    private $body1;
    private $body2;
    private $body3;

    private $submitted_by1;
    private $submitted_by2;

    public function setUp() {
        parent::setUp();
        $this->tracker_id    = 123;
        $this->email         = 'martin.goyot@example.com';

        $this->timestamp1    = 1355896800;
        $this->timestamp2    = 1355896802;
        $this->timestamp3    = 1355896805;

        $this->body1         = 'coucou';
        $this->body2         = 'hibou';
        $this->body3         = 'forêt';
        $this->body4         = '';

        $this->submitted_by1 = 101;
        $this->submitted_by2 = 102;

        $this->artifact = anArtifact()->withTrackerId($this->tracker_id)->build();

        $this->changeset_with_submitted_by1                       = new Tracker_Artifact_Changeset(1, $this->artifact, $this->submitted_by1,  $this->timestamp1, null);
        $this->changeset_with_submitted_by2                       = new Tracker_Artifact_Changeset(2, $this->artifact, $this->submitted_by2,  $this->timestamp2, null);
        $this->changeset_without_submitted_by                     = new Tracker_Artifact_Changeset(3, $this->artifact, null,  $this->timestamp3, $this->email);
        $this->changeset_with_comment_with_empty_body             = new Tracker_Artifact_Changeset(4, $this->artifact, $this->submitted_by2,  $this->timestamp2, null);
        $this->changeset_with_different_submitted_by              = new Tracker_Artifact_Changeset(4, $this->artifact, $this->submitted_by2,  $this->timestamp2, null);
        $this->changeset_which_has_been_modified_by_another_user  = new Tracker_Artifact_Changeset(4, $this->artifact, $this->submitted_by2,  $this->timestamp2, null);

        $comment1 = new Tracker_Artifact_Changeset_Comment(1, $this->changeset_with_submitted_by1, 2, 3, $this->submitted_by1,  $this->timestamp1, $this->body1, 'text', 0);
        $comment2 = new Tracker_Artifact_Changeset_Comment(2, $this->changeset_with_submitted_by2, 2, 3, $this->submitted_by2,  $this->timestamp2, $this->body2, 'text', 0);
        $comment3 = new Tracker_Artifact_Changeset_Comment(3, $this->changeset_without_submitted_by, 2, 3, null,  $this->timestamp3, $this->body3, 'text', 0);
        $comment4 = new Tracker_Artifact_Changeset_Comment(4, $this->changeset_with_submitted_by2, 2, 3, $this->submitted_by2,  $this->timestamp2, $this->body4, 'text', 0);
        $comment5 = new Tracker_Artifact_Changeset_Comment(5, $this->changeset_which_has_been_modified_by_another_user, 2, 3, $this->submitted_by1,  $this->timestamp2, $this->body3, 'text', 0);

        $this->changeset_with_submitted_by1->setLatestComment($comment1);
        $this->changeset_with_submitted_by2->setLatestComment($comment2);
        $this->changeset_without_submitted_by->setLatestComment($comment3);
        $this->changeset_with_comment_with_empty_body->setLatestComment($comment4);
        $this->changeset_which_has_been_modified_by_another_user->setLatestComment($comment5);
    }

    public function itReturnsAnEmptySoapArrayWhenThereIsNoComments() {
        $changesets = array($this->changeset_with_comment_with_empty_body);
        $this->artifact->setChangesets($changesets);

        $result = $this->artifact->exportCommentsToSOAP();
        $this->assertArrayEmpty($result);
    }

    public function itReturnsASOAPArrayWhenThereAreTwoComments() {
        $changesets = array($this->changeset_with_submitted_by1, $this->changeset_with_submitted_by2);
        $this->artifact->setChangesets($changesets);

        $result = $this->artifact->exportCommentsToSOAP();
        $expected = array(
            array(
                'submitted_by' => $this->submitted_by1,
                'email'        => null,
                'submitted_on' => $this->timestamp1,
                'body'         => $this->body1,
            ),
            array(
                'submitted_by' => $this->submitted_by2,
                'email'        => null,
                'submitted_on' => $this->timestamp2,
                'body'         => $this->body2,
            )
        );

        $this->assertEqual($expected, $result);
    }

    public function itReturnsAnEmailInTheSOAPArrayWhenThereIsNoSubmittedBy() {
        $changesets = array($this->changeset_without_submitted_by);
        $this->artifact->setChangesets($changesets);

        $result = $this->artifact->exportCommentsToSOAP();
        $expected = array(array(
            'submitted_by' => null,
            'email'        => $this->email,
            'submitted_on' => $this->timestamp3,
            'body'         => $this->body3,
        ));

        $this->assertEqual($expected, $result);
    }

    public function itDoesNotReturnAnArrayWhenCommentHasAnEmptyBody() {
        $changesets = array($this->changeset_with_comment_with_empty_body);
        $this->artifact->setChangesets($changesets);

        $result = $this->artifact->exportCommentsToSOAP();
        $this->assertArrayEmpty($result);
    }

    public function itUsesChangesetSubmittedByAndNotCommentsOne() {
        $changesets = array($this->changeset_which_has_been_modified_by_another_user);
        $this->artifact->setChangesets($changesets);

        $expected = array(array(
            'submitted_by' => $this->submitted_by2,
            'email'        => null,
            'submitted_on' => $this->timestamp2,
            'body'         => $this->body3,
        ));

        $result = $this->artifact->exportCommentsToSOAP();

        $this->assertEqual($result, $expected);
    }

     public function itReturnsTheReferencesInSOAPFormat() {
        $id       = $tracker_id = $parent_id = $name = $label = $description = $use_it = $scope = $required = $notifications = $rank = 0;
        $factory  = mock('CrossReferenceFactory');
        $artifact = partial_mock('Tracker_Artifact', array('getCrossReferenceFactory'));
        $wiki_ref = array(
            'ref' => 'wiki #toto',
            'url' => 'http://example.com/le_link_to_teh_wiki'
        );
        $file_ref = array(
            'ref' => 'file #chapeau',
            'url' => 'http://example.com/files/chapeau'
        );
        $art_ref = array(
            'ref' => 'art #123',
            'url' => 'http://example.com/tracker/123'
        );
        $doc_ref = array(
            'ref' => 'doc #42',
            'url' => 'http://example.com/docman/42'
        );

        stub($artifact)->getCrossReferenceFactory()->returns($factory);
        stub($factory)->getFormattedCrossReferences()->returns(
            array(
                'source' => array($wiki_ref, $file_ref),
                'target' => array($art_ref),
                'both'   => array($doc_ref),
            )
        );
        $soap = $artifact->getCrossReferencesSOAPValues();
        $this->assertEqual($soap, array(
            $wiki_ref,
            $file_ref,
            $art_ref,
            $doc_ref
        ));
    }
}

class Tracker_Artifact_PostActionsTest extends TuleapTestCase {
    private $changeset_dao;

    public function setUp() {
        parent::setUp();
        $this->fields_data = array();
        $this->submitter   = aUser()->withId(74)->build();

        $this->changeset_dao  = mock('Tracker_Artifact_ChangesetDao');
        $this->changesets  = array(new Tracker_Artifact_Changeset_Null());
        $factory     = mock('Tracker_FormElementFactory');
        stub($factory)->getAllFormElementsForTracker()->returns(array());
        stub($factory)->getUsedFields()->returns(array());

        $this->artifact_factory = mock('Tracker_ArtifactFactory');
        $this->workflow = mock('Workflow');
        $this->changeset_factory  = mock('Tracker_Artifact_ChangesetFactory');
        stub($this->changeset_factory)->getChangeset()->returns(new Tracker_Artifact_Changeset(
            123,
            aMockArtifact()->build(),
            12,
            21,
            ''
        ));
        $tracker        = stub('Tracker')->getWorkflow()->returns($this->workflow);
        $this->artifact = anArtifact()
            ->withId(42)
            ->withChangesets($this->changesets)
            ->withTracker($tracker)
            ->build();

        $this->submitted_on = $_SERVER['REQUEST_TIME'];

        $fields_validator = mock('Tracker_Artifact_Changeset_NewChangesetFieldsValidator');
        stub($fields_validator)->validate()->returns(true);

        $comment_dao = stub('Tracker_Artifact_Changeset_CommentDao')->createNewVersion()->returns(true);

        $this->creator = new Tracker_Artifact_Changeset_NewChangesetCreator(
            $fields_validator,
            $factory,
            $this->changeset_dao,
            $comment_dao,
            $this->artifact_factory,
            mock('EventManager'),
            mock('ReferenceManager'),
            mock('Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder')
        );
    }

    public function itCallsTheAfterMethodOnWorkflowWhenCreateNewChangeset() {
        stub($this->changeset_dao)->create()->returns(true);
        stub($this->artifact_factory)->save()->returns(true);
        expect($this->workflow)->after($this->fields_data, new IsAExpectation('Tracker_Artifact_Changeset'), end($this->changesets))->once();

        $this->creator->create(
            $this->artifact,
            $this->fields_data,
            '',
            $this->submitter,
            $this->submitted_on,
            false,
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
    }

    public function itDoesNotCallTheAfterMethodOnWorkflowWhenSaveOfArtifactFailsOnNewChangeset() {
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
            Tracker_Artifact_Changeset_Comment::TEXT_COMMENT
        );
    }
}

class Tracker_Artifact_getSoapValueTest extends TuleapTestCase {
    private $artifact;
    private $user;
    private $id = 1235;
    private $tracker_id = 567;
    private $submitted_by = 891;
    private $submitted_on = 111213;
    private $use_artifact_permissions = true;
    private $last_update_date = 654683;

    public function setUp() {
        parent::setUp();
        $this->user     = mock('PFUser');

        $this->last_changeset = mock('Tracker_Artifact_Changeset');
        stub($this->last_changeset)->getSubmittedOn()->returns($this->last_update_date);
        stub($this->last_changeset)->getValues()->returns(array());

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->formelement_factory)->getUsedFieldsForSoap()->returns(array());

        $this->artifact = partial_mock(
            'Tracker_Artifact',
            array(
                'userCanView',
                'getCrossReferencesSOAPValues',
            ),
            array($this->id, $this->tracker_id, $this->submitted_by, $this->submitted_on, $this->use_artifact_permissions)
        );
        stub($this->artifact)->userCanView()->returns(true);
        stub($this->artifact)->getCrossReferencesSOAPValues()->returns(array(array('ref' => 'art #123', 'url' => '/path/to/art=123')));
        $this->artifact->setChangesets(array($this->last_changeset));
        $this->artifact->setFormElementFactory($this->formelement_factory);
        $this->artifact->setTracker(aTracker()->withId($this->tracker_id)->build());
    }

    public function itReturnsEmptyArrayIfUserCannotViewArtifact() {
        $artifact = partial_mock('Tracker_Artifact', array('userCanView'));
        $artifact->setTracker(aTracker()->build());
        $artifact->setFormElementFactory($this->formelement_factory);
        $user     = mock('PFUser');
        stub($artifact)->userCanView($user)->returns(false);

        $this->assertArrayEmpty($artifact->getSoapValue($user));
    }

    public function itReturnsDataIfUserCanViewArtifact() {
        $artifact = partial_mock('Tracker_Artifact', array('userCanView', 'getCrossReferencesSOAPValues'), array('whatever', 'whatever', 'whatever', 'whatever', 'whatever'));
        $artifact->setChangesets(array($this->last_changeset));
        $artifact->setTracker(aTracker()->build());
        $artifact->setFormElementFactory($this->formelement_factory);
        $user     = mock('PFUser');
        stub($artifact)->userCanView($user)->returns(true);

        $this->assertArrayNotEmpty($artifact->getSoapValue($user));
    }

    public function itHasBasicArtifactInfo() {
        $soap_value = $this->artifact->getSoapValue($this->user);
        $this->assertIdentical($soap_value['artifact_id'], $this->id);
        $this->assertIdentical($soap_value['tracker_id'], $this->tracker_id);
        $this->assertIdentical($soap_value['submitted_by'], $this->submitted_by);
        $this->assertIdentical($soap_value['submitted_on'], $this->submitted_on);
    }

    public function itContainsCrossReferencesValue() {
        $soap_value = $this->artifact->getSoapValue($this->user);
        $this->assertEqual($soap_value['cross_references'][0], array('ref' => 'art #123', 'url' => '/path/to/art=123'));
    }

    public function itHasALastUpdateDate() {
        $soap_value = $this->artifact->getSoapValue($this->user);
        $this->assertIdentical($soap_value['last_update_date'], $this->last_update_date);
    }
}

class Tracker_Artifact_getSoapValueWithFieldValuesTest extends TuleapTestCase {
    private $artifact;
    private $user;
    private $field;

    public function setUp() {
        parent::setUp();
        $this->user = mock('PFUser');

        $this->field_id = 123242;

        $this->field           = aMockField()->build();
        $this->changeset_value = mock('Tracker_Artifact_ChangesetValue');
        $this->last_changeset  = stub('Tracker_Artifact_Changeset')->getValues()->returns(array($this->field_id => $this->changeset_value));

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        stub($this->formelement_factory)->getFormElementById()->returns($this->field);

        $this->tracker = aTracker()->build();

        $this->artifact = partial_mock(
            'Tracker_Artifact',
            array(
                'userCanView',
                'getCrossReferencesSOAPValues',
            ),
            array('whatever', 'whatever', 'whatever', 'whatever', 'whatever')
        );
        stub($this->artifact)->userCanView()->returns(true);
        $this->artifact->setChangesets(array($this->last_changeset));
        $this->artifact->setFormElementFactory($this->formelement_factory);
        $this->artifact->setTracker($this->tracker);
    }

    public function itFetchFieldFromFactory() {
        expect($this->formelement_factory)->getUsedFieldsForSoap($this->tracker)->once();
        stub($this->formelement_factory)->getUsedFieldsForSoap()->returns(array());
        $this->artifact->getSoapValue($this->user);
    }

    public function itHasAValueFromField() {
        stub($this->formelement_factory)->getUsedFieldsForSoap()->returns(array($this->field));

        stub($this->field)->getSoapValue($this->user, $this->last_changeset)->returns('whatever')->once();

        $soap_value = $this->artifact->getSoapValue($this->user);
        $this->assertEqual($soap_value['value'][0], 'whatever');
    }

    public function itDoesntModifySoapValueIfNoFieldValues() {
        stub($this->formelement_factory)->getUsedFieldsForSoap()->returns(array($this->field));

        stub($this->field)->getSoapValue()->returns(null);

        $soap_value = $this->artifact->getSoapValue($this->user);
        $this->assertArrayEmpty($soap_value['value']);
    }
}

class Tracker_Artifact_ExportToXMLTest extends TuleapTestCase {

    private $user_manager;

    public function setUp() {
        $this->user_manager = mock('UserManager');
        UserManager::setInstance($this->user_manager);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown() {
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();

        parent::tearDown();
    }

    public function itExportsTheArtifactToXML() {
        $user = aUser()->withId(101)->withLdapId('ldap_O1')->withUserName('user_01')->build();
        stub($this->user_manager)->getUserById(101)->returns($user);
        stub($this->formelement_factory)->getUsedFileFields()->returns(array());

        $changeset_01 = stub('Tracker_Artifact_Changeset')->getsubmittedBy()->returns(101);
        $changeset_02 = stub('Tracker_Artifact_Changeset')->getsubmittedBy()->returns(101);

        $project = stub('Project')->getID()->returns(101);
        $tracker = aTracker()->withId(101)->withProject($project)->build();

        $artifact = anArtifact()->withTracker($tracker)
                                      ->withId(101)
                                      ->withChangesets(array($changeset_01, $changeset_02))
                                      ->build();

        $artifacts_node = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <artifacts/>');

        $text_field_01 = stub('Tracker_FormElement_Field_Text')->getName()->returns('text_01');
        stub($text_field_01)->getTracker()->returns($tracker);

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(1, mock('Tracker_Artifact_Changeset'), $text_field_01, true, 'value_01', 'text');
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(2, mock('Tracker_Artifact_Changeset'), $text_field_01, true, 'value_02', 'text');

        stub($changeset_01)->getArtifact()->returns($artifact);
        stub($changeset_01)->getValues()->returns(array($value_01));

        stub($changeset_02)->getArtifact()->returns($artifact);
        stub($changeset_02)->getValues()->returns(array($value_02));

        $archive = mock('Tuleap\Project\XML\Export\ArchiveInterface');

        $user_xml_exporter      = new UserXmlExporter($this->user_manager, mock('UserXMLExportedCollection'));
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
