<?php
/**
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository\Settings;

use Tuleap\NeverThrow\Result;
use Tuleap\Project\REST\UserGroupRetriever;
use Tuleap\SVN\Admin\ImmutableTag;
use Tuleap\SVN\Admin\ImmutableTagDao;
use Tuleap\SVN\Admin\ImmutableTagFactory;
use Tuleap\SVN\Repository\HookConfig;
use Tuleap\SVN\Repository\SvnRepository;
use Tuleap\SVN\REST\v1\CommitRulesRepresentation;
use Tuleap\SVN\REST\v1\ImmutableTagRepresentation;
use Tuleap\SVN\REST\v1\NotificationPOSTPUTRepresentation;
use Tuleap\SVN\REST\v1\SettingsPOSTRepresentation;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;

final class SettingsBuilderTest extends TestCase
{
    public function testItReturnsAnEmptyDefaultSettingsWhenRepresentationIsNULL(): void
    {
        $builder = new SettingsBuilder(
            new ImmutableTagFactory($this->createMock(ImmutableTagDao::class)),
            RetrieveUserByIdStub::withNoUser(),
            $this->createMock(UserGroupRetriever::class),
        );

        $repository = SvnRepository::buildActiveRepository(1, 'repo01', ProjectTestBuilder::aProject()->build());

        $result = $builder->buildFromPOSTPUTRESTRepresentation($repository, null);

        self::assertTrue(Result::isOk($result));

        $settings = $result->value;
        self::assertInstanceOf(Settings::class, $settings);

        self::assertSame([], $settings->getCommitRules());
        self::assertSame("", $settings->getAccessFileContent());
        self::assertSame([], $settings->getMailNotification());

        $settings_immutable_tags = $settings->getImmutableTag();
        self::assertNotNull($settings_immutable_tags);
        self::assertSame([], $settings_immutable_tags->getPaths());
        self::assertSame([], $settings_immutable_tags->getWhitelist());
    }

    public function testItReturnsSettingsWithRepresentationDataWithUserNotifications(): void
    {
        $builder = new SettingsBuilder(
            new ImmutableTagFactory($this->createMock(ImmutableTagDao::class)),
            RetrieveUserByIdStub::withUsers(
                UserTestBuilder::anActiveUser()->withId(101)->withUserName('user01')->build(),
                UserTestBuilder::anActiveUser()->withId(102)->withUserName('user02')->build(),
            ),
            $this->createMock(UserGroupRetriever::class),
        );

        $repository = SvnRepository::buildActiveRepository(1, 'repo01', ProjectTestBuilder::aProject()->build());

        $settings_post_representation = $this->buildSettingsPOSTRepresentationWithUsersNotifications($repository);

        $result = $builder->buildFromPOSTPUTRESTRepresentation($repository, $settings_post_representation);

        self::assertTrue(Result::isOk($result));

        $settings = $result->value;
        self::assertInstanceOf(Settings::class, $settings);

        self::assertEqualsCanonicalizing(
            [
                'mandatory_reference' => false,
                'commit_message_can_change' => true,
            ],
            $settings->getCommitRules()
        );
        self::assertSame("Access file content", $settings->getAccessFileContent());

        $settings_notifications = $settings->getMailNotification();
        self::assertNotNull($settings_notifications);
        self::assertCount(1, $settings_notifications);

        $settings_notification = $settings_notifications[0];
        self::assertNotNull($settings_notification);
        self::assertSame('/path01', $settings_notification->getPath());
        self::assertSame('user01, user02', $settings_notification->getNotifiedUsersAsString());

        $settings_immutable_tags = $settings->getImmutableTag();
        self::assertNotNull($settings_immutable_tags);
        self::assertSame("/path01", $settings_immutable_tags->getPathsAsString());
        self::assertSame("", $settings_immutable_tags->getWhitelistAsString());
    }

    public function testItReturnsSettingsWithRepresentationDataWithUserGroupsNotifications(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $project_member_ugroup = ProjectUGroupTestBuilder::buildProjectMembers();
        $project_member_ugroup->setProject($project);

        $custom_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(201)->withName('ugroup01')->withProject($project)->build();

        $user_group_retriever = $this->createMock(UserGroupRetriever::class);
        $user_group_retriever->method('getExistingUserGroup')->willReturnMap([
            ['101_3', $project_member_ugroup],
            ['201', $custom_ugroup],
        ]);

        $builder = new SettingsBuilder(
            new ImmutableTagFactory($this->createMock(ImmutableTagDao::class)),
            RetrieveUserByIdStub::withNoUser(),
            $user_group_retriever,
        );

        $repository = SvnRepository::buildActiveRepository(1, 'repo01', $project);

        $settings_post_representation = $this->buildSettingsPOSTRepresentationWithUserGroupsNotifications($repository);

        $result = $builder->buildFromPOSTPUTRESTRepresentation($repository, $settings_post_representation);

        self::assertTrue(Result::isOk($result));

        $settings = $result->value;
        self::assertInstanceOf(Settings::class, $settings);

        self::assertEqualsCanonicalizing(
            [
                'mandatory_reference' => false,
                'commit_message_can_change' => true,
            ],
            $settings->getCommitRules()
        );
        self::assertSame("Access file content", $settings->getAccessFileContent());

        $settings_notifications = $settings->getMailNotification();
        self::assertNotNull($settings_notifications);
        self::assertCount(1, $settings_notifications);

        $settings_notification = $settings_notifications[0];
        self::assertNotNull($settings_notification);
        self::assertSame('/path01', $settings_notification->getPath());
        self::assertSame('project_members, ugroup01', $settings_notification->getNotifiedUserGroupsAsString());

        $settings_immutable_tags = $settings->getImmutableTag();
        self::assertNotNull($settings_immutable_tags);
        self::assertSame("/path01", $settings_immutable_tags->getPathsAsString());
        self::assertSame("", $settings_immutable_tags->getWhitelistAsString());
    }

    public function testItReturnsAnErrorIfAProvidedUserDoesNotExist(): void
    {
        $builder = new SettingsBuilder(
            new ImmutableTagFactory($this->createMock(ImmutableTagDao::class)),
            RetrieveUserByIdStub::withUsers(
                UserTestBuilder::anActiveUser()->withId(101)->withUserName('user01')->build(),
            ),
            $this->createMock(UserGroupRetriever::class),
        );

        $repository = SvnRepository::buildActiveRepository(1, 'repo01', ProjectTestBuilder::aProject()->build());

        $settings_post_representation = $this->buildSettingsPOSTRepresentationWithUsersNotifications($repository);

        $result = $builder->buildFromPOSTPUTRESTRepresentation($repository, $settings_post_representation);

        self::assertTrue(Result::isErr($result));
        self::assertSame("User 102 not found", (string) $result->error);
    }

    public function testItReturnsAnErrorIfAProvidedUserGroupIsForbidden(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $project_svn_admins_ugroup = new \ProjectUGroup([
            'ugroup_id' => \ProjectUGroup::SVN_ADMIN,
            'name'      => 'SVN Admins',
        ]);
        $project_svn_admins_ugroup->setProject($project);

        $user_group_retriever = $this->createMock(UserGroupRetriever::class);
        $user_group_retriever->method('getExistingUserGroup')->with('101_19')->willReturn($project_svn_admins_ugroup);

        $builder = new SettingsBuilder(
            new ImmutableTagFactory($this->createMock(ImmutableTagDao::class)),
            RetrieveUserByIdStub::withNoUser(),
            $user_group_retriever,
        );

        $repository = SvnRepository::buildActiveRepository(1, 'repo01', ProjectTestBuilder::aProject()->build());

        $settings_post_representation = $this->buildSettingsPOSTRepresentationWithForbiddenUserGroupsNotifications($repository);

        $result = $builder->buildFromPOSTPUTRESTRepresentation($repository, $settings_post_representation);

        self::assertTrue(Result::isErr($result));
        self::assertSame("Notifications can not be sent to ugroups Anonymous Authenticated and Registered", (string) $result->error);
    }

    public function testItReturnsAnErrorIfAProvidedUserGroupComesFromAnotherProject(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $project_member_ugroup = ProjectUGroupTestBuilder::buildProjectMembers();
        $project_member_ugroup->setProject($project);

        $custom_ugroup = ProjectUGroupTestBuilder::aCustomUserGroup(201)
            ->withName('ugroup01')
            ->withProject(ProjectTestBuilder::aProject()->withId(999)->build())
            ->build();

        $user_group_retriever = $this->createMock(UserGroupRetriever::class);
        $user_group_retriever->method('getExistingUserGroup')->willReturnMap([
            ['101_3', $project_member_ugroup],
            ['201', $custom_ugroup],
        ]);

        $builder = new SettingsBuilder(
            new ImmutableTagFactory($this->createMock(ImmutableTagDao::class)),
            RetrieveUserByIdStub::withNoUser(),
            $user_group_retriever,
        );

        $repository = SvnRepository::buildActiveRepository(1, 'repo01', $project);

        $settings_post_representation = $this->buildSettingsPOSTRepresentationWithUserGroupsNotifications($repository);

        $result = $builder->buildFromPOSTPUTRESTRepresentation($repository, $settings_post_representation);

        self::assertTrue(Result::isErr($result));
        self::assertSame("You can't add a user group from a different project", (string) $result->error);
    }

    private function buildSettingsPOSTRepresentationWithUsersNotifications(SvnRepository $repository): SettingsPOSTRepresentation
    {
        return new /** @psalm-immutable */ class ($repository) extends SettingsPOSTRepresentation {
            public function __construct(SvnRepository $repository)
            {
                $this->commit_rules = CommitRulesRepresentation::build(
                    new HookConfig(
                        $repository,
                        [
                            HookConfig::MANDATORY_REFERENCE => false,
                            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                        ]
                    )
                );

                $this->immutable_tags = ImmutableTagRepresentation::build(
                    new ImmutableTag($repository, '/path01', ''),
                );

                $this->access_file         = "Access file content";
                $this->email_notifications = [
                    new NotificationPOSTPUTRepresentation(
                        ['emails' => [], 'users' => [101, 102], 'ugroups' => []],
                        '/path01',
                    ),
                ];
            }
        };
    }

    private function buildSettingsPOSTRepresentationWithUserGroupsNotifications(SvnRepository $repository): SettingsPOSTRepresentation
    {
        return new /** @psalm-immutable */ class ($repository) extends SettingsPOSTRepresentation {
            public function __construct(SvnRepository $repository)
            {
                $this->commit_rules = CommitRulesRepresentation::build(
                    new HookConfig(
                        $repository,
                        [
                            HookConfig::MANDATORY_REFERENCE => false,
                            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                        ]
                    )
                );

                $this->immutable_tags = ImmutableTagRepresentation::build(
                    new ImmutableTag($repository, '/path01', ''),
                );

                $this->access_file         = "Access file content";
                $this->email_notifications = [
                    new NotificationPOSTPUTRepresentation(
                        ['emails' => [], 'users' => [], 'ugroups' => ['101_3', '201']],
                        '/path01',
                    ),
                ];
            }
        };
    }

    private function buildSettingsPOSTRepresentationWithForbiddenUserGroupsNotifications(SvnRepository $repository): SettingsPOSTRepresentation
    {
        return new /** @psalm-immutable */ class ($repository) extends SettingsPOSTRepresentation {
            public function __construct(SvnRepository $repository)
            {
                $this->commit_rules = CommitRulesRepresentation::build(
                    new HookConfig(
                        $repository,
                        [
                            HookConfig::MANDATORY_REFERENCE => false,
                            HookConfig::COMMIT_MESSAGE_CAN_CHANGE => true,
                        ]
                    )
                );

                $this->immutable_tags = ImmutableTagRepresentation::build(
                    new ImmutableTag($repository, '/path01', ''),
                );

                $this->access_file         = "Access file content";
                $this->email_notifications = [
                    new NotificationPOSTPUTRepresentation(
                        ['emails' => [], 'users' => [], 'ugroups' => ['101_19']],
                        '/path01',
                    ),
                ];
            }
        };
    }
}
