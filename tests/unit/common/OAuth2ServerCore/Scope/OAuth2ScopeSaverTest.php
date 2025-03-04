<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2ServerCore\Scope;

use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\User\OAuth2\Scope\OAuth2ScopeIdentifier;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2ScopeSaverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&OAuth2ScopeIdentifierSaverDAO
     */
    private $dao;

    /**
     * @var OAuth2ScopeSaver
     */
    private $saver;

    protected function setUp(): void
    {
        $this->dao = $this->createMock(OAuth2ScopeIdentifierSaverDAO::class);

        $this->saver = new OAuth2ScopeSaver($this->dao);
    }

    public function testScopesAreSaved(): void
    {
        $identifier_a = OAuth2ScopeIdentifier::fromIdentifierKey('foobar');
        $identifier_b = OAuth2ScopeIdentifier::fromIdentifierKey('barbaz');

        $scope_a = $this->createMock(AuthenticationScope::class);
        $scope_a->method('getIdentifier')->willReturn($identifier_a);
        $scope_b = $this->createMock(AuthenticationScope::class);
        $scope_b->method('getIdentifier')->willReturn($identifier_b);

        $this->dao->expects(self::once())->method('saveScopeKeysByID')->with(12, 'foobar', 'barbaz');

        $this->saver->saveScopes(12, [$scope_a, $scope_b]);
    }
}
