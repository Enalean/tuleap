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

namespace Tuleap\Http\Server\Authentication;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @see https://tools.ietf.org/html/rfc7617
 * @see https://tools.ietf.org/html/rfc7235#section-4.2
 */
final class BasicAuthLoginExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @dataProvider dataProviderValidAuthorizationHeaders
     */
    public function testExtractsCredentialFromValidAuthorizationHeader(string $authorization_header_line, string $expected_password): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getHeaderLine')->with('Authorization')->willReturn($authorization_header_line);

        $extractor = new BasicAuthLoginExtractor();

        $login_credential = $extractor->extract($server_request);
        self::assertNotNull($login_credential);
        self::assertEquals('username', $login_credential->getUsername());
        self::assertEquals($expected_password, $login_credential->getPassword()->getString());
    }

    public static function dataProviderValidAuthorizationHeaders(): array
    {
        $expected_username            = 'username';
        $expected_password            = 'password';
        $expected_password_with_colon = 'pass:word';

        return [
            ['Basic ' . base64_encode($expected_username . ':' . $expected_password), $expected_password],
            ["Basic\t" . base64_encode($expected_username . ':' . $expected_password), $expected_password],
            ['Basic        ' . base64_encode($expected_username . ':' . $expected_password), $expected_password],
            ["Basic\t " . base64_encode($expected_username . ':' . $expected_password), $expected_password],
            ['basic ' . base64_encode($expected_username . ':' . $expected_password), $expected_password],
            ['Basic ' . base64_encode($expected_username . ':' . $expected_password_with_colon), $expected_password_with_colon],
        ];
    }

    /**
     * @dataProvider dataProviderNotValidAuthorizationHeaders
     */
    public function testNoCredentialsAreExtractedWhenTheAuthorizationHeaderLineIsNotValid(string $authorization_header_line): void
    {
        $server_request = $this->createMock(ServerRequestInterface::class);
        $server_request->method('getHeaderLine')->with('Authorization')->willReturn($authorization_header_line);

        $extractor = new BasicAuthLoginExtractor();

        $login_credential = $extractor->extract($server_request);
        self::assertNull($login_credential);
    }

    public static function dataProviderNotValidAuthorizationHeaders(): array
    {
        return [
            'No authorization header' => [''],
            'Not a basic auth scheme' => ['Bearer Foo'],
            'Wrongly encoded basic auth scheme' => ['Basic NotBase64'],
        ];
    }
}
