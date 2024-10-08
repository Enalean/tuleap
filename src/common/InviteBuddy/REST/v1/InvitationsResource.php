<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\InviteBuddy\REST\v1;

use Luracast\Restler\RestException;
use Tuleap\Authentication\SplitToken\PrefixedSplitTokenSerializer;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\InviteBuddy\InvitationEmailNotifier;
use Tuleap\InviteBuddy\InvitationInstrumentation;
use Tuleap\InviteBuddy\InvitationLimitChecker;
use Tuleap\InviteBuddy\InvitationSender;
use Tuleap\InviteBuddy\InvitationSenderGateKeeper;
use Tuleap\InviteBuddy\InvitationSenderGateKeeperException;
use Tuleap\InviteBuddy\InvitationToOneRecipientWithoutVerificationSender;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\InviteBuddy\PrefixTokenInvitation;
use Tuleap\InviteBuddy\UnableToSendInvitationsException;
use Tuleap\Language\LocaleSwitcher;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\ProjectMembers\UserCanManageProjectMembersChecker;
use Tuleap\Project\Admin\ProjectMembers\UserIsNotAllowedToManageProjectMembersException;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithStatusCheckAndNotifications;
use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;
use Tuleap\REST\I18NRestException;
use Tuleap\User\Avatar\AvatarHashDao;
use Tuleap\User\Avatar\ComputeAvatarHash;
use Tuleap\User\Avatar\UserAvatarUrlProvider;

class InvitationsResource extends AuthenticatedResource
{
    public const ROUTE = 'invitations';

    /**
     * @url OPTIONS
     * @access protected
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Create a new invitation
     *
     * Example of expected format:
     * <pre>
     * {<br/>
     *   "emails": ["john.doe@example.com", …],<br/>
     *   "custom_message": "A custom message",<br/>
     * }<br/>
     * </pre>
     * <br/>
     * Mails that receive a failure while sending them will be returned in the <code>failures</code> field.<br/>
     * If every requested mails are in failure, then you will get an Error 500 instead.
     *
     * @url POST
     *
     * @access protected
     *
     * @param InvitationPOSTRepresentation $invitation The access key representation for creation.
     *
     * @status 201
     *
     * @throws RestException 400
     */
    public function post(InvitationPOSTRepresentation $invitation): InvitationPOSTResultRepresentation
    {
        Header::allowOptionsPost();

        $user_manager = \UserManager::instance();
        $current_user = $user_manager->getCurrentUser();

        $dao                        = new InvitationDao(new SplitTokenVerificationStringHasher(), new InvitationInstrumentation(Prometheus::instance()));
        $invite_buddy_configuration = new InviteBuddyConfiguration(\EventManager::instance());
        $sender                     = new InvitationSender(
            new InvitationSenderGateKeeper(
                new \Valid_Email(),
                $invite_buddy_configuration,
                new InvitationLimitChecker($dao, $invite_buddy_configuration)
            ),
            $user_manager,
            \BackendLogger::getDefaultLogger(),
            new UserCanManageProjectMembersChecker(new MembershipDelegationDao()),
            new InvitationToOneRecipientWithoutVerificationSender(
                new InvitationEmailNotifier(new LocaleSwitcher()),
                $dao,
                $dao,
                new InvitationInstrumentation(Prometheus::instance()),
                new PrefixedSplitTokenSerializer(new PrefixTokenInvitation()),
                new \ProjectHistoryDao(),
            ),
            ProjectMemberAdderWithStatusCheckAndNotifications::build(),
        );


        try {
            $project = $invitation->project_id
                ? \ProjectManager::instance()->getValidProjectById($invitation->project_id)
                : null;

            $result = $sender->send(
                $current_user,
                array_filter($invitation->emails),
                $project,
                $invitation->custom_message,
                null,
            );

            return InvitationPOSTResultRepresentation::fromResult(
                $result,
                new UserAvatarUrlProvider(new AvatarHashDao(), new ComputeAvatarHash()),
            );
        } catch (\Project_NotFoundException | UserIsNotAllowedToManageProjectMembersException) {
            throw new RestException(404);
        } catch (InvitationSenderGateKeeperException $e) {
            throw new I18NRestException(400, $e->getMessage());
        } catch (UnableToSendInvitationsException $e) {
            throw new I18NRestException(500, $e->getMessage());
        }
    }
}
