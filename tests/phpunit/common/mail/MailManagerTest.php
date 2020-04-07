<?php
/**
 * Copyright (c) Enalean, 2011-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class MailManagerTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        $user_manager = \Mockery::spy(\UserManager::class);
        UserManager::setInstance($user_manager);
    }

    protected function tearDown(): void
    {
        UserManager::clearInstance();
        parent::tearDown();
    }

    public function testGetMailPrefsShouldReturnUsersAccordingToPreferences(): void
    {
        $mm = \Mockery::mock(\MailManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $manuel = \Mockery::spy(\PFUser::class);
        $manuel->shouldReceive('getPreference')->with('user_tracker_mailformat')->andReturns('html');
        $manuel->shouldReceive('getStatus')->andReturns('A');

        $nicolas = \Mockery::spy(\PFUser::class);
        $nicolas->shouldReceive('getPreference')->with('user_tracker_mailformat')->andReturns('text');
        $nicolas->shouldReceive('getStatus')->andReturns('A');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getAllUsersByEmail')->with('manuel@enalean.com')->andReturns(array($manuel));
        $um->shouldReceive('getAllUsersByEmail')->with('nicolas@enalean.com')->andReturns(array($nicolas));
        $mm->shouldReceive('getUserManager')->andReturns($um);

        $addresses = array('manuel@enalean.com', 'nicolas@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEquals(array($manuel), $prefs['html']);
        $this->assertEquals(array($nicolas), $prefs['text']);
    }

    public function testGetMailPrefsShouldReturnUserWithTextPref(): void
    {
        $mm = \Mockery::mock(\MailManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $manuel = \Mockery::spy(\PFUser::class);
        $manuel->shouldReceive('getPreference')->with('user_tracker_mailformat')->andReturns('text');
        $manuel->shouldReceive('getStatus')->andReturns('A');

        $manuel2 = \Mockery::spy(\PFUser::class);
        $manuel2->shouldReceive('getPreference')->with('user_tracker_mailformat')->andReturns('html');
        $manuel2->shouldReceive('getStatus')->andReturns('A');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getAllUsersByEmail')->with('manuel@enalean.com')->andReturns(array($manuel, $manuel2));

        $mm->shouldReceive('getUserManager')->andReturns($um);

        $addresses = array('manuel@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEquals(array($manuel), $prefs['text']);
        $this->assertEquals([], $prefs['html']);
    }

    public function testGetMailPrefsShouldReturnUserWithHtmlPref(): void
    {
        $mm = \Mockery::mock(\MailManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $manuel = \Mockery::spy(\PFUser::class);
        $manuel->shouldReceive('getPreference')->andReturns(false);
        $manuel->shouldReceive('getStatus')->andReturns('A');

        $manuel2 = \Mockery::spy(\PFUser::class);
        $manuel2->shouldReceive('getPreference')->with('user_tracker_mailformat')->andReturns('html');
        $manuel2->shouldReceive('getStatus')->andReturns('A');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getAllUsersByEmail')->with('manuel@enalean.com')->andReturns(array($manuel, $manuel2));

        $mm->shouldReceive('getUserManager')->andReturns($um);

        $addresses = array('manuel@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEquals([], $prefs['text']);
        $this->assertEquals(array($manuel2), $prefs['html']);
    }

    public function testGetMailPrefsShouldReturnLastUser(): void
    {
        $mm = \Mockery::mock(\MailManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $manuel = \Mockery::spy(\PFUser::class);
        $manuel->shouldReceive('getPreference')->andReturns(false);
        $manuel->shouldReceive('getStatus')->andReturns('A');

        $manuel2 = \Mockery::spy(\PFUser::class);
        $manuel2->shouldReceive('getPreference')->andReturns(false);
        $manuel2->shouldReceive('getStatus')->andReturns('A');

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getAllUsersByEmail')->with('manuel@enalean.com')->andReturns(array($manuel, $manuel2));

        $mm->shouldReceive('getUserManager')->andReturns($um);

        $addresses = array('manuel@enalean.com');

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        $this->assertEquals([], $prefs['text']);
        $this->assertEquals(array($manuel2), $prefs['html']);
    }

    public function testGetMailPrefsShouldReturnHTMLUsersWithAnonymous(): void
    {
        $mm = \Mockery::mock(\MailManager::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $um = \Mockery::spy(\UserManager::class);
        $um->shouldReceive('getAllUsersByEmail')->andReturns(array());
        $mm->shouldReceive('getUserManager')->andReturns($um);

        $mm->shouldReceive('getConfig')->andReturns('fr_BE');

        $prefs = $mm->getMailPreferencesByEmail(array('manuel@enalean.com'));
        $this->assertEquals([], $prefs['text']);
        $this->assertCount(1, $prefs['html']);
        $this->assertEquals('manuel@enalean.com', $prefs['html'][0]->getEmail());
        $this->assertTrue($prefs['html'][0]->isAnonymous());
        $this->assertEquals('fr_BE', $prefs['html'][0]->getLanguageID());
    }

    public function testGetMailPrefsByUsersShouldReturnHTMLByDefault(): void
    {
        $mm   = new MailManager();
        $user = new PFUser(array('id' => 123, 'language_id' => 'en_US'));
        $this->assertEquals(Codendi_Mail_Interface::FORMAT_HTML, $mm->getMailPreferencesByUser($user));
    }

    public function testGetMailPrefsByUsersShouldReturnTextWhenUserRequestIt(): void
    {
        $mm   = new MailManager();
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getPreference')->with('user_tracker_mailformat')->once()->andReturns('text');
        $this->assertEquals(Codendi_Mail_Interface::FORMAT_TEXT, $mm->getMailPreferencesByUser($user));
    }

    public function testGetMailPrefsByUsersShouldReturnHTMLWhenPreferenceReturnsFalse(): void
    {
        $mm   = new MailManager();
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getPreference')->with('user_tracker_mailformat')->once()->andReturns(false);
        $this->assertEquals(Codendi_Mail_Interface::FORMAT_HTML, $mm->getMailPreferencesByUser($user));
    }
}
