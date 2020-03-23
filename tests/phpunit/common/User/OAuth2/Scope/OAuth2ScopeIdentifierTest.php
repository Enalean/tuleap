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

namespace Tuleap\User\OAuth2\Scope;

use PHPUnit\Framework\TestCase;

final class OAuth2ScopeIdentifierTest extends TestCase
{
    /**
     * @dataProvider dataProviderValidIdentifierKey
     */
    public function testIdentifierKeyIsNotModified(string $identifier_key): void
    {
        $identifier     = OAuth2ScopeIdentifier::fromIdentifierKey($identifier_key);

        $this->assertEquals($identifier_key, $identifier->toString());
    }

    public function dataProviderValidIdentifierKey(): array
    {
        return [
            ['profile:foo'],
            ['profile'],
            ['profile_foo'],
        ];
    }

    /**
     * @dataProvider dataProviderInvalidIdentifierKey
     */
    public function testIdentifierKeyNotCorrectlyFormattedIsRejected(string $invalid_identifier_key): void
    {
        $this->expectException(InvalidOAuth2ScopeIdentifierException::class);
        OAuth2ScopeIdentifier::fromIdentifierKey($invalid_identifier_key);
    }

    public function dataProviderInvalidIdentifierKey(): array
    {
        return [
            'Space (force encoding for RFC6750 section 3)' => ['identifier space'],
            'Majuscule'                                    => ['PROFILE'],
            '"Special" char'                               => ['#"aaaa'],
            'Empty identifier key'                         => [''],
        ];
    }
}
