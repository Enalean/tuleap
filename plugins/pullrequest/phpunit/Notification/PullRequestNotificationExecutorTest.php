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

namespace Tuleap\PullRequest\Notification;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\PullRequest\PullRequest;

final class PullRequestNotificationExecutorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testLogMessageForEachRecipient(): void
    {
        $logger = \Mockery::mock(\Logger::class);

        $executor = new PullRequestNotificationExecutor($logger);

        $notification = new class implements NotificationToProcess {
            public function getPullRequest(): PullRequest
            {
                $pull_request = \Mockery::mock(PullRequest::class);
                $pull_request->shouldReceive('getId')->andReturn(147);
                return $pull_request;
            }

            public function getRecipients(): array
            {
                return [$this->buildUser(), $this->buildUser()];
            }

            private function buildUser(): \PFUser
            {
                $user = \Mockery::mock(\PFUser::class);
                $user->shouldReceive('getEmail')->once()->andReturn('email');
                return $user;
            }

            public function asPlaintext(): string
            {
                return 'Notification body';
            }
        };

        $logger->shouldReceive('debug')->twice();

        $executor->execute($notification);
    }
}
