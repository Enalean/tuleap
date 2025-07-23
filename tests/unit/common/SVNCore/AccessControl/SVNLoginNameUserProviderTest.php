<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\SVNCore\AccessControl;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SVNLoginNameUserProviderTest extends TestCase
{
    public function testUsesUserIdentifierViaTheEventSystem(): void
    {
        $expected_user    = UserTestBuilder::buildWithDefaults();
        $event_dispatcher = new class ($expected_user) implements EventDispatcherInterface
        {
            public function __construct(private \PFUser $expected_user)
            {
            }

            #[\Override]
            public function dispatch(object $event): object
            {
                assert($event instanceof UserRetrieverBySVNLoginNameEvent);
                $event->user = $this->expected_user;
                return $event;
            }
        };
        $user_provider    = new SVNLoginNameUserProvider($this->createStub(\UserManager::class), $event_dispatcher);

        self::assertSame($expected_user, $user_provider->getUserFromSVNLoginName('my_svn_login_name', ProjectTestBuilder::aProject()->build()));
    }

    public function testIdentifyUserFromItsTuleapUserNameWhenNotFoundViaTheEventSystem(): void
    {
        $event_dispatcher = new class implements EventDispatcherInterface
        {
            #[\Override]
            public function dispatch(object $event): object
            {
                return $event;
            }
        };
        $user_manager     = $this->createStub(\UserManager::class);
        $expected_user    = UserTestBuilder::buildWithDefaults();
        $user_manager->method('getUserByUserName')->willReturn($expected_user);

        $user_provider = new SVNLoginNameUserProvider($user_manager, $event_dispatcher);

        self::assertSame($expected_user, $user_provider->getUserFromSVNLoginName('login_name', ProjectTestBuilder::aProject()->build()));
    }

    public function testDoesNotFallbackToUsingTuleapUsernameWhenForbiddenViaTheTuleapSystem(): void
    {
        $event_dispatcher = new class implements EventDispatcherInterface
        {
            #[\Override]
            public function dispatch(object $event): object
            {
                assert($event instanceof UserRetrieverBySVNLoginNameEvent);
                $event->can_user_be_provided_by_other_means = false;
                return $event;
            }
        };

        $user_provider = new SVNLoginNameUserProvider($this->createStub(\UserManager::class), $event_dispatcher);

        self::assertNull($user_provider->getUserFromSVNLoginName('login_name', ProjectTestBuilder::aProject()->build()));
    }
}
