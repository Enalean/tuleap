<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication\UserInfo;

use Psr\Http\Message\ResponseInterface;

final class UserInfoResponseTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testContentTypeNotAnnouncedAsJSONIsRejected(): void
    {
        $http_response = $this->createMock(ResponseInterface::class);
        $http_response->method('getHeaderLine')->willReturn('application/jwt');

        $this->expectException(NotSupportedContentTypeUserInfoResponseException::class);

        UserInfoResponse::buildFromHTTPResponse($http_response);
    }

    public function testNotValidJSONISRejected(): void
    {
        $http_response = $this->createMock(ResponseInterface::class);
        $http_response->method('getHeaderLine')->willReturn('application/json');
        $http_response->method('getBody')->willReturn('{NotJSONValid');

        $this->expectException(IncorrectlyFormattedUserInfoResponseException::class);

        UserInfoResponse::buildFromHTTPResponse($http_response);
    }

    public function testUserInfoResponseIsParsed(): void
    {
        $claims = [
            'sub'                => '248289761001',
            'name'               => 'Jane Doe',
            'given_name'         => 'Jane',
            'family_name'        => 'Doe',
            'preferred_username' => 'j.doe',
            'email'              => 'janedoe@example.com',
        ];
        foreach (['application/json', 'application/json; charset=UTF-8'] as $content_type) {
            $http_response = $this->createMock(ResponseInterface::class);
            $http_response->method('getHeaderLine')->willReturn($content_type);
            $http_response->method('getBody')->willReturn(json_encode($claims));

            $user_info_response = UserInfoResponse::buildFromHTTPResponse($http_response);
            self::assertSame($claims, $user_info_response->getClaims());
        }
    }

    public function testAnEmptyUserInfoResponseCanBeProvided(): void
    {
        $user_info_response = UserInfoResponse::buildEmptyUserInfoResponse();
        self::assertEmpty($user_info_response->getClaims());
    }
}
