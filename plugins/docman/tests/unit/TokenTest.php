<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman;

use Codendi_Request;
use Docman_Token;
use Docman_TokenDao;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TokenTest extends TestCase
{
    public function testGenerateRandomToken(): void
    {
        $dao = $this->createMock(Docman_TokenDao::class);
        $dao->method('create');
        $http = new Codendi_Request(['bc' => false]);

        $t1 = $this->mockToken($dao, 'https://example.com/?id=1&action=show', '123', $http);
        $t2 = $this->mockToken($dao, 'https://example.com/?id=1&action=show', '123', $http);
        $t3 = $this->mockToken($dao, 'https://example.com/?id=2&action=show', '123', $http);
        $t4 = $this->mockToken($dao, 'https://example.com/?id=1&action=show', '987', $http);

        self::assertNotEquals($t1->getToken(), $t2->getToken(), 'Same users, same referers, different tokens');
        self::assertNotEquals($t1->getToken(), $t3->getToken(), 'Different referers, different tokens');
        self::assertNotEquals($t1->getToken(), $t4->getToken(), 'Different users, different tokens');
    }

    public function testNullToken(): void
    {
        $dao = $this->createMock(Docman_TokenDao::class);
        $dao->method('create');
        $http = new Codendi_Request(['bc' => false]);

        $t1 = $this->mockToken($dao, 'https://example.com/?', '123', $http);
        self::assertNull($t1->getToken(), 'Without referer, we should have a null token');

        $t2 = $this->mockToken($dao, 'https://example.com/?id=1&action=show', '123', $http);
        self::assertNotNull($t2->getToken());

        $t3 = $this->mockToken($dao, 'https://example.com/?id=1&action=show', null, $http);
        self::assertNull($t3->getToken(), 'With anonymous user, we should have a null token');
    }

    public function testStorage(): void
    {
        $user_id = '123';
        $referer = 'https://example.com/?id=1&action=show';

        $dao = $this->createMock(Docman_TokenDao::class);
        $dao->expects(self::once())->method('create')->with($user_id, self::anything(), $referer);
        $http = new Codendi_Request(['bc' => false]);

        $this->mockToken($dao, $referer, $user_id, $http);
    }

    public function testInvalidReferer(): void
    {
        $dao = $this->createMock(Docman_TokenDao::class);
        $dao->method('create');
        $http = new Codendi_Request(['bc' => false]);
        foreach (['aaaa', '?action=foo', '?action=details&section=notification'] as $referer) {
            $t = $this->mockToken($dao, "https://exmaple.com/$referer", '123', $http);
            self::assertNull($t->getToken(), 'Without valid referer, we should have a null token');
        }
        foreach (['?action=show', '?id=1&action=show', '?action=details'] as $referer) {
            $t = $this->mockToken($dao, "https://exmaple.com/$referer", '123', $http);
            self::assertNotNull($t->getToken(), "With valid referer, we should'nt have a null token");
        }
    }

    private function mockToken(
        Docman_TokenDao $dao,
        string $referer,
        ?string $user_id,
        Codendi_Request $request,
    ): Docman_Token&MockObject {
        $token = $this->createPartialMock(Docman_Token::class, [
            '_getDao',
            '_getReferer',
            '_getCurrentUserId',
            '_getHTTPRequest',
        ]);
        $token->method('_getDao')->willReturn($dao);
        $token->method('_getReferer')->willReturn($referer);
        $token->method('_getCurrentUserId')->willReturn($user_id);
        $token->method('_getHTTPRequest')->willReturn($request);
        $token->__construct();

        return $token;
    }
}
