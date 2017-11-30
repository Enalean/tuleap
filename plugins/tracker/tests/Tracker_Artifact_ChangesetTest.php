<?php
/**
 * Copyright (c) Enalean, 2012-2017. All Rights Reserved.
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
        'sendNotification',
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

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        $GLOBALS['sys_default_domain'] = 'localhost';
        ForgeConfig::set('sys_https_host', '');
        $this->recipient_factory  = mock('Tracker_Artifact_MailGateway_RecipientFactory');
        $this->recipients_manager = mock('Tuleap\Tracker\Artifact\Changeset\Notification\RecipientsManager');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        unset($GLOBALS['sys_default_domain']);
        parent::tearDown();
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

        $result = $current_changeset->diffToprevious();

        $this->assertPattern('/field1/', $result);
        $this->assertNoPattern('/field2/', $result);
    }

    function testNotify() {
        $fact     = new MockTracker_FormElementFactory();
        $dao      = new MockTracker_Artifact_Changeset_ValueDao();
        $artifact = new MockTracker_Artifact();
        $tracker  = new MockTracker();
        $um       = new MockUserManager();
        $comment  = new MockTracker_Artifact_Changeset_Comment();

        $artifact->setReturnReference('getTracker', $tracker);
        $artifact->setReturnValue('getId', 666);

        $tracker->setReturnValue('getItemName', 'story');
        $tracker->setReturnValue('isNotificationStopped', false);

        stub($this->recipients_manager)->getRecipients()->returns(
            array(
                'a_user' => true,
                'multiple_users' => true,
                'email@example.com' => true,
                'dont_check_perms' => true,
                'global3' => true,
                'comment1' => true,
                'comment2' => true,
            )
        );
        $language = mock('BaseLanguage');
        stub($this->recipients_manager)->getUserFromRecipientName('a_user')->returns(aUser()->withEmail('a_user')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('multiple_users')->returns(aUser()->withEmail('multiple_users')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('email@example.com')->returns(aUser()->withEmail('email@example.com')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('dont_check_perms')->returns(aUser()->withEmail('dont_check_perms')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('global3')->returns(aUser()->withEmail('global3')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('comment1')->returns(aUser()->withEmail('comment1')->withLanguage($language)->build());
        stub($this->recipients_manager)->getUserFromRecipientName('comment2')->returns(aUser()->withEmail('comment2')->withLanguage($language)->build());


        $config = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig');

        $mail_sender = mock('Tuleap\Tracker\Artifact\Changeset\Notification\MailSender');

        $current_changeset = partial_mock(
            'Tracker_Artifact_Changeset',
            array(
                'getId',
                'getValueDao',
                'getFormElementFactory',
                'getArtifact',
                'getUserManager',
                'getTracker',
                'getComment',
                'getMailGatewayConfig',
                'isNotificationAssignedToEnabled',
                'getLogger',
                'getMailSender',
                'getRecipientsManager',
                'getUserHelper'
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
        $current_changeset->setReturnReference('getRecipientsManager', $this->recipients_manager);
        $current_changeset->setReturnReference('getMailSender', $mail_sender);
        $current_changeset->setReturnReference('getUserHelper', mock('UserHelper'));

        expect($mail_sender)->send(
            '*',
            array(
                'a_user',
                'multiple_users',
                'email@example.com',
                'dont_check_perms',
                'global3',
                'comment1',
                'comment2',
            ),
            array(), // headers
            '*', //from
            '[story #666] ', //subject
            '*',
            '*',
            '*'
        )->once();

        $current_changeset->notify();
    }

    function testNotifyStopped() {
        $changeset = new Tracker_Artifact_ChangesetTestVersion();
        $tracker  = new MockTracker();
        $tracker->setReturnValue('isNotificationStopped', true);
        $changeset->setReturnReference('getTracker', $tracker);
        $changeset->expectNever('getFormElementFactory');
        $changeset->expectNever('getMailSender');
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

        stub($this->recipients_manager)->getUserFromRecipientName('user01')->returns($user);

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
        stub($this->recipients_manager)->getUserFromRecipientName('user01')->returns($user1);
        stub($this->recipients_manager)->getUserFromRecipientName('user02')->returns($user2);
        stub($this->recipients_manager)->getUserFromRecipientName('user03')->returns($user3);

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
                'isNotificationAssignedToEnabled',
                'getRecipientsManager',
            )
        );
        $changeset->setReturnValue('getUserHelper', $uh);
        $changeset->setReturnValue('getUserManager', $um);
        $changeset->setReturnValue('getArtifact', $a);
        $changeset->setReturnValue('getLanguageFactory', $languageFactory);
        $changeset->setReturnValue('getRecipientFactory', $this->recipient_factory);
        $changeset->setReturnValue('isNotificationAssignedToEnabled', false);
        $changeset->setReturnValue('getRecipientsManager', $this->recipients_manager);

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

    public function itContainsChanges() {
        $pattern = '/'. preg_quote('tracker_artifact_followup-with_changes') .'/';
        $this->assertPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertNoPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames(false));
    }

    public function itContainsComment() {
        $pattern = '/'. preg_quote('tracker_artifact_followup-with_comment') .'/';
        $this->assertPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames(false));
        $this->assertPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
        $this->assertPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertNoPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames('The changes'));
    }

    public function itContainsSystemUser() {
        $pattern = '/'. preg_quote('tracker_artifact_followup-by_system_user') .'/';
        $this->assertNoPattern($pattern, $this->changeset_with_comment->getFollowUpClassnames(false));
        $this->assertNoPattern($pattern, $this->changeset_with_both_changes_and_comment->getFollowUpClassnames('The changes'));
        $this->assertNoPattern($pattern, $this->changeset_with_changes->getFollowUpClassnames('The changes'));
        $this->assertNoPattern($pattern, $this->changeset_by_anonymous->getFollowUpClassnames('The changes'));

        $this->assertPattern($pattern, $this->changeset_by_workflowadmin->getFollowUpClassnames('The changes'));
    }
}
