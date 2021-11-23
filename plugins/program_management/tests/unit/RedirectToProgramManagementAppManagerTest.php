<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

final class RedirectToProgramManagementAppManagerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsTrueWhenARedirectionPostCreationIsNeeded(): void
    {
        $request = $this->buildCodendiRequest(RedirectToProgramManagementAppManager::REDIRECT_AFTER_CREATE_ACTION);
        $manager = RedirectToProgramManagementAppManager::buildFromCodendiRequest($request);

        self::assertTrue($manager->needsRedirectionAfterCreate());
        self::assertTrue($manager->isRedirectionNeeded());
        self::assertFalse($manager->needsRedirectionAfterUpdate());
        self::assertEquals(RedirectToProgramManagementAppManager::REDIRECT_AFTER_CREATE_ACTION, $manager->getRedirectValue());
    }

    public function testItReturnsTrueWhenARedirectionPostUpdateIsNeeded(): void
    {
        $request = $this->buildCodendiRequest(RedirectToProgramManagementAppManager::REDIRECT_AFTER_UPDATE_ACTION);
        $manager = RedirectToProgramManagementAppManager::buildFromCodendiRequest($request);

        self::assertTrue($manager->needsRedirectionAfterUpdate());
        self::assertTrue($manager->isRedirectionNeeded());
        self::assertFalse($manager->needsRedirectionAfterCreate());
        self::assertEquals(RedirectToProgramManagementAppManager::REDIRECT_AFTER_UPDATE_ACTION, $manager->getRedirectValue());
    }

    private function buildCodendiRequest(?string $redirect_value): \Codendi_Request
    {
        $request = $this->createMock(\Codendi_Request::class);
        $request->expects(self::once())
            ->method('get')
            ->with(RedirectToProgramManagementAppManager::FLAG)
            ->willReturn($redirect_value);

        return $request;
    }
}
