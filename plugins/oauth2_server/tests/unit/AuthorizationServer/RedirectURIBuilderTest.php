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

namespace Tuleap\OAuth2Server\AuthorizationServer;

use PHPUnit\Framework\TestCase;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Http\HTTPFactoryBuilder;

final class RedirectURIBuilderTest extends TestCase
{
    /**
     * @var RedirectURIBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new RedirectURIBuilder(HTTPFactoryBuilder::URIFactory());
    }

    /**
     * @dataProvider dataProviderValidErrorURIs
     */
    public function testBuildErrorURI(array $parameters, string $expected_result_uri): void
    {
        $result = $this->builder->buildErrorURI(...$parameters);
        $this->assertSame((string) $result, $expected_result_uri);
    }

    public function dataProviderValidErrorURIs(): array
    {
        return [
            'Base redirect URI has no query'     => [
                ['https://example.com/redirect', null, 'error_type'],
                'https://example.com/redirect?error=error_type',
            ],
            'Base redirect URI has a query'      => [
                ['https://example.com/redirect?key=value', null, 'error_type'],
                'https://example.com/redirect?key=value&error=error_type',
            ],
            'Base redirect URI has no path'      => [
                ['https://example.com?key=value', null, 'error_type'],
                'https://example.com?key=value&error=error_type',
            ],
            'Base redirect URI has a port'       => [
                ['https://example.com:8080/redirect?key=value', null, 'error_type'],
                'https://example.com:8080/redirect?key=value&error=error_type',
            ],
            'State parameter is kept unmodified' => [
                ['https://example.com/redirect?key=value', 'state_value', 'error_type'],
                'https://example.com/redirect?key=value&state=state_value&error=error_type',
            ]
        ];
    }

    /**
     * @dataProvider dataProviderValidSuccessURIs
     */
    public function testBuildSuccessURI(array $parameters, string $expected_result_uri): void
    {
        $result = $this->builder->buildSuccessURI(...$parameters);
        $this->assertSame((string) $result, $expected_result_uri);
    }

    public function dataProviderValidSuccessURIs(): array
    {
        return [
            'Base redirect URI has no query' => [
                ['https://example.com/redirect', null, new ConcealedString('auth_code')],
                'https://example.com/redirect?code=auth_code'
            ],
            'Base redirect URI has a query' => [
                ['https://example.com/redirect?key=value', null, new ConcealedString('auth_code')],
                'https://example.com/redirect?key=value&code=auth_code'
            ],
            'Base redirect URI has no path' => [
                ['https://example.com?key=value', null, new ConcealedString('auth_code')],
                'https://example.com?key=value&code=auth_code'
            ],
            'Base redirect URI has a port' => [
                ['https://example.com:8080/redirect?key=value', null, new ConcealedString('auth_code')],
                'https://example.com:8080/redirect?key=value&code=auth_code'
            ],
            'State parameter is kept unmodified' => [
                ['https://example.com/redirect?key=value', 'state_value', new ConcealedString('auth_code')],
                'https://example.com/redirect?key=value&state=state_value&code=auth_code'
            ]
        ];
    }
}
