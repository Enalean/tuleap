<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Mail\MailFactory;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class ProjectMemberAdderWithStatusCheckAndNotificationsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    private AddProjectMember&MockObject $add_project_member;
    private \BaseLanguage&MockObject $base_language;
    private MailFactory&MockObject $mail_factory;
    private ProjectMemberAdderWithStatusCheckAndNotifications $project_member_adder;
    private \PFUser $an_active_user;
    private \Project $an_active_project;
    private \PFUser $project_admin;

    protected function setUp(): void
    {
        $this->add_project_member = $this->createMock(AddProjectMember::class);
        $this->base_language      = $this->createMock(\BaseLanguage::class);
        $this->mail_factory       = $this->createMock(MailFactory::class);
        \ForgeConfig::set('sys_noreply', 'noreply@tuleap.example.com');
        \ForgeConfig::set(\Tuleap\Config\ConfigurationVariables::NAME, 'Tuleap');
        \ForgeConfig::set('sys_email_admin', 'admin@tuleap.example.com');

        $this->an_active_user    = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->an_active_project = ProjectTestBuilder::aProject()
            ->withId(202)
            ->withPublicName('A project name')
            ->withUnixName('a-project-name')
            ->build();

        $this->project_admin = UserTestBuilder::buildWithDefaults();

        $this->project_member_adder = new ProjectMemberAdderWithStatusCheckAndNotifications(
            $this->add_project_member,
            $this->base_language,
            $this->mail_factory
        );
    }

    public function testItAddsAndNotifyActiveUsers(): void
    {
        $this->base_language->expects(self::once())->method('getOverridableText')->willReturn('A Subject');
        $this->base_language->expects(self::once())->method('getContent')->willReturn(__DIR__ . '/_fixtures/empty.php');

        $mail = $this->createMock(\Codendi_Mail::class);
        $mail->expects(self::once())->method('setTo')->with('foo@example.com');
        $mail->expects(self::once())->method('setFrom');
        $mail->expects(self::once())->method('setSubject')->with('A Subject');
        $mail->expects(self::once())->method('setBodyText')->with('A body');
        $mail->expects(self::once())->method('send')->willReturn(true);

        $this->mail_factory->expects(self::once())->method('getMail')->willReturn($mail);

        $this->add_project_member->expects(self::once())->method('addProjectMember')->with($this->an_active_user, $this->an_active_project, $this->project_admin);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::INFO);

        $this->project_member_adder->addProjectMemberWithFeedback($this->an_active_user, $this->an_active_project, $this->project_admin);
    }

    public function testItAddsAndNotifyRestrictedUsers(): void
    {
        $this->an_active_user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);

        $this->base_language->expects(self::once())->method('getOverridableText')->willReturn('A Subject');
        $this->base_language->expects(self::once())->method('getContent')->willReturn(__DIR__ . '/_fixtures/empty.php');

        $mail = $this->createMock(\Codendi_Mail::class);
        $mail->expects(self::once())->method('setTo');
        $mail->expects(self::once())->method('setFrom');
        $mail->expects(self::once())->method('setSubject');
        $mail->expects(self::once())->method('setBodyText');
        $mail->expects(self::once())->method('send')->willReturn(true);

        $this->mail_factory->expects(self::once())->method('getMail')->willReturn($mail);

        $this->add_project_member->expects(self::once())->method('addProjectMember')->with($this->an_active_user, $this->an_active_project, $this->project_admin);

        $this->project_member_adder->addProjectMemberWithFeedback($this->an_active_user, $this->an_active_project, $this->project_admin);
    }

    public function testItRaisesAnErrorWhenAddedUserDoesntHaveAnEmail(): void
    {
        $this->an_active_user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => '']);

        $this->add_project_member->expects(self::once())->method('addProjectMember')->with($this->an_active_user, $this->an_active_project, $this->project_admin);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($this->an_active_user, $this->an_active_project, $this->project_admin);
    }

    public function testItDoesntProceedWithSuspendedUsers(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_SUSPENDED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($user, $this->an_active_project, $this->project_admin);
    }

    public function testItDoesntProceedWithDeletedUsers(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_DELETED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($user, $this->an_active_project, $this->project_admin);
    }

    public function testItDisplaysAnErrorWhenRestrictedUserIsAddedToWoRestrictedProject(): void
    {
        $this->add_project_member->method('addProjectMember')->willThrowException(new CannotAddRestrictedUserToProjectNotAllowingRestricted($this->an_active_user, $this->an_active_project));

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($this->an_active_user, $this->an_active_project, $this->project_admin);
    }

    public function testItDisplaysAnErrorWhenUserIsAlreadyMember(): void
    {
        $this->add_project_member->method('addProjectMember')->willThrowException(new AlreadyProjectMemberException());

        $GLOBALS['Response']->expects(self::once())->method('addFeedback')->with(\Feedback::ERROR);

        $this->project_member_adder->addProjectMemberWithFeedback($this->an_active_user, $this->an_active_project, $this->project_admin);
    }
}
