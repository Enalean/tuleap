<?php
/**
 *  Copyright (c) Enalean, 2017 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\SVN\REST\v1;

use PHPUnit\Framework\TestCase;

class SettingsRepresentationValidatorTest extends TestCase
{
    /**
     * @var SettingsRepresentationValidator
     */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new SettingsRepresentationValidator();
    }

    public function testItThrowAnExceptionWHenPathAreNotUnique(): void
    {
        $notification_representation_01         = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02         = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new class ($notification_representation_01, $notification_representation_02) extends SettingsPOSTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };
        $settings->email_notifications = [$notification_representation_01, $notification_representation_02];

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenPathAreUnique(): void
    {
        $notification_representation_01         = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02         = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/trunks'
        );

        $settings = new class ($notification_representation_01, $notification_representation_02) extends SettingsPOSTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowAnExceptionAccessFileIsSentEmpty(): void
    {
        $settings = new class ('') extends SettingsPUTRepresentation {
            public function __construct(string $access_file)
            {
                $this->access_file = $access_file;
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItThrowAnExceptionAccessFileKeyIsNotPresent(): void
    {
        $settings = new class extends SettingsPUTRepresentation {
            public function __construct()
            {
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItDontRaiseErrorWhenSettingsAreNotProvided(): void
    {
        $this->expectNotToPerformAssertions();

        $this->validator->validateForPUTRepresentation(null);
    }

    public function testItThrowsAnExceptionWhenUsersAndEmailAreBothEmpty(): void
    {
        $notification_representation_01         = new NotificationRepresentation(
            ['emails' =>  [], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new class ($notification_representation_01) extends SettingsPOSTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPOST(): void
    {
        $notification_representation_01 = new NotificationRepresentation(
            ['emails' =>  ['test@example.com', 'test1@example.com', 'test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new class ($notification_representation_01) extends SettingsPOSTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPOST(): void
    {
        $notification_representation_01 = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02 = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/trunks'
        );

        $settings = new class ($notification_representation_01, $notification_representation_02) extends SettingsPOSTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPUT(): void
    {
        $notification_representation_01 = new NotificationRepresentation(
            ['emails' =>  ['test@example.com', 'test1@example.com', 'test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new class ($notification_representation_01) extends SettingsPUTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
                $this->access_file         = '';
                $this->immutable_tags      = [];
                $this->commit_rules        = [];
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPUT(): void
    {
        $notification_representation_01         = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02         = new NotificationRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/trunks'
        );

        $settings = new class ($notification_representation_01, $notification_representation_02) extends SettingsPUTRepresentation {
            public function __construct(NotificationRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
                $this->access_file         = '';
                $this->immutable_tags      = [];
                $this->commit_rules        = [];
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPUTRepresentation($settings);
    }
}
