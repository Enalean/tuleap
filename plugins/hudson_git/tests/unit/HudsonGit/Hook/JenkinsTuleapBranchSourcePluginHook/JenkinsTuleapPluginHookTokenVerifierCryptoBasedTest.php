<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Hook\JenkinsTuleapBranchSourcePluginHook;

use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;
use Tuleap\Test\PHPUnit\TestCase;

final class JenkinsTuleapPluginHookTokenVerifierCryptoBasedTest extends TestCase
{
    public function testVerifiesValidToken(): void
    {
        $key      = new EncryptionKey(new ConcealedString(str_repeat('a', 32)));
        $verifier = self::buildVerifier($key);

        $valid_token = bin2hex(SymmetricCrypto::encrypt(new ConcealedString('tuleap-jenkins-plugin-trigger-60'), $key));

        self::assertTrue($verifier->isTriggerTokenValid(new ConcealedString($valid_token), new \DateTimeImmutable('@70')));
    }

    public function testRejectsTokenIncorrectlyEncoded(): void
    {
        $verifier = self::buildVerifier(new EncryptionKey(new ConcealedString(str_repeat('a', 32))));

        self::assertFalse($verifier->isTriggerTokenValid(new ConcealedString('foo'), new \DateTimeImmutable('@70')));
    }

    public function testRejectsTokenIncorrectlyAuthenticated(): void
    {
        $verifier = self::buildVerifier(new EncryptionKey(new ConcealedString(str_repeat('a', 32))));

        $invalid_token = bin2hex(SymmetricCrypto::encrypt(new ConcealedString('tuleap-jenkins-plugin-trigger-60'), new EncryptionKey(new ConcealedString(str_repeat('b', 32)))));

        self::assertFalse($verifier->isTriggerTokenValid(new ConcealedString($invalid_token), new \DateTimeImmutable('@70')));
    }

    public function testRejectsEmptyToken(): void
    {
        $key      = new EncryptionKey(new ConcealedString(str_repeat('a', 32)));
        $verifier = self::buildVerifier($key);

        self::assertFalse($verifier->isTriggerTokenValid(new ConcealedString(''), new \DateTimeImmutable('@70')));
    }

    public function testRejectsTokenWithoutPrefix(): void
    {
        $key      = new EncryptionKey(new ConcealedString(str_repeat('a', 32)));
        $verifier = self::buildVerifier($key);

        $invalid_token = bin2hex(SymmetricCrypto::encrypt(new ConcealedString('60'), $key));

        self::assertFalse($verifier->isTriggerTokenValid(new ConcealedString($invalid_token), new \DateTimeImmutable('@70')));
    }

    public function testRejectsTokenWithoutTimestamp(): void
    {
        $key      = new EncryptionKey(new ConcealedString(str_repeat('a', 32)));
        $verifier = self::buildVerifier($key);

        $invalid_token = bin2hex(SymmetricCrypto::encrypt(new ConcealedString('tuleap-jenkins-plugin-trigger-aa'), $key));

        self::assertFalse($verifier->isTriggerTokenValid(new ConcealedString($invalid_token), new \DateTimeImmutable('@70')));
    }

    public function testRejectsExpiredTokenWithoutTimestamp(): void
    {
        $key      = new EncryptionKey(new ConcealedString(str_repeat('a', 32)));
        $verifier = self::buildVerifier($key);

        $expired_token = bin2hex(SymmetricCrypto::encrypt(new ConcealedString('tuleap-jenkins-plugin-trigger-10'), $key));

        self::assertFalse($verifier->isTriggerTokenValid(new ConcealedString($expired_token), new \DateTimeImmutable('@70')));
    }

    private static function buildVerifier(EncryptionKey $key): JenkinsTuleapPluginHookTokenVerifierCryptoBased
    {
        return new JenkinsTuleapPluginHookTokenVerifierCryptoBased($key);
    }
}
