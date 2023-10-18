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

namespace Tuleap\User\AccessKey\Scope;

use Tuleap\Authentication\Scope\AuthenticationScope;

final class AccessKeyScopeSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyScopeDAO
     */
    private $dao;
    /**
     * @var AccessKeyScopeSaver
     */
    private $saver;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(AccessKeyScopeDAO::class);

        $this->saver = new AccessKeyScopeSaver($this->dao);
    }

    public function testScopesAreDedupedWhenBeingSaved(): void
    {
        $identifier = AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar');

        $scope_a = $this->createMock(AuthenticationScope::class);
        $scope_a->method('getIdentifier')->willReturn($identifier);
        $scope_b = $this->createMock(AuthenticationScope::class);
        $scope_b->method('getIdentifier')->willReturn($identifier);

        $this->dao->expects(self::once())->method('saveScopeKeysByAccessKeyID')->with(11, 'foo:bar');

        $this->saver->saveKeyScopes(11, $scope_a, $scope_b);
    }

    public function testAtLeastOneScopeMustBeGiven(): void
    {
        $this->expectException(NoValidAccessKeyScopeException::class);

        $this->saver->saveKeyScopes(14);
    }
}
