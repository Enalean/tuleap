<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class User_LoginManagerStatusTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Tuleap\ForgeConfigSandbox;
    use \Tuleap\GlobalLanguageMock;

    private User_UserStatusManager $user_status_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user_status_manager = new User_UserStatusManager();
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testItSucceedsIfUserIsActive(): void
    {
        $this->expectNotToPerformAssertions();
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_ACTIVE)
        );
    }

    public function testItSucceedsIfUserIsRestricted(): void
    {
        $this->expectNotToPerformAssertions();
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_RESTRICTED)
        );
    }

    public function testItSucceedsIfAllowPendingAndStatusIsValidated(): void
    {
        $this->expectNotToPerformAssertions();
        $this->user_status_manager->checkStatusOnVerifyPage(
            $this->buildUser(PFUser::STATUS_VALIDATED)
        );
    }

    public function testItSucceedsIfAllowPendingAndStatusIsValidatedRestricted(): void
    {
        $this->expectNotToPerformAssertions();
        $this->user_status_manager->checkStatusOnVerifyPage(
            $this->buildUser(PFUser::STATUS_VALIDATED_RESTRICTED)
        );
    }

    public function testItSucceedsIfAllowPendingAndStatusIsPendingAndNoUserApproval(): void
    {
        $this->expectNotToPerformAssertions();
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);
        $this->user_status_manager->checkStatusOnVerifyPage(
            $this->buildUser(PFUser::STATUS_PENDING)
        );
    }

    public function testItRaisesAnExceptionWhenUserIsDeleted(): void
    {
        $this->expectException(\User_StatusDeletedException::class);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_DELETED)
        );
    }

    public function testItRaisesAnExceptionWhenUserIsSuspended(): void
    {
        $this->expectException(\User_StatusSuspendedException::class);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_SUSPENDED)
        );
    }

    public function testItRaisesAnExceptionWhenStatusIsUnknown(): void
    {
        $this->expectException(\User_StatusInvalidException::class);
        $this->user_status_manager->checkStatus(
            $this->buildUser('dsfd')
        );
    }

    public function testItRaisesAnExceptionIfAllowPendingAndStatusIsUnknown(): void
    {
        $this->expectException(\User_StatusInvalidException::class);
        $this->user_status_manager->checkStatusOnVerifyPage(
            $this->buildUser('dfd')
        );
    }

    public function testItRaisesAnExceptionWhenSiteMandateUserApprovalAndStatusIsPending(): void
    {
        $this->expectException(\User_StatusPendingException::class);
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 1);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_PENDING)
        );
    }

    public function testItRaisesAnExceptionWhenSiteMandateUserApprovalAndStatusIsValidated(): void
    {
        $this->expectException(\User_StatusPendingException::class);
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 1);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_VALIDATED)
        );
    }

    public function testItRaisesAnExceptionWhenSiteDoesntMandateUserApprovalAndStatusIsValidated(): void
    {
        $this->expectException(\User_StatusPendingException::class);
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_VALIDATED)
        );
    }

    public function testItRaisesAnExceptionWhenSiteMandateUserApprovalAndStatusIsValidatedRestricted(): void
    {
        $this->expectException(\User_StatusPendingException::class);
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 1);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_VALIDATED_RESTRICTED)
        );
    }

    public function testItRaisesAnExceptionWhenSiteDoesntMandateUserApprovalAndStatusIsValidatedRestricted(): void
    {
        $this->expectException(\User_StatusPendingException::class);
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_VALIDATED_RESTRICTED)
        );
    }

    public function testItRaisesAnExceptionWhenSiteDoesntMandateUserApprovalAndStatusIsPending(): void
    {
        $this->expectException(\User_StatusPendingException::class);
        ForgeConfig::set(User_UserStatusManager::CONFIG_USER_REGISTRATION_APPROVAL, 0);
        $this->user_status_manager->checkStatus(
            $this->buildUser(PFUser::STATUS_PENDING)
        );
    }

    private function buildUser(string $status): PFUser
    {
        return new PFUser(['password' => 'password', 'status' => $status]);
    }
}
