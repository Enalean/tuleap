<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace User\XML\Import;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\GlobalLanguageMock;
use UserManager;

final class WillBeCreatedUserTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private ReadyToBeImportedUser $user;
    private UserManager&MockObject $user_manager;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager = $this->createMock(UserManager::class);
        $this->logger       = new NullLogger();

        $this->user = new WillBeCreatedUser(
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            'S',
            'ed143'
        );
    }

    public function testItCreatesANewUserInDatabase(): void
    {
        $this->user_manager->expects(self::once())->method('createAccount')->willReturn(new \PFUser(['language_id' => 'en']));

        $this->user->process($this->user_manager, $this->logger);
    }

    public function testItThrowsAnExceptionIfUserCannotBeCreated(): void
    {
        $this->user_manager->method('createAccount')->willReturn(null);

        $this->expectException(\User\XML\Import\UserCannotBeCreatedException::class);

        $this->user->process($this->user_manager, $this->logger);
    }
}
