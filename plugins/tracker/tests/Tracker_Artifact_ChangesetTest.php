<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
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
        'sendNotification',
        'getUserManager',
        'getTracker',
        'getComment',
        'getMailGatewayConfig',
        'isNotificationAssignedToEnabled'
    )
);

Mock::generate('Tracker_Artifact_Changeset');
Mock::generate('Tracker_FormElement_Field_Date');
Mock::generate('Tracker_Artifact_Changeset_ValueDao');
require_once('common/dao/include/DataAccessResult.class.php');
Mock::generate('DataAccessResult');
Mock::generate('Tracker_FormElementFactory');
Mock::generate('Tracker_Artifact_ChangesetValue_Date');
Mock::generate('Tracker_Artifact_ChangesetValue_List');
Mock::generate('Tracker_FormElement_Field_Selectbox');
Mock::generate('Tracker_Artifact');
Mock::generate('Tracker');
Mock::generate('Tracker_Artifact_Changeset_Comment');
require_once('common/user/UserManager.class.php');
Mock::generate('UserManager');

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

Mock::generate('UserHelper');
Mock::generate('PFUser');
Mock::generate('BaseLanguageFactory');

class Tracker_Artifact_ChangesetTest extends TuleapTestCase {

    function setUp() {
        $GLOBALS['Language']           = new MockBaseLanguage();
        $GLOBALS['sys_default_domain'] = 'localhost';
        $GLOBALS['sys_force_ssl']      = 0;
        $this->recipient_factory       = mock('Tracker_Artifact_MailGateway_RecipientFactory');
    }

    function tearDown() {
        unset($GLOBALS['Language']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_force_ssl']);
    }

    function _testGetValue() {
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

    function testDiffToPrevious() {
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

        $this->assertPattern('/field1/', $current_changeset->diffToprevious());
        $this->assertNoPattern('/field2/', $current_changeset->diffToprevious());
    }

    function testNotify() {
        $defs = array(
            array(
                'has_changed'              => 1,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 0,
                'recipients'               => array(),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 1,
                'recipients'               => array('a_user'),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 0,
                'recipients'               => array('should_not_appear'),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 0,
                'hasNotifications'         => 1,
                'recipients'               => array('should_not_appear_(not_supported)'),
            ),
            array(
                'has_changed'              => 0,
                'isNotificationsSupported' => 1,
                'hasNotifications'         => 1,
                'recipients'               => array('multiple_users', 'email@example.com'),
            ),
        );

        $fact     = new MockTracker_FormElementFactory();
        $dar      = new MockDataAccessResult();
        $dao      = new MockTracker_Artifact_Changeset_ValueDao();
        $artifact = new MockTracker_Artifact();
        $tracker  = new MockTracker();
        $um       = new MockUserManager();
        $comment  = new MockTracker_Artifact_Changeset_Comment();

        $i = 0;
        // try DRY in unit tests also... build mocks automatically
        foreach ($defs as $d) {
            $id = $i + 1;
            $changeset_value_id = 1000 + $id;
            $dar->setReturnValueAt($i++, 'current', array(
                'changeset_id' => 66,
                'field_id'     => $id,
                'id'           => $changeset_value_id,
                'has_changed'  => $d['has_changed'],
            ));
            $f = new MockTracker_FormElement_Field_Selectbox();
            $fact->setReturnReference('getFieldById', $f, array($id));
            $f->setReturnValue('getId', $id);
            $f->setReturnValue('getLabel', ('field_'.$id));
            $f->setReturnValue('isNotificationsSupported', $d['isNotificationsSupported']);
            $f->setReturnValue('hasNotifications', $d['hasNotifications']);
            $f->setReturnValue('getRecipients', $d['recipients']);

            $p = new MockTracker_Artifact_ChangesetValue_List();
            $c = new MockTracker_Artifact_ChangesetValue_List();
            $c->setReturnValue('hasChanged', $d['has_changed']);
            $c->setReturnValue('diff', 'has changed', array($p));
            $f->setReturnReference('getChangesetValue', $c, array('*', $changeset_value_id, $d['has_changed']));
            unset($f);
        }
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt($i, 'valid', false);
        $dao->setReturnReference('searchById', $dar);

        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getCommentators', array('comment1', 'comment2'));
        $artifact->setReturnValue('getId', 666);

        $tracker->setReturnValue('getItemName', 'story');
        $tracker->setReturnValue('isNotificationStopped', false);
        $tracker->setReturnValue('getRecipients', array(
            array(
                'recipients'        => array(
                    'global1',
                    'global2',
                ),
                'on_updates'        => false,
                'check_permissions' => true,
            ),
            array(
                'recipients'        => array(
                    'dont_check_perms',
                    'global3',
                    'email@example.com',
                ),
                'on_updates'        => true,
                'check_permissions' => false,
            ),
        ));

        $config = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig');

        $current_changeset = partial_mock(
            'Tracker_Artifact_Changeset',
            array(
                'getId',
                'getValueDao',
                'getFormElementFactory',
                'getArtifact',
                'sendNotification',
                'getUserManager',
                'getTracker',
                'getComment',
                'getMailGatewayConfig',
                'isNotificationAssignedToEnabled',
                'getLogger',
            )
        );
        $current_changeset->setReturnValue('getId', 66);
        $current_changeset->setReturnValue('isNotificationAssignedToEnabled', false);
        $current_changeset->setReturnReference('getValueDao', $dao);
        $current_changeset->setReturnReference('getFormElementFactory', $fact);
        $current_changeset->setReturnReference('getArtifact', $artifact);
        $current_changeset->setReturnReference('getUserManager', $um);
        $current_changeset->setReturnReference('getTracker', $tracker);
        $current_changeset->setReturnReference('getComment', $comment);
        $current_changeset->setReturnReference('getMailGatewayConfig', $config);
        $current_changeset->setReturnReference('getLogger', mock('Logger'));

        $expected_body = <<<BODY
story #666
<http://{$GLOBALS['sys_default_domain']}/tracker/?aid=666>

BODY;
        $current_changeset->expect(
            'sendNotification',
            array(
                //the recipients
                array(
                    'a_user',
                    'multiple_users',
                    'email@example.com',
                    'dont_check_perms',
                    'global3',
                    'comment1',
                    'comment2',
                ),

                //the headers
                array(),

                //the subject
                '[story #666]',

                //the body
                $expected_body
            )
        );
        $current_changeset->notify();
    }

    function testNotifyStopped() {
        $changeset = new Tracker_Artifact_ChangesetTestVersion();
        $tracker  = new MockTracker();
        $tracker->setReturnValue('isNotificationStopped', true);
        $changeset->setReturnReference('getTracker', $tracker);
        $changeset->expectNever('getFormElementFactory');
        $changeset->expectNever('sendNotification');
        $changeset->notify();
    }

    function testChangesetShouldUseUserLanguageInGetBody() {
        $user = mock('PFUser');
        $userLanguage = new MockBaseLanguage();
        $GLOBALS['Language']->expectNever('getText');
        $userLanguage->expectAtLeastOnce('getText');
        $changeset = $this->buildChangeSet($user);
        $changeset->getBodyText(false, $user, $userLanguage, false);
    }

    function testChangesetShouldUseUserLanguageInBuildMessage() {
        $GLOBALS['Language']->expectNever('getText');
        $userLanguage = new MockBaseLanguage();
        $userLanguage->expectAtLeastOnce('getText');

        $user = mock('PFUser');
        $user->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user->setReturnValue('getLanguage', $userLanguage);

        $changeset = $this->buildChangeSet($user);
        stub($changeset)->getUserFromRecipientName('user01')->returns($user);

        $config = stub('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig')->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($changeset)->getMailGatewayConfig()->returns($config);

        $recipients = array(
            'user01' => false
        );

        $changeset->buildOneMessageForMultipleRecipients($recipients, true);
    }

    public function testItSendsOneMailPerRecipient() {
        $userLanguage = new MockBaseLanguage();

        $user1 = mock('PFUser');
        $user1->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user1->setReturnValue('getLanguage', $userLanguage);
        stub($user1)->getId()->returns(102);

        $user2 = mock('PFUser');
        $user2->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user2->setReturnValue('getLanguage', $userLanguage);
        stub($user2)->getId()->returns(103);

        $user3 = mock('PFUser');
        $user3->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $user3->setReturnValue('getLanguage', $userLanguage);
        stub($user3)->getId()->returns(104);

        $changeset = $this->buildChangeSet($user1);
        stub($changeset)->getUserFromRecipientName('user01')->returns($user1);
        stub($changeset)->getUserFromRecipientName('user02')->returns($user2);
        stub($changeset)->getUserFromRecipientName('user03')->returns($user3);

        $config = stub('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig')->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($changeset)->getMailGatewayConfig()->returns($config);

        $recipient1 = stub('Tracker_Artifact_MailGateway_Recipient')->getEmail()->returns('email1');
        $recipient2 = stub('Tracker_Artifact_MailGateway_Recipient')->getEmail()->returns('email2');
        $recipient3 = stub('Tracker_Artifact_MailGateway_Recipient')->getEmail()->returns('email3');

        stub($this->recipient_factory)->getFromUserAndChangeset($user1, '*')->returns($recipient1);
        stub($this->recipient_factory)->getFromUserAndChangeset($user2, '*')->returns($recipient2);
        stub($this->recipient_factory)->getFromUserAndChangeset($user3, '*')->returns($recipient3);

        $recipients = array(
            'user01' => false,
            'user02' => false,
            'user03' => false
        );

        $messages = $changeset->buildAMessagePerRecipient($recipients, true);

        $this->assertEqual(count($messages),3);
    }

    private function buildChangeSet($user) {
        $uh = new MockUserHelper();

        $tracker = new MockTracker();

        $a = new MockTracker_Artifact();
        stub($a)->getId()->returns(111);
        $a->setReturnValue('getTracker', $tracker);

        $um = new MockUserManager();
        $um->setReturnValue('getUserById', $user);

        $languageFactory = new MockBaseLanguageFactory();

        $changeset = TestHelper::getPartialMock(
            'Tracker_Artifact_Changeset',
            array(
                'getUserHelper',
                'getUserManager',
                'getArtifact',
                'getComment',
                'getLanguageFactory',
                'getUserFromRecipientName',
                'getRecipientFactory',
                'getMailGatewayConfig',
                'isNotificationAssignedToEnabled'
            )
        );
        $changeset->setReturnValue('getUserHelper', $uh);
        $changeset->setReturnValue('getUserManager', $um);
        $changeset->setReturnValue('getArtifact', $a);
        $changeset->setReturnValue('getLanguageFactory', $languageFactory);
        $changeset->setReturnValue('getRecipientFactory', $this->recipient_factory);
        $changeset->setReturnValue('isNotificationAssignedToEnabled', false);

        return $changeset;
    }

    public function testDisplayDiffShouldNotStripHtmlTagsInPlainTextFormat() {
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

    public function itCleansUserFromRecipientsWhenUserCantReadAtLeastOneChangedField() {
        $field1             = new MockTracker_FormElement_Field_Date();
        $value1_previous    = new MockTracker_Artifact_ChangesetValue_Date();
        $value1_current     = new MockTracker_Artifact_ChangesetValue_Date();
        $dao                = new MockTracker_Artifact_Changeset_ValueDao();
        $dar                = new MockDataAccessResult();
        $fact               = new MockTracker_FormElementFactory();
        $artifact           = new MockTracker_Artifact();
        $previous_changeset = new MockTracker_Artifact_Changeset();
        $um                 = new MockUserManager();
        $comment            = new MockTracker_Artifact_Changeset_Comment();

        $current_changeset = new Tracker_Artifact_ChangesetTestVersion();

        $previous_changeset->setReturnValue('getId', 65);
        $previous_changeset->setReturnReference('getValue', $value1_previous, array($field1));
        $previous_changeset->setReturnReference('getUserManager', $um);

        $artifact->setReturnReference('getPreviousChangeset', $previous_changeset, array(66));

        $dar->setReturnValueAt(0, 'current', array('changeset_id' => 66, 'field_id' => 1, 'id' => 11, 'has_changed' => 1));
        $dar->setReturnValue('valid', true);
        $dar->setReturnValueAt(2, 'valid', false);
        $dao->setReturnReference('searchById', $dar);

        $fact->setReturnReference('getFieldById', $field1, array(1));

        $field1->setReturnValue('getId', 1);
        $field1->setReturnValue('getLabel', 'field1');
        $field1->setReturnValue('userCanRead', false);
        $field1->setReturnReference('getChangesetValue', $value1_current, array('*', 11, 1));

        $value1_previous->expectNever('hasChanged');
        $value1_current->setReturnValue('hasChanged', true);
        $value1_current->setReturnValue('diff', 'has changed', array($value1_previous, '*'));

        $comment->setReturnValue('hasEmptyBody', true);

        $current_changeset->setReturnValue('getId', 66);
        $current_changeset->setReturnReference('getValueDao', $dao);
        $current_changeset->setReturnReference('getFormElementFactory', $fact);
        $current_changeset->setReturnReference('getArtifact', $artifact);
        $current_changeset->setReturnReference('getUserManager', $um);
        $current_changeset->setReturnReference('getComment', $comment);

        $recipients = array("recipient1" => true, "recipient2" => true, "recipient3" => true);

        $user1 = stub('PFUser')->getUserName()->returns('recipient1');
        $user2 = stub('PFUser')->getUserName()->returns('recipient2');
        $user3 = stub('PFUser')->getUserName()->returns('recipient3');

        $um->setReturnReference('getUserByUserName', $user1);
        $um->setReturnReference('getUserByUserName', $user2);
        $um->setReturnReference('getUserByUserName', $user3);

        $current_changeset->removeRecipientsThatMayReceiveAnEmptyNotification($recipients);
        $this->assertEqual($recipients, array());
    }

    public function itCleansUserFromRecipientsWhenUserHasUnsubscribedFromArtifact() {
        $artifact          = mock('Tracker_Artifact');
        $current_changeset = new Tracker_Artifact_ChangesetTestVersion();
        $um                = new MockUserManager();
        $current_changeset->setReturnReference('getUserManager', $um);
        $current_changeset->setReturnReference('getArtifact', $artifact);
        $recipients   = array("recipient1" => true, "recipient2" => true, "recipient3" => true);

        $user1 = stub('PFUser')->getUserName()->returns('recipient1');
        $user2 = stub('PFUser')->getUserName()->returns('recipient2');
        $user3 = stub('PFUser')->getUserName()->returns('recipient3');

        $um->setReturnReference('getUserByUserName', $user1);
        $um->setReturnReference('getUserByUserName', $user2);
        $um->setReturnReference('getUserByUserName', $user3);

        $user1->setReturnValue('getId', 101);
        $user2->setReturnValue('getId', 102);
        $user3->setReturnValue('getId', 103);

        $unsubscribers = array(101, 102, 103);
        $artifact->setReturnValue('getUnsubscribersIds', $unsubscribers);

        $current_changeset->removeRecipientsThatHaveUnsubscribedArtifactNotification($recipients);
        $this->assertEqual($recipients, array());
    }
}

class Tracker_Artifact_ChangesetDeleteTest extends TuleapTestCase {
    private $user;
    private $changeset_id;
    private $changeset;

    public function setUp() {
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

    public function itDeletesCommentsValuesAndChangeset() {
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

class Tracker_Artifact_Changeset_classnamesTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->changeset_with_changes = partial_mock('Tracker_Artifact_Changeset', array('diffToPrevious', 'getComment'), array('*', '*', 101, '*', '*'));
        $this->changeset_with_both_changes_and_comment = partial_mock('Tracker_Artifact_Changeset', array('diffToPrevious', 'getComment'), array('*', '*', 101, '*', '*'));
        $this->changeset_with_comment = partial_mock('Tracker_Artifact_Changeset', array('diffToPrevious', 'getComment'), array('*', '*', 101, '*', '*'));
        $this->changeset_by_workflowadmin = partial_mock('Tracker_Artifact_Changeset', array('diffToPrevious', 'getComment'), array('*', '*', 90, '*', '*'));
        $this->changeset_by_anonymous = partial_mock('Tracker_Artifact_Changeset', array('diffToPrevious', 'getComment'), array('*', '*', null, '*', 'email'));

        $comment = mock('Tracker_Artifact_Changeset_Comment');
        $empty_comment = stub('Tracker_Artifact_Changeset_Comment')->hasEmptyBody()->returns(true);

        stub($this->changeset_with_changes)->getComment()->returns($empty_comment);
        stub($this->changeset_with_both_changes_and_comment)->getComment()->returns($comment);
        stub($this->changeset_with_comment)->getComment()->returns($comment);
        stub($this->changeset_by_workflowadmin)->getComment()->returns($comment);
        stub($this->changeset_by_anonymous)->getComment()->returns($comment);

        stub($this->changeset_with_changes)->diffToPrevious()->returns('The changes');
        stub($this->changeset_with_both_changes_and_comment)->diffToPrevious()->returns('The changes');
        stub($this->changeset_with_comment)->diffToPrevious()->returns(false);
        stub($this->changeset_by_workflowadmin)->diffToPrevious()->returns('The changes');
        stub($this->changeset_by_anonymous)->diffToPrevious()->returns('The changes');
    }

    public function itContainsChanges() {
        $pattern = '/'. preg_quote('tracker_artifact_followup-with_changes') .'/';
        $this->assertPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames());
        $this->assertPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames());
        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames());
        $this->assertPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames());

        $this->assertNoPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames());
    }

    public function itContainsComment() {
        $pattern = '/'. preg_quote('tracker_artifact_followup-with_comment') .'/';
        $this->assertPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames());
        $this->assertPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames());
        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames());
        $this->assertPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames());

        $this->assertNoPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames());
    }

    public function itContainsSystemUser() {
        $pattern = '/'. preg_quote('tracker_artifact_followup-by_system_user') .'/';
        $this->assertNoPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames());
        $this->assertNoPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames());
        $this->assertNoPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames());
        $this->assertNoPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames());

        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames());
    }
}
