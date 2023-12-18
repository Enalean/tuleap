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
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups\Membership\DynamicUGroups;

use BaseLanguage;
use Feedback;
use Tuleap\Mail\MailFactory;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\Admin\ProjectUGroup\CannotAddRestrictedUserToProjectNotAllowingRestricted;

class ProjectMemberAdderWithStatusCheckAndNotifications implements ProjectMemberAdder
{
    /**
     * @var BaseLanguage
     */
    private $language;
    /**
     * @var MailFactory
     */
    private $mail_factory;
    /**
     * @var AddProjectMember
     */
    private $project_member_adder;

    public function __construct(AddProjectMember $project_member_adder, BaseLanguage $language, MailFactory $mail_factory)
    {
        $this->project_member_adder = $project_member_adder;
        $this->language             = $language;
        $this->mail_factory         = $mail_factory;
    }

    public static function build(): self
    {
        return new self(
            AddProjectMember::build(),
            $GLOBALS['Language'],
            new MailFactory()
        );
    }

    public static function buildWithoutPermissionsChecks(): self
    {
        return new self(
            AddProjectMember::buildWithoutPermissionsChecks(),
            $GLOBALS['Language'],
            new MailFactory()
        );
    }

    public function addProjectMemberWithFeedback(\PFUser $user, \Project $project, \PFUser $project_admin): void
    {
        try {
            $this->addProjectMember($user, $project, $project_admin);
            $GLOBALS['Response']->addFeedback(Feedback::INFO, _('User added'));
        } catch (UserIsNotActiveOrRestrictedException) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('include_account', 'account_notactive', $user->getUserName()));
        } catch (CannotAddRestrictedUserToProjectNotAllowingRestricted $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (AlreadyProjectMemberException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
        } catch (NoEmailForUserException $exception) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('No email for user account'));
        } catch (UserIsNotAllowedToManageProjectMembersException $e) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('Must be project admin to add a project member'));
        }
    }

    /**
     * @throws UserIsNotActiveOrRestrictedException
     * @throws CannotAddRestrictedUserToProjectNotAllowingRestricted
     * @throws AlreadyProjectMemberException
     * @throws NoEmailForUserException
     * @throws UserIsNotAllowedToManageProjectMembersException
     */
    public function addProjectMember(\PFUser $user, \Project $project, \PFUser $project_admin): void
    {
        if (! $user->isActive() && ! $user->isRestricted()) {
            throw new UserIsNotActiveOrRestrictedException();
        }

        $this->project_member_adder->addProjectMember($user, $project, $project_admin);
        $this->sendNotification($user, $project);
    }

    private function sendNotification(\PFUser $user, \Project $project): void
    {
        if (! $user->getEmail()) {
            throw new NoEmailForUserException();
        }

        $mail = $this->mail_factory->getMail();
        $mail->setTo($user->getEmail());
        $mail->setFrom(\ForgeConfig::get('sys_noreply'));
        $mail->setSubject($this->language->getOverridableText('include_account', 'welcome', [\ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME), $project->getPublicName()]));
        $mail->setBodyText($this->getMessageBody($project));
        if (! $mail->send()) {
            $GLOBALS['Response']->addFeedback(Feedback::ERROR, sprintf(_('Unable to send email to user, please contact %s'), \ForgeConfig::get('sys_email_admin')));
        }
    }

    /**
     * Both variables $base_url and $unix_group_name are used
     * by default in add_user_to_group_email.txt
     */
    private function getMessageBody(\Project $project): string
    {
        $group_name      = $project->getPublicName();
        $base_url        = \Tuleap\ServerHostname::HTTPSUrl();
        $unix_group_name = $project->getUnixName();
        // $message is defined in the content file
        $message = '';
        include($this->language->getContent('include/add_user_to_group_email'));

        return $message;
    }
}
