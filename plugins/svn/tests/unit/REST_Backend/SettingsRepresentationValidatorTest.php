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

use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVNCore\Repository;

final class SettingsRepresentationValidatorTest extends \Tuleap\Test\PHPUnit\TestCase
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
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new /** @psalm-immutable */ class ($notification_representation_01, $notification_representation_02) extends SettingsPOSTRepresentation {
            public function __construct(NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenPathAreUnique(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/trunks'
        );

        $settings = new /** @psalm-immutable */ class ($notification_representation_01, $notification_representation_02) extends SettingsPOSTRepresentation {
            public function __construct(NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowAnExceptionAccessFileIsSentEmpty(): void
    {
        $settings = new /** @psalm-immutable */ class ('') extends SettingsPUTRepresentation {
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
        $settings = new /** @psalm-immutable */ class extends SettingsPUTRepresentation {
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

    public function testItThrowsAnExceptionWhenUsersAndEmailAndUgroupsAreAllEmpty(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  [], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new /** @psalm-immutable */ class ($notification_representation_01) extends SettingsPOSTRepresentation {
            public function __construct(NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPOST(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com', 'test1@example.com', 'test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $settings = new /** @psalm-immutable */ class ($notification_representation_01) extends SettingsPOSTRepresentation {
            public function __construct(NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPOST(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/trunks'
        );

        $settings = new /** @psalm-immutable */ class ($notification_representation_01, $notification_representation_02) extends SettingsPOSTRepresentation {
            public function __construct(NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPOSTRepresentation($settings);
    }

    public function testItThrowsAnExceptionWhenSameMailIsAddedTwiceOnTheSamePathOnPUT(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com', 'test1@example.com', 'test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $repository = $this->createMock(Repository::class);

        $settings = new /** @psalm-immutable */ class ($repository, $notification_representation_01) extends SettingsPUTRepresentation {
            public function __construct(Repository $repository, NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
                $this->access_file         = '';
                $this->immutable_tags      = ImmutableTagRepresentation::build(ImmutableTag::buildEmptyImmutableTag($repository));
                $this->commit_rules        = CommitRulesRepresentation::build(new HookConfig($repository, []));
            }
        };

        $this->expectException(SettingsInvalidException::class);
        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenSameMailIsUsedForTwoDifferentPathOnPUT(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/tags'
        );

        $notification_representation_02 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  ['test@example.com'], 'users' => [], 'ugroups' => []],
            '/trunks'
        );

        $repository = $this->createMock(Repository::class);

        $settings = new /** @psalm-immutable */ class ($repository, $notification_representation_01, $notification_representation_02) extends SettingsPUTRepresentation {
            public function __construct(Repository $repository, NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
                $this->access_file         = '';
                $this->immutable_tags      = ImmutableTagRepresentation::build(ImmutableTag::buildEmptyImmutableTag($repository));
                $this->commit_rules        = CommitRulesRepresentation::build(new HookConfig($repository, []));
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPUTRepresentation($settings);
    }

    public function testItDontThrowExceptionWhenOnlyUgroupsIsProvidedOnPUT(): void
    {
        $notification_representation_01 = new NotificationPOSTPUTRepresentation(
            ['emails' =>  [], 'users' => [], 'ugroups' => ['101_4']],
            '/tags'
        );

        $repository = $this->createMock(Repository::class);

        $settings = new /** @psalm-immutable */ class ($repository, $notification_representation_01) extends SettingsPUTRepresentation {
            public function __construct(Repository $repository, NotificationPOSTPUTRepresentation ...$emails_notifications)
            {
                $this->email_notifications = $emails_notifications;
                $this->access_file         = '';
                $this->immutable_tags      = ImmutableTagRepresentation::build(ImmutableTag::buildEmptyImmutableTag($repository));
                $this->commit_rules        = CommitRulesRepresentation::build(new HookConfig($repository, []));
            }
        };

        $this->expectNotToPerformAssertions();

        $this->validator->validateForPUTRepresentation($settings);
    }
}
