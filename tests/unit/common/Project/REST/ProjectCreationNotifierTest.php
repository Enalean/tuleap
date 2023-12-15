<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Project\REST;

use Codendi_Mail;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Project\ProjectCreationNotifier;
use Tuleap\Test\Builders\ProjectTestBuilder;
use TuleapRegisterMail;

class ProjectCreationNotifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LoggerInterface&MockObject $logger;
    private ProjectCreationNotifier $project_creation_notifier;
    private Project $project;
    private TuleapRegisterMail&MockObject $register_mail;

    protected function setUp(): void
    {
        $this->register_mail = $this->createMock(TuleapRegisterMail::class);
        $this->logger        = $this->createMock(LoggerInterface::class);

        $this->project_creation_notifier = new ProjectCreationNotifier($this->register_mail, $this->logger);

        $this->project = ProjectTestBuilder::aProject()->withPublicName('new_project')->build();
    }

    public function testNotifySiteAdmin(): void
    {
        $mail = $this->createMock(Codendi_Mail::class);
        $mail->method('send')->willReturn(true);

        $this->register_mail
            ->method('getMailNotificationProject')
            ->with(
                'New project registered: new_project',
                false,
                false,
                $this->project
            )->willReturn($mail);

        $this->logger->expects(self::never())->method('Warning');

        $this->project_creation_notifier->notifySiteAdmin($this->project);
    }

    public function testNotifySiteAdminLoggerWaringIfMailNotSend(): void
    {
        $mail = $this->createMock(Codendi_Mail::class);
        $mail->method('send')->willReturn(false);

        $this->register_mail
            ->method('getMailNotificationProject')
            ->with(
                'New project registered: new_project',
                false,
                false,
                $this->project
            )->willReturn($mail);

        $this->logger->expects(self::once())->method('Warning');

        $this->project_creation_notifier->notifySiteAdmin($this->project);
    }
}
