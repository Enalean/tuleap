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

use Logger;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\GlobalLanguageMock;
use UserManager;

final class WillBeCreatedUserTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /** @var ToBeCreatedUser */
    private $user;

    /** @var UserManager */
    private $user_manager;

    /** @var Logger */
    private $logger;

    protected function setUp() : void
    {
        parent::setUp();

        $this->user_manager = \Mockery::spy(UserManager::class);
        $this->logger       = \Mockery::spy(\Psr\Log\LoggerInterface::class);

        $this->user = new WillBeCreatedUser(
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            'S',
            'ed143'
        );
    }

    public function testItCreatesANewUserInDatabase() : void
    {
        $this->user_manager->shouldReceive('createAccount')->once()->andReturns(new \PFUser(['language_id' => 'en']));

        $this->user->process($this->user_manager, $this->logger);
    }

    public function testItThrowsAnExceptionIfUserCannotBeCreated() : void
    {
        $this->user_manager->shouldReceive('createAccount')->andReturns(false);

        $this->expectException(\User\XML\Import\UserCannotBeCreatedException::class);

        $this->user->process($this->user_manager, $this->logger);
    }
}
