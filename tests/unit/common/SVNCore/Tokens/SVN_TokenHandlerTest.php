<?php
/**
 * Copyright (c) Enalean 2022-Present. All rights reserved
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

namespace Tuleap\SVNCore\Tokens;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SVN_TokenHandlerTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    private const VALID_TOKEN = 'valid_token';
    // crypt(self::VALID_TOKEN, '$6$rounds=20000$16$')
    private const VALID_TOKEN_PASSWORD_HASH = '$6$rounds=20000$bf679aec72b28967$zt8Hv8kaQlRekiSvswUGQDvBl8Z8HR45CaFlaJUogQfxVdWzp.Y4JAT4h31zOgurbCT.p9tVmrFCaSNLsapIe1';

    /**
     * @var \SVN_TokenDao&\PHPUnit\Framework\MockObject\Stub
     */
    private $token_dao;
    private \SVN_TokenHandler $token_handler;

    #[\Override]
    protected function setUp(): void
    {
        $password_handler = new \StandardPasswordHandler();
        $this->token_dao  = $this->createStub(\SVN_TokenDao::class);
        $this->token_dao->method('getSVNTokensForUser')->willReturn([
            ['id' => 100, 'token' => 'some_token', 'generated_date' => 1, 'last_usage' => 2, 'last_ip' => '2001:db8::3', 'comment' => ''],
            ['id' => 200, 'token' => self::VALID_TOKEN_PASSWORD_HASH, 'generated_date' => 1, 'last_usage' => 2, 'last_ip' => '2001:db8::3', 'comment' => ''],
        ]);
        $this->token_handler = new \SVN_TokenHandler($this->token_dao, $password_handler);
    }

    public function testAcceptsValidToken(): void
    {
        $this->token_dao->method('updateTokenLastUsage');

        $is_valid = $this->token_handler->isTokenValid(
            UserTestBuilder::anActiveUser()->build(),
            new ConcealedString(self::VALID_TOKEN),
            ''
        );

        self::assertTrue($is_valid);
    }

    public function testRejectsInvalidToken(): void
    {
        $is_valid = $this->token_handler->isTokenValid(
            UserTestBuilder::anActiveUser()->build(),
            new ConcealedString('incorrect_token'),
            ''
        );

        self::assertFalse($is_valid);
    }
}
