<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
require_once('bootstrap.php');
Mock::generatePartial(
    'Tracker_Artifact_Changeset',
    'Tracker_Artifact_ChangesetTestVersion',
    array(
        'getId',
        'getValueDao',
        'getFormElementFactory',
        'getArtifact',
        'executePostCreationActions',
        'getUserManager',
        'getTracker',
        'getComment',
        'getMailGatewayConfig',
        'isNotificationAssignedToEnabled',
        'getMailSender',
    )
);

Mock::generate('Tracker_Artifact_Changeset');
Mock::generate('Tracker_FormElement_Field_Date');
Mock::generate('Tracker_Artifact_Changeset_ValueDao');
Mock::generate('DataAccessResult');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Artifact_ChangesetValue_Date');
Mock::generate('Tracker_Artifact_ChangesetValue_List');
Mock::generate('Tracker_FormElement_Field_Selectbox');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker');
Mock::generate('Tracker_Artifact_Changeset_Comment');
Mock::generate('UserManager');

Mock::generate('BaseLanguage');

Mock::generate('UserHelper');
Mock::generate('PFUser');
Mock::generate('BaseLanguageFactory');

class Tracker_Artifact_ChangesetTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        $GLOBALS['sys_default_domain'] = 'localhost';
        ForgeConfig::set('sys_https_host', '');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        unset($GLOBALS['sys_default_domain']);
        parent::tearDown();
    }

    function _testGetValue()
    {
        $field = new MockTracker_FormElement_Field_Date();
        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $dao   = new MockTracker_Artifact_Changeset_ValueDao();
        $dar   = new MockDataAccessResult();
        $fact  = new MockTracker_FormElementFactory();
        $value = new MockTracker_Artifact_ChangesetValue_Date();
        $um    = new MockUserManager();

        $dar->setReturnValue('current', array('changeset_id' => 1, 'field_id' => 2, 'id' => 3, 'has_changed' => 0));
        $dar->setReturnValueAt(0, 'valid', true);
        $dar->setReturnValue('valid', false);
        $dao->setReturnReference('searchById', $dar);

        $fact->setReturnReference('getFieldById', $field);

        $field->setReturnValue('getId', 2);
        $field->setReturnReference('getChangesetValue', $value);

        $changeset = new Tracker_Artifact_ChangesetTestVersion();
        $changeset->setReturnReference('getValueDao', $dao);
        $changeset->setReturnReference('getFormElementFactory', $fact);
        $changeset->setReturnReference('getUserManager', $um);

        $this->assertIsA($changeset->getValue($field), 'Tracker_Artifact_ChangesetValue_Date');
    }

    function testDiffToPrevious()
    {
        $field1             = new MockTracker_FormElement_Field_Date();
        $value1_previous    = new MockTracker_Artifact_ChangesetValue_Date();
        $value1_current     = new MockTracker_Artifact_ChangesetValue_Date();
        $field2             = new MockTracker_FormElement_Field_Date();
        $value2_previous    = new MockTracker_Artifact_ChangesetValue_Date();
        $value2_current     = new MockTracker_Artifact_ChangesetValue_Date();
        $dao                = new MockTracker_Artifact_Changeset_ValueDao();
        $dar                = new MockDataAccessResult();
        $fact               = new MockTracker_FormElementFactory();
        $value              = new MockTracker_Artifact_ChangesetValue_Date();
        $artifact           = new MockTracker_Artifact();
        $previous_changeset = new MockTracker_Artifact_Changeset();
        $um                 = new MockUserManager();

        $current_changeset = new Tracker_Artifact_ChangesetTestVersion();

        $previous_changeset->setReturnValue('getId', 65);
        $previous_changeset->setReturnReference('getValue', $value1_previous, array($field1));
        $previous_changeset->setReturnReference('getValue', $value2_previous, array($field2));
        $previous_changeset->setReturnReference('getUserManager', $um);

        $artifact->setReturnReference('getPreviousChangeset', $previous_changeset, array(66));

        $dar->setReturnValueAt(0, 'current', array('changeset_id' => 66, 'field_id' => 1, 'id' => 11, 'has_changed' => 1));
        $dar->setReturnValueAt(1, 'current', array('changeset_id' => 66, 'field_id' => 2, 'id' => 21, 'has_changed' => 0));
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(2, 'valid', false);
        $dao->setReturnReference('searchById', $dar);

        $fact->setReturnReference('getFieldById', $field1, array(1));
        $fact->setReturnReference('getFieldById', $field2, array(2));

        $field1->setReturnValue('getId', 1);
        $field1->setReturnValue('getLabel', 'field1');
        $field1->setReturnValue('userCanRead', true);
        $field1->setReturnReference('getChangesetValue', $value1_current, array('*', 11, 1));

        $value1_previous->expectNever('hasChanged');
        $value1_current->setReturnValue('hasChanged', true);
        $value1_current->setReturnValue('diff', 'has changed', array($value1_previous, '*', null));

        $field2->setReturnValue('getId', 2);
        $field2->setReturnValue('getLabel', 'field2');
        $field2->setReturnValue('userCanRead', true);
        $field2->setReturnReference('getChangesetValue', $value2_current, array('*', 21, 0));

        $value2_previous->expectNever('hasChanged');
        $value2_current->setReturnValue('hasChanged', false);
        $value2_current->expectNever('diff');

        $current_changeset->setReturnValue('getId', 66);
        $current_changeset->setReturnReference('getValueDao', $dao);
        $current_changeset->setReturnReference('getFormElementFactory', $fact);
        $current_changeset->setReturnReference('getArtifact', $artifact);
        $current_changeset->setReturnReference('getUserManager', $um);

        $result = $current_changeset->diffToprevious();

        $this->assertPattern('/field1/', $result);
        $this->assertNoPattern('/field2/', $result);
    }

    public function testDisplayDiffShouldNotStripHtmlTagsInPlainTextFormat()
    {
        $diff   = "@@ -1 +1 @@
- Quelle est la couleur <b> du <i> cheval blanc d'Henri IV?
+ Quelle est la couleur <b> du <i> <s> cheval blanc d'Henri IV?";
        $format = 'text';
        $field  = new MockTracker_FormElement_Field_Date();
        $field->setReturnValue('getLabel', 'Summary');

        $changeset = new Tracker_Artifact_Changeset(null, null, null, null, null);
        $result    = $changeset->displayDiff($diff, $format, $field);
        $this->assertPattern('%Quelle est la couleur <b> du <i> <s> cheval blanc%', $result);
        $this->assertPattern('%Summary%', $result);
    }
}

class Tracker_Artifact_ChangesetDeleteTest extends TuleapTestCase
{
    private $user;
    private $changeset_id;
    private $changeset;

    public function setUp()
    {
        parent::setUp();
        $this->tracker      = aMockTracker()->build();
        $artifact     = anArtifact()->withTracker($this->tracker)->build();
        $this->user         = stub('PFUser')->isSuperUser()->returns(true);
        $this->changeset_id = 1234;
        $this->changeset    = partial_mock(
            'Tracker_Artifact_Changeset',
            array('getCommentDao', 'getChangesetDao', 'getFormElementFactory', 'getValueDao'),
            array($this->changeset_id, $artifact, null, null, null)
        );
    }

    public function itDeletesCommentsValuesAndChangeset()
    {
        stub($this->tracker)->userIsAdmin($this->user)->returns(true);

        $changeset_dao = mock('Tracker_Artifact_ChangesetDao');
        $changeset_dao->expectOnce('delete', array($this->changeset_id));
        stub($this->changeset)->getChangesetDao()->returns($changeset_dao);

        $comment_dao = mock('Tracker_Artifact_Changeset_CommentDao');
        $comment_dao->expectOnce('delete', array($this->changeset_id));
        stub($this->changeset)->getCommentDao()->returns($comment_dao);

        $value_dao = mock('Tracker_Artifact_Changeset_ValueDao');
        $value_dao->expectOnce('delete', array($this->changeset_id));
        stub($this->changeset)->getValueDao()->returns($value_dao);

        stub($value_dao)->searchById($this->changeset_id)->returnsDar(
            array('id' => 1025, 'field_id' => 125),
            array('id' => 1026, 'field_id' => 126)
        );

        $formelement_factory = mock('Tracker_FormElementFactory');
        $field_text = mock('Tracker_FormElement_Field_Text');
        $field_text->expectOnce('deleteChangesetValue', array('*', 1025));
        stub($formelement_factory)->getFieldById(125)->returns($field_text);
        $field_float = mock('Tracker_FormElement_Field_Float');
        $field_float->expectOnce('deleteChangesetValue', array('*', 1026));
        stub($formelement_factory)->getFieldById(126)->returns($field_float);

        stub($this->changeset)->getFormElementFactory()->returns($formelement_factory);

        $this->changeset->delete($this->user);
    }
}

class Tracker_Artifact_Changeset_classnamesTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->changeset_with_changes = partial_mock('Tracker_Artifact_Changeset', array('getComment'), array('*', '*', 101, '*', '*'));
        $this->changeset_with_both_changes_and_comment = partial_mock('Tracker_Artifact_Changeset', array('getComment'), array('*', '*', 101, '*', '*'));
        $this->changeset_with_comment = partial_mock('Tracker_Artifact_Changeset', array('getComment'), array('*', '*', 101, '*', '*'));
        $this->changeset_by_workflowadmin = partial_mock('Tracker_Artifact_Changeset', array('getComment'), array('*', '*', 90, '*', '*'));
        $this->changeset_by_anonymous = partial_mock('Tracker_Artifact_Changeset', array('getComment'), array('*', '*', null, '*', 'email'));

        $comment = mock('Tracker_Artifact_Changeset_Comment');
        $empty_comment = stub('Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(true);

        stub($this->changeset_with_changes)->getComment()->returns($empty_comment);
        stub($this->changeset_with_both_changes_and_comment)->getComment()->returns($comment);
        stub($this->changeset_with_comment)->getComment()->returns($comment);
        stub($this->changeset_by_workflowadmin)->getComment()->returns($comment);
        stub($this->changeset_by_anonymous)->getComment()->returns($comment);
    }

    public function itContainsChanges()
    {
        $pattern = '/'. preg_quote('tracker_artifact_followup-with_changes') .'/';
        $this->assertPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertNoPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames(false));
    }

    public function itContainsComment()
    {
        $pattern = '/'. preg_quote('tracker_artifact_followup-with_comment') .'/';
        $this->assertPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames(false));
        $this->assertPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertNoPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames('The changes'));
    }

    public function itContainsSystemUser()
    {
        $pattern = '/'. preg_quote('tracker_artifact_followup-by_system_user') .'/';
        $this->assertNoPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames(false));
        $this->assertNoPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertNoPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertNoPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
    }
}
