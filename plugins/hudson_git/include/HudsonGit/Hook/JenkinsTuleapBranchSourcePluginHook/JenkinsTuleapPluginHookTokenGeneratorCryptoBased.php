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

final class JenkinsTuleapPluginHookTokenGeneratorCryptoBased implements JenkinsTuleapPluginHookTokenGenerator
{
    public const PREFIX = 'tuleap-jenkins-plugin-trigger-';

    public function __construct(private EncryptionKey $key)
    {
    }

    public function generateTriggerToken(\DateTimeImmutable $now): ConcealedString
    {
        return new ConcealedString(sodium_bin2hex(SymmetricCrypto::encrypt(new ConcealedString(self::PREFIX . $now->getTimestamp()), $this->key)));
    }
}
