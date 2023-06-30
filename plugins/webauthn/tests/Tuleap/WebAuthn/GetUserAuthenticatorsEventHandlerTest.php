<?php
/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn;

use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\WebAuthn\WebAuthnCredentialSourceDaoStub;
use Tuleap\User\Admin\GetUserAuthenticatorsEvent;

final class GetUserAuthenticatorsEventHandlerTest extends TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']->method('getText')->willReturn('c');
    }

    public function testItReturnsEmptyArray(): void
    {
        $event = new GetUserAuthenticatorsEvent(UserTestBuilder::aUser()->build());

        $handler = new GetUserAuthenticatorsEventHandler(WebAuthnCredentialSourceDaoStub::withoutCredentialSources());
        $handler->handle($event);

        self::assertTrue($event->answered);
        self::assertEmpty($event->authenticators);
    }

    public function testItReturnsNonEmptyArray(): void
    {
        $sources = ['1', '2', '3'];

        $event = new GetUserAuthenticatorsEvent(UserTestBuilder::aUser()->build());

        $handler = new GetUserAuthenticatorsEventHandler(WebAuthnCredentialSourceDaoStub::withCredentialSources(...$sources));
        $handler->handle($event);

        self::assertTrue($event->answered);
        self::assertNotEmpty($event->authenticators);
        self::assertSameSize($sources, $event->authenticators);
    }
}
