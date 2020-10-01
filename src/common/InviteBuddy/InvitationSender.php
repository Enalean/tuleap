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

namespace Tuleap\InviteBuddy;

use PFUser;
use Psr\Log\LoggerInterface;

class InvitationSender
{
    public const  STATUS_SENT  = 'sent';
    private const STATUS_ERROR = 'error';

    /**
     * @var InvitationSenderGateKeeper
     */
    private $gate_keeper;
    /**
     * @var InvitationEmailNotifier
     */
    private $email_notifier;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var InvitationDao
     */
    private $dao;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var InvitationInstrumentation
     */
    private $instrumentation;

    public function __construct(
        InvitationSenderGateKeeper $gate_keeper,
        InvitationEmailNotifier $email_notifier,
        \UserManager $user_manager,
        InvitationDao $dao,
        LoggerInterface $logger,
        InvitationInstrumentation $instrumentation
    ) {
        $this->gate_keeper     = $gate_keeper;
        $this->email_notifier  = $email_notifier;
        $this->user_manager    = $user_manager;
        $this->dao             = $dao;
        $this->logger          = $logger;
        $this->instrumentation = $instrumentation;
    }

    /**
     * @param string[] $emails
     *
     * @return string[] emails in failure
     *
     * @throws InvitationSenderGateKeeperException
     * @throws UnableToSendInvitationsException
     */
    public function send(PFUser $current_user, array $emails, ?string $custom_message): array
    {
        $emails = array_filter($emails);
        $this->gate_keeper->checkNotificationsCanBeSent($current_user, $emails);

        $now = (new \DateTimeImmutable())->getTimestamp();

        $failures = [];
        foreach ($emails as $email) {
            $recipient = new InvitationRecipient(
                $this->user_manager->getUserByEmail($email),
                $email,
            );

            $status = self::STATUS_SENT;
            if ($this->email_notifier->send($current_user, $recipient, $custom_message)) {
                $this->instrumentation->increment();
            } else {
                $this->logger->error("Unable to send invitation from user #{$current_user->getId()} to $email");
                $status     = self::STATUS_ERROR;
                $failures[] = $email;
            }

            $this->dao->save(
                $now,
                (int) $current_user->getId(),
                $email,
                $recipient->getUserId(),
                $custom_message,
                $status
            );
        }

        if (count($failures) === count($emails)) {
            throw new UnableToSendInvitationsException(
                ngettext(
                    "An error occurred while trying to send invitation",
                    "An error occurred while trying to send invitations",
                    count($failures)
                )
            );
        }

        return $failures;
    }
}
