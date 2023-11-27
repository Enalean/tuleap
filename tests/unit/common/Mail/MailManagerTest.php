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

namespace Tuleap\Mail;

use Codendi_Mail_Interface;
use MailManager;
use Tuleap\Test\Builders\UserTestBuilder;

final class MailManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testGetMailPrefsShouldReturnUsersAccordingToPreferences(): void
    {
        $mm = $this->createPartialMock(\MailManager::class, [
            'getUserManager',
        ]);

        $manuel = UserTestBuilder::anActiveUser()->build();
        $manuel->setPreference('user_tracker_mailformat', 'html');

        $nicolas = UserTestBuilder::anActiveUser()->build();
        $nicolas->setPreference('user_tracker_mailformat', 'text');

        $um = $this->createMock(\UserManager::class);
        $um->method('getAllUsersByEmail')->withConsecutive(
            ['manuel@enalean.com'],
            ['nicolas@enalean.com']
        )->willReturnOnConsecutiveCalls([$manuel], [$nicolas]);
        $mm->method('getUserManager')->willReturn($um);

        $addresses = ['manuel@enalean.com', 'nicolas@enalean.com'];

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        self::assertEquals([$manuel], $prefs['html']);
        self::assertEquals([$nicolas], $prefs['text']);
    }

    public function testGetMailPrefsShouldReturnUserWithTextPref(): void
    {
        $mm = $this->createPartialMock(\MailManager::class, [
            'getUserManager',
        ]);

        $manuel = UserTestBuilder::anActiveUser()->build();
        $manuel->setPreference('user_tracker_mailformat', 'text');

        $manuel2 = UserTestBuilder::anActiveUser()->build();
        $manuel2->setPreference('user_tracker_mailformat', 'html');

        $um = $this->createMock(\UserManager::class);
        $um->method('getAllUsersByEmail')->with('manuel@enalean.com')->willReturn([$manuel, $manuel2]);

        $mm->method('getUserManager')->willReturn($um);

        $addresses = ['manuel@enalean.com'];

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        self::assertEquals([$manuel], $prefs['text']);
        self::assertEquals([], $prefs['html']);
    }

    public function testGetMailPrefsShouldReturnUserWithHtmlPref(): void
    {
        $mm = $this->createPartialMock(\MailManager::class, [
            'getUserManager',
        ]);

        $manuel = UserTestBuilder::anActiveUser()->build();
        $manuel->setPreference('user_tracker_mailformat', '');

        $manuel2 = UserTestBuilder::anActiveUser()->build();
        $manuel2->setPreference('user_tracker_mailformat', 'html');

        $um = $this->createMock(\UserManager::class);
        $um->method('getAllUsersByEmail')->with('manuel@enalean.com')->willReturn([$manuel, $manuel2]);

        $mm->method('getUserManager')->willReturn($um);

        $addresses = ['manuel@enalean.com'];

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        self::assertEquals([], $prefs['text']);
        self::assertEquals([$manuel2], $prefs['html']);
    }

    public function testGetMailPrefsShouldReturnLastUser(): void
    {
        $mm = $this->createPartialMock(\MailManager::class, [
            'getUserManager',
        ]);

        $manuel = UserTestBuilder::anActiveUser()->build();
        $manuel->setPreference('user_tracker_mailformat', '');

        $manuel2 = UserTestBuilder::anActiveUser()->build();
        $manuel2->setPreference('user_tracker_mailformat', '');

        $um = $this->createMock(\UserManager::class);
        $um->method('getAllUsersByEmail')->with('manuel@enalean.com')->willReturn([$manuel, $manuel2]);

        $mm->method('getUserManager')->willReturn($um);

        $addresses = ['manuel@enalean.com'];

        $prefs = $mm->getMailPreferencesByEmail($addresses);
        self::assertEquals([], $prefs['text']);
        self::assertEquals([$manuel2], $prefs['html']);
    }

    public function testGetMailPrefsShouldReturnHTMLUsersWithAnonymous(): void
    {
        $mm = $this->createPartialMock(\MailManager::class, [
            'getUserManager',
            'getConfig',
        ]);

        $um = $this->createMock(\UserManager::class);
        $um->method('getAllUsersByEmail')->willReturn([]);
        $mm->method('getUserManager')->willReturn($um);

        $mm->method('getConfig')->willReturn('fr_BE');

        $prefs = $mm->getMailPreferencesByEmail(['manuel@enalean.com']);
        self::assertEquals([], $prefs['text']);
        self::assertCount(1, $prefs['html']);
        self::assertEquals('manuel@enalean.com', $prefs['html'][0]->getEmail());
        self::assertTrue($prefs['html'][0]->isAnonymous());
        self::assertEquals('fr_BE', $prefs['html'][0]->getLanguageID());
    }

    public function testGetMailPrefsByUsersShouldReturnHTMLByDefault(): void
    {
        $mm   = new MailManager();
        $user = UserTestBuilder::anActiveUser()->withId(123)->withLocale('en_US')->build();
        self::assertEquals(Codendi_Mail_Interface::FORMAT_HTML, $mm->getMailPreferencesByUser($user));
    }

    public function testGetMailPrefsByUsersShouldReturnTextWhenUserRequestIt(): void
    {
        $mm   = new MailManager();
        $user = UserTestBuilder::anActiveUser()->build();
        $user->setPreference('user_tracker_mailformat', 'text');
        self::assertEquals(Codendi_Mail_Interface::FORMAT_TEXT, $mm->getMailPreferencesByUser($user));
    }

    public function testGetMailPrefsByUsersShouldReturnHTMLWhenPreferenceReturnsFalse(): void
    {
        $mm   = new MailManager();
        $user = UserTestBuilder::anActiveUser()->build();
        $user->setPreference('user_tracker_mailformat', '');
        self::assertEquals(Codendi_Mail_Interface::FORMAT_HTML, $mm->getMailPreferencesByUser($user));
    }
}
