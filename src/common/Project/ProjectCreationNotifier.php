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

namespace Tuleap\Project;

use ForgeConfig;
use Project;
use Psr\Log\LoggerInterface;
use TuleapRegisterMail;

class ProjectCreationNotifier implements NotifySiteAdmin
{
    private $logger;
    /**
     * @var TuleapRegisterMail
     */
    private $register_mail;

    public function __construct(TuleapRegisterMail $register_mail, LoggerInterface $logger)
    {
        $this->register_mail = $register_mail;
        $this->logger        = $logger;
    }

    public function notifySiteAdmin(Project $project): void
    {
        $subject = sprintf(
            dgettext('tuleap-core', 'New project registered: %s'),
            $project->getPublicName()
        );

        $mail = $this->register_mail->getMailNotificationProject(
            $subject,
            ForgeConfig::get('sys_noreply'),
            ForgeConfig::get('sys_email_admin'),
            $project
        );

        if (! $mail->send()) {
            $this->logger->warning(
                "The mail for project" . $project->getPublicName() . "creation was not accepted for the delivery."
            );
        }
    }
}
