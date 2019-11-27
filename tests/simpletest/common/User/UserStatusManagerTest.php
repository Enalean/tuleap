<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

class User_LoginManagerStatusTest extends TuleapTestCase
{
    private $user_status_manager;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        ForgeConfig::store();
        $this->user_status_manager = new User_UserStatusManager();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itSucceedsIfUserIsActive()
    {
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_ACTIVE)->build()
        );
    }

    public function itSucceedsIfUserIsRestricted()
    {
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_RESTRICTED)->build()
        );
    }

    public function itSucceedsIfAllowPendingAndStatusIsValidated()
    {
        $this->user_status_manager->checkStatusOnVerifyPage(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_VALIDATED)->build()
        );
    }

    public function itSucceedsIfAllowPendingAndStatusIsValidatedRestricted()
    {
        $this->user_status_manager->checkStatusOnVerifyPage(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_VALIDATED_RESTRICTED)->build()
        );
    }

    public function itSucceedsIfAllowPendingAndStatusIsPendingAndNoUserApproval()
    {
        ForgeConfig::set('sys_user_approval', 0);
        $this->user_status_manager->checkStatusOnVerifyPage(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_PENDING)->build()
        );
    }

    public function itRaisesAnExceptionWhenUserIsDeleted()
    {
        $this->expectException('User_StatusDeletedException');
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_DELETED)->build()
        );
    }

    public function itRaisesAnExceptionWhenUserIsSuspended()
    {
        $this->expectException('User_StatusSuspendedException');
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_SUSPENDED)->build()
        );
    }

    public function itRaisesAnExceptionWhenStatusIsUnknown()
    {
        $this->expectException('User_StatusInvalidException');
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus('dsfd')->build()
        );
    }

    public function itRaisesAnExceptionIfAllowPendingAndStatusIsUnknown()
    {
        $this->expectException('User_StatusInvalidException');
        $this->user_status_manager->checkStatusOnVerifyPage(
            aUser()->withPassword('password')->withStatus('dfd')->build()
        );
    }

    public function itRaisesAnExceptionWhenSiteMandateUserApprovalAndStatusIsPending()
    {
        $this->expectException('User_StatusPendingException');
        ForgeConfig::set('sys_user_approval', 1);
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_PENDING)->build()
        );
    }

    public function itRaisesAnExceptionWhenSiteMandateUserApprovalAndStatusIsValidated()
    {
        $this->expectException('User_StatusPendingException');
        ForgeConfig::set('sys_user_approval', 1);
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_VALIDATED)->build()
        );
    }

    public function itRaisesAnExceptionWhenSiteDoesntMandateUserApprovalAndStatusIsValidated()
    {
        $this->expectException('User_StatusPendingException');
        ForgeConfig::set('sys_user_approval', 0);
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_VALIDATED)->build()
        );
    }

    public function itRaisesAnExceptionWhenSiteMandateUserApprovalAndStatusIsValidatedRestricted()
    {
        $this->expectException('User_StatusPendingException');
        ForgeConfig::set('sys_user_approval', 1);
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_VALIDATED_RESTRICTED)->build()
        );
    }

    public function itRaisesAnExceptionWhenSiteDoesntMandateUserApprovalAndStatusIsValidatedRestricted()
    {
        $this->expectException('User_StatusPendingException');
        ForgeConfig::set('sys_user_approval', 0);
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_VALIDATED_RESTRICTED)->build()
        );
    }

    public function itRaisesAnExceptionWhenSiteDoesntMandateUserApprovalAndStatusIsPending()
    {
        $this->expectException('User_StatusPendingException');
        ForgeConfig::set('sys_user_approval', 0);
        $this->user_status_manager->checkStatus(
            aUser()->withPassword('password')->withStatus(PFUser::STATUS_PENDING)->build()
        );
    }
}
