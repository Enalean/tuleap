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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Psr\Log\LoggerInterface;
use Tuleap\Project\ProjectCreationNotifier;
use TuleapRegisterMail;

class ProjectCreationNotifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LoggerInterface
     */
    private $logger;
    /**
     * @var ProjectCreationNotifier
     */
    private $project_creation_notifier;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TuleapRegisterMail
     */
    private $register_mail;

    protected function setUp(): void
    {
        $this->register_mail = Mockery::mock(TuleapRegisterMail::class);
        $this->logger        = Mockery::mock(LoggerInterface::class);

        $this->project_creation_notifier = new ProjectCreationNotifier($this->register_mail, $this->logger);

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getPublicName')->andReturn('new_project');
    }

    public function testNotifySiteAdmin(): void
    {
        $mail = Mockery::mock(Codendi_Mail::class);
        $mail->shouldReceive('send')->andReturn(true);

        $this->register_mail
            ->shouldReceive('getMailNotificationProject')
            ->withArgs(
                [
                    'New project registered: new_project',
                    false,
                    false,
                    $this->project
                ]
            )->andReturn($mail);

        $this->logger->shouldReceive('Warning')->never();

        $this->project_creation_notifier->notifySiteAdmin($this->project);
    }

    public function testNotifySiteAdminLoggeWaringIfMailNotSend(): void
    {
        $mail = Mockery::mock(Codendi_Mail::class);
        $mail->shouldReceive('send')->andReturn(false);

        $this->register_mail
            ->shouldReceive('getMailNotificationProject')
            ->withArgs(
                [
                    'New project registered: new_project',
                    false,
                    false,
                    $this->project
                ]
            )->andReturn($mail);

        $this->logger->shouldReceive('Warning')->once();

        $this->project_creation_notifier->notifySiteAdmin($this->project);
    }
}
