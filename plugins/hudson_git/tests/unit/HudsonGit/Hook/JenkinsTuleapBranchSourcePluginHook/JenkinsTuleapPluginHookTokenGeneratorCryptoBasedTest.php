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

final class JenkinsTuleapPluginHookTokenGeneratorCryptoBasedTest extends TestCase
{
    public function testGeneratesToken(): void
    {
        $key       = new EncryptionKey(new ConcealedString(str_repeat('a', SODIUM_CRYPTO_SECRETBOX_KEYBYTES)));
        $generator = new JenkinsTuleapPluginHookTokenGeneratorCryptoBased($key);

        $token = $generator->generateTriggerToken(new \DateTimeImmutable('@10'));
        self::assertEquals('tuleap-jenkins-plugin-trigger-10', SymmetricCrypto::decrypt(hex2bin($token->getString()), $key));
    }
}
