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
use ColinODell\PsrTestLogger\TestLogger;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use Tuleap\Project\ProjectCreationNotifier;
use Tuleap\Test\Builders\ProjectTestBuilder;
use TuleapRegisterMail;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectCreationNotifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TestLogger $logger;
    private ProjectCreationNotifier $project_creation_notifier;
    private Project $project;
    private TuleapRegisterMail&Stub $register_mail;

    #[\Override]
    protected function setUp(): void
    {
        $this->register_mail = $this->createStub(TuleapRegisterMail::class);
        $this->logger        = new TestLogger();

        $this->project_creation_notifier = new ProjectCreationNotifier($this->register_mail, $this->logger);

        $this->project = ProjectTestBuilder::aProject()->withPublicName('new_project')->build();
    }

    public function testNotifySiteAdmin(): void
    {
        $mail = $this->createStub(Codendi_Mail::class);
        $mail->method('send')->willReturn(true);

        $this->register_mail
            ->method('getMailNotificationProject')
            ->with(
                'New project registered: new_project',
                false,
                false,
                $this->project
            )->willReturn($mail);

        $this->project_creation_notifier->notifySiteAdmin($this->project);

        self::assertFalse($this->logger->hasWarningRecords());
    }

    public function testNotifySiteAdminLoggerWarningIfMailNotSend(): void
    {
        $mail = $this->createStub(Codendi_Mail::class);
        $mail->method('send')->willReturn(false);

        $this->register_mail
            ->method('getMailNotificationProject')
            ->with(
                'New project registered: new_project',
                false,
                false,
                $this->project
            )->willReturn($mail);

        $this->project_creation_notifier->notifySiteAdmin($this->project);

        self::assertTrue($this->logger->hasWarningRecords());
    }
}
