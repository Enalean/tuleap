<?php
/**
 * Copyright (c) STMicroelectronics, 2004-2009. All rights reserved
 * Copyright (c) Enalean, 2017. All rights reserved
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

require_once 'bootstrap.php';

Mock::generatePartial('Docman_NotificationsManager',
                      'Docman_NotificationsManager_TestVersion',
                      array('_getMailBuilder',
                            '_getItemFactory',
                            '_groupGetObject',
                            '_getDao',
                            'getListeningUsers'));

Mock::generate('Docman_ItemFactory');
Mock::generate('Docman_Item');
Mock::generate('Docman_Path');

Mock::generate('PFUser');

Mock::generate('Mail');

Mock::generate('Project');

Mock::generate('Feedback');

Mock::generate('BaseLanguage');

class Docman_NotificationsManagerTest extends TuleapTestCase
{
    /**
     * @var Tuleap\Mail\MailFilter
     */
    private $mail_filter;

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['sys_noreply'] = 'norelpy@codendi.org';
        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', '/tuleap');

        $this->mail_filter = mock('Tuleap\Mail\MailFilter');
    }

    public function tearDown() {
        unset($GLOBALS['sys_noreply']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testSendNotificationsSuccess()
    {
        $mail              = new MockMail($this);
        $mail->setReturnValue('send', true);
        $feedback          = new MockFeedback($this);
        $project           = new MockProject($this);
        $project->setReturnValue('getPublicName', 'Guinea Pig');
        $itemFty           = new MockDocman_ItemFactory($this);
        $notifications_dao = mock('Tuleap\Docman\Notifications\Dao');
        $project           = aMockProject()->withId(101)->build();
        $mail_builder      = new MailBuilder(TemplateRendererFactory::build(), $this->mail_filter);
        $users_retriever   = mock('Tuleap\Docman\Notifications\UsersRetriever');

        $nm = new Docman_NotificationsManager_TestVersion($this);
        $nm->setReturnValue('_getItemFactory', $itemFty);
        $nm->setReturnValue('_groupGetObject', $project);
        $nm->__construct(
            $project,
            '/toto',
            $feedback,
            $mail_builder,
            $notifications_dao,
            $users_retriever
        );

        $user = mock('PFUser');
        $user->setReturnValue('getEmail', 'foo@codendi.org');
        $nm->_messages = array(array('title' => 'Move', 'content' => 'Changed', 'to' => array($user)));

        $nm->sendNotifications('', '');
    }

    public function testGetMessageForUserSameListenedItem()
    {
        $GLOBALS['Language']->setReturnValue('getText', 'notif_modified_by', array('plugin_docman', 'notif_modified_by'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_wiki_new_version', array('plugin_docman', 'notif_wiki_new_version', 'wiki'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_something_happen', array('plugin_docman', 'notif_something_happen'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_footer_message', array('plugin_docman', 'notif_footer_message'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_footer_message_link', array('plugin_docman', 'notif_footer_message_link'));

        $project           = aMockProject()->withId(101)->build();
        $feedback          = new MockFeedback($this);
        $mail_builder      = new MailBuilder(TemplateRendererFactory::build(), $this->mail_filter);
        $notifications_dao = mock('Tuleap\Docman\Notifications\Dao');
        $users_retriever   = mock('Tuleap\Docman\Notifications\UsersRetriever');

        $notificationsManager = new Docman_NotificationsManager_TestVersion();
        $notificationsManager->__construct(
            $project,
            '/toto',
            $feedback,
            $mail_builder,
            $notifications_dao,
            $users_retriever
        );
        $notificationsManager->_url = 'http://www.example.com/plugins/docman/';
        $user = mock('PFUser');
        $user->setReturnValue('getRealName', 'John Doe');
        $user->setReturnValue('getId', 2);
        $params['path']      = new MockDocman_Path();
        $params['path']->setReturnValue('get', 'Folder1/Folder2/File');
        $params['item']      = new MockDocman_Item();
        $params['item']->setReturnValue('getId', 1);
        $notificationsManager->setReturnValue('getListeningUsers', array($user->getId() => $params['item']));
        $params['wiki_page'] = 'wiki';
        $params['url']       = 'http://www.example.com/plugins/docman/';

        $message1 = "Folder1/Folder2/File notif_modified_by John Doe.\nhttp://www.example.com/plugins/docman/&action=details&id=1\n\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $message2 = "Folder1/Folder2/File notif_modified_by John Doe.\nhttp://www.example.com/plugins/docman/&action=details&id=1\n\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $message3 = "notif_wiki_new_version John Doe.\nhttp://www.example.com/plugins/docman/\n\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $message4 = "notif_something_happen\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";

        $this->assertEqual($message1, $notificationsManager->_getMessageForUser($user, 'modified', $params));
        $this->assertEqual($message2, $notificationsManager->_getMessageForUser($user, 'new_version', $params));
        $this->assertEqual($message3, $notificationsManager->_getMessageForUser($user, 'new_wiki_version', $params));
        $this->assertEqual($message4, $notificationsManager->_getMessageForUser($user, 'something happen', $params));
    }

    public function testGetMessageForUserParentListened()
    {
        $GLOBALS['Language']->setReturnValue('getText', 'notif_modified_by', array('plugin_docman', 'notif_modified_by'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_wiki_new_version', array('plugin_docman', 'notif_wiki_new_version', 'wiki'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_something_happen', array('plugin_docman', 'notif_something_happen'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_footer_message', array('plugin_docman', 'notif_footer_message'));
        $GLOBALS['Language']->setReturnValue('getText', 'notif_footer_message_link', array('plugin_docman', 'notif_footer_message_link'));

        $project           = aMockProject()->withId(101)->build();
        $feedback          = new MockFeedback($this);
        $mail_builder      = new MailBuilder(TemplateRendererFactory::build(), $this->mail_filter);
        $notifications_dao = mock('Tuleap\Docman\Notifications\Dao');
        $users_retriever   = mock('Tuleap\Docman\Notifications\UsersRetriever');

        $notificationsManager = new Docman_NotificationsManager_TestVersion();
        $notificationsManager->__construct(
            $project,
            '/toto',
            $feedback,
            $mail_builder,
            $notifications_dao,
            $users_retriever
        );
        $notificationsManager->_url = 'http://www.example.com/plugins/docman/';
        $user = mock('PFUser');
        $user->setReturnValue('getRealName', 'John Doe');
        $user->setReturnValue('getId', 2);
        $params['path']      = new MockDocman_Path();
        $params['path']->setReturnValue('get', 'Folder1/Folder2/File');
        $params['item']      = new MockDocman_Item();
        $params['item']->setReturnValue('getId', 10);
        $parentItem      = new MockDocman_Item();
        $parentItem->setReturnValue('getId', 1);
        $notificationsManager->setReturnValue('getListeningUsers', array($user->getId() => $parentItem));
        $params['wiki_page'] = 'wiki';
        $params['url']       = 'http://www.example.com/plugins/docman/';

        $message1 = "Folder1/Folder2/File notif_modified_by John Doe.\nhttp://www.example.com/plugins/docman/&action=details&id=10\n\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $message2 = "Folder1/Folder2/File notif_modified_by John Doe.\nhttp://www.example.com/plugins/docman/&action=details&id=10\n\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $message3 = "notif_wiki_new_version John Doe.\nhttp://www.example.com/plugins/docman/\n\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";
        $message4 = "notif_something_happen\n\n--------------------------------------------------------------------\nnotif_footer_message\nnotif_footer_message_link\nhttp://www.example.com/plugins/docman/&action=details&section=notifications&id=1";

        $this->assertEqual($message1, $notificationsManager->_getMessageForUser($user, 'modified', $params));
        $this->assertEqual($message2, $notificationsManager->_getMessageForUser($user, 'new_version', $params));
        $this->assertEqual($message3, $notificationsManager->_getMessageForUser($user, 'new_wiki_version', $params));
        $this->assertEqual($message4, $notificationsManager->_getMessageForUser($user, 'something happen', $params));
    }
}
