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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Mail\MailFactory;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;

class ProjectMemberAdderWithStatusCheckAndNotificationsTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    /**
     * @var M\MockInterface|AddProjectMember
     */
    private $add_project_member;
    /**
     * @var \BaseLanguage|M\MockInterface
     */
    private $base_language;
    /**
     * @var M\MockInterface|MailFactory
     */
    private $mail_factory;
    /**
     * @var ProjectMemberAdderWithStatusCheckAndNotifications
     */
    private $project_member_adder;
    /**
     * @var \PFUser
     */
    private $an_active_user;
    /**
     * @var \Project
     */
    private $an_active_project;

    protected function setUp(): void
    {
        $this->add_project_member = M::mock(AddProjectMember::class);
        $this->base_language      = M::mock(\BaseLanguage::class);
        $this->mail_factory       = M::mock(MailFactory::class);
        \ForgeConfig::set('sys_noreply', 'noreply@tuleap.example.com');
        \ForgeConfig::set('sys_name', 'Tuleap');
        \ForgeConfig::set('sys_email_admin', 'admin@tuleap.example.com');

        $this->an_active_user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_ACTIVE, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);
        $this->an_active_project = M::mock(\Project::class, ['getID' => 202, 'getPublicName' => 'A project name', 'getUnixName' => 'a-project-name']);

        $this->project_member_adder = new ProjectMemberAdderWithStatusCheckAndNotifications(
            $this->add_project_member,
            $this->base_language,
            $this->mail_factory
        );
    }

    public function testItAddsAndNotifyActiveUsers(): void
    {
        $this->base_language->shouldReceive('getOverridableText')->once()->andReturn('A Subject');
        $this->base_language->shouldReceive('getContent')->once()->andReturn(__DIR__ . '/_fixtures/empty.php');

        $mail = M::mock(\Codendi_Mail::class);
        $mail->shouldReceive('setTo')->with('foo@example.com')->once();
        $mail->shouldReceive('setFrom')->once();
        $mail->shouldReceive('setSubject')->with('A Subject')->once();
        $mail->shouldReceive('setBodyText')->with('A body')->once();
        $mail->shouldReceive('send')->once()->andReturnTrue();

        $this->mail_factory->shouldReceive('getMail')->once()->andReturn($mail);

        $this->add_project_member->shouldReceive('addProjectMember')->with($this->an_active_user, $this->an_active_project)->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::INFO, M::any())->once();

        $this->project_member_adder->addProjectMember($this->an_active_user, $this->an_active_project);
    }

    public function testItAddsAndNotifyRestrictedUsers(): void
    {
        $this->an_active_user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);

        $this->base_language->shouldReceive('getOverridableText')->once()->andReturn('A Subject');
        $this->base_language->shouldReceive('getContent')->once()->andReturn(__DIR__ . '/_fixtures/empty.php');

        $mail = M::spy(\Codendi_Mail::class);
        $mail->shouldReceive('send')->once()->andReturnTrue();

        $this->mail_factory->shouldReceive('getMail')->once()->andReturn($mail);

        $this->add_project_member->shouldReceive('addProjectMember')->with($this->an_active_user, $this->an_active_project)->once();

        $this->project_member_adder->addProjectMember($this->an_active_user, $this->an_active_project);
    }

    public function testItRaisesAnErrorWhenAddedUserDoesntHaveAnEmail(): void
    {
        $this->an_active_user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_RESTRICTED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => '']);

        $this->add_project_member->shouldReceive('addProjectMember')->with($this->an_active_user, $this->an_active_project)->once();

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($this->an_active_user, $this->an_active_project);
    }

    public function testItDoesntProceedWithSuspendedUsers(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_SUSPENDED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($user, $this->an_active_project);
    }

    public function testItDoesntProceedWithDeletedUsers(): void
    {
        $user = new \PFUser(['user_id' => 101, 'user_name' => 'foo', 'status' => \PFUser::STATUS_DELETED, 'language_id' => \BaseLanguage::DEFAULT_LANG, 'email' => 'foo@example.com']);

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($user, $this->an_active_project);
    }

    public function testItDisplaysAnErrorWhenRestrictedUserIsAddedToWoRestrictedProject(): void
    {
        $this->add_project_member->shouldReceive('addProjectMember')->andThrow(new CannotAddRestrictedUserToProjectNotAllowingRestricted($this->an_active_user, $this->an_active_project));

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($this->an_active_user, $this->an_active_project);
    }

    public function testItDisplaysAnErrorWhenUserIsAlreadyMember(): void
    {
        $this->add_project_member->shouldReceive('addProjectMember')->andThrow(new AlreadyProjectMemberException());

        $GLOBALS['Response']->shouldReceive('addFeedback')->with(\Feedback::ERROR, M::any())->once();

        $this->project_member_adder->addProjectMember($this->an_active_user, $this->an_active_project);
    }
}
