<?php
/**
 * Copyright (c) Enalean, 2011-2018. All Rights Reserved.
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

Mock::generate('PFUser');
Mock::generate('UserManager');

class MailManagerTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $user_manager = mock('UserManager');
        UserManager::setInstance($user_manager);
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    function testGetMailPrefsShouldReturnUsersAccordingToPreferences()
    {
        $mm = TestHelper::getPartialMock('MailManager', array('getUserManager'));

        $manuel = mock('PFUser');
        $manuel->setReturnValue('getPreference', 'html', array('user_tracker_mailformat'));
        $manuel->setReturnValue('getStatus', 'A');

        $nicolas = mock('PFUser');
        $nicolas->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $nicolas->setReturnValue('getStatus', 'A');

        $um = new MockUserManager();
        $um->setReturnValue('getAllUsersByEmail', array($manuel), array('manuel@enalean.com'));
        $um->setReturnValue('getAllUsersByEmail', array($nicolas), array('nicolas@enalean.com'));
        $mm->setReturnValue('getUserManager', $um);

        $addresses = array('manuel@enalean.com', 'nicolas@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEqual($prefs['html'], array($manuel));
        $this->assertEqual($prefs['text'], array($nicolas));
    }

    function testGetMailPrefsShouldReturnUserWithTextPref()
    {
        $mm = TestHelper::getPartialMock('MailManager', array('getUserManager'));

        $manuel = mock('PFUser');
        $manuel->setReturnValue('getPreference', 'text', array('user_tracker_mailformat'));
        $manuel->setReturnValue('getStatus', 'A');

        $manuel2 = mock('PFUser');
        $manuel2->setReturnValue('getPreference', 'html', array('user_tracker_mailformat'));
        $manuel2->setReturnValue('getStatus', 'A');

        $um = new MockUserManager();
        $um->setReturnValue('getAllUsersByEmail', array($manuel, $manuel2), array('manuel@enalean.com'));

        $mm->setReturnValue('getUserManager', $um);

        $addresses = array('manuel@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEqual($prefs['text'], array($manuel));
        $this->assertEqual($prefs['html'], array());
    }

    function testGetMailPrefsShouldReturnUserWithHtmlPref()
    {
        $mm = TestHelper::getPartialMock('MailManager', array('getUserManager'));

        $manuel = mock('PFUser');
        $manuel->setReturnValue('getPreference', false);
        $manuel->setReturnValue('getStatus', 'A');

        $manuel2 = mock('PFUser');
        $manuel2->setReturnValue('getPreference', 'html', array('user_tracker_mailformat'));
        $manuel2->setReturnValue('getStatus', 'A');

        $um = new MockUserManager();
        $um->setReturnValue('getAllUsersByEmail', array($manuel, $manuel2), array('manuel@enalean.com'));

        $mm->setReturnValue('getUserManager', $um);

        $addresses = array('manuel@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEqual($prefs['text'], array());
        $this->assertEqual($prefs['html'], array($manuel2));
    }

    function testGetMailPrefsShouldReturnLastUser()
    {
        $mm = TestHelper::getPartialMock('MailManager', array('getUserManager'));

        $manuel = mock('PFUser');
        $manuel->setReturnValue('getPreference', false);
        $manuel->setReturnValue('getStatus', 'A');

        $manuel2 = mock('PFUser');
        $manuel2->setReturnValue('getPreference', false);
        $manuel2->setReturnValue('getStatus', 'A');

        $um = new MockUserManager();
        $um->setReturnValue('getAllUsersByEmail', array($manuel, $manuel2), array('manuel@enalean.com'));

        $mm->setReturnValue('getUserManager', $um);

        $addresses = array('manuel@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEqual($prefs['text'], array());
        $this->assertEqual($prefs['html'], array($manuel2));
    }

    function testGetMailPrefsShouldReturnHTMLUsersWhithAnonymous()
    {
        $mm = TestHelper::getPartialMock('MailManager', array('getUserManager', 'getConfig'));

        $um = new MockUserManager();
        $um->setReturnValue('getAllUsersByEmail', array());
        $mm->setReturnValue('getUserManager', $um);

        $mm->setReturnValue('getConfig', 'fr_BE');

        $prefs = $mm->getMailPreferencesByEmail(array('manuel@enalean.com'));
        $this->assertEqual($prefs['text'], array());
        $this->assertEqual(count($prefs['html']), 1);
        $this->assertEqual($prefs['html'][0]->getEmail(), 'manuel@enalean.com');
        $this->assertEqual($prefs['html'][0]->isAnonymous(), true);
        $this->assertEqual($prefs['html'][0]->getLanguageID(), 'fr_BE');
    }

    function testGetMailPrefsByUsersShouldReturnHTMLByDefault()
    {
        $mm   = new MailManager();
        $user = new PFUser(array('id' => 123, 'language_id' => 'en_US'));
        $this->assertEqual($mm->getMailPreferencesByUser($user), Codendi_Mail_Interface::FORMAT_HTML);
    }

    function testGetMailPrefsByUsersShouldReturnTextWhenUserRequestIt()
    {
        $mm   = new MailManager();
        $user = mock('PFUser');
        $user->expectOnce('getPreference', array('user_tracker_mailformat'));
        $user->setReturnValue('getPreference', 'text');
        $this->assertEqual($mm->getMailPreferencesByUser($user), Codendi_Mail_Interface::FORMAT_TEXT);
    }

    function testGetMailPrefsByUsersShouldReturnHTMLWhenPreferenceReturnsFalse()
    {
        $mm   = new MailManager();
        $user = mock('PFUser');
        $user->expectOnce('getPreference', array('user_tracker_mailformat'));
        $user->setReturnValue('getPreference', false);
        $this->assertEqual($mm->getMailPreferencesByUser($user), Codendi_Mail_Interface::FORMAT_HTML);
    }
}
