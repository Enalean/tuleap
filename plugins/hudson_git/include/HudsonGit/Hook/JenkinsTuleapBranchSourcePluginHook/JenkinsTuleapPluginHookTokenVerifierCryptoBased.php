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

use DateTimeImmutable;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\Exception\InvalidCiphertextException;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\Cryptography\Symmetric\SymmetricCrypto;

final class JenkinsTuleapPluginHookTokenVerifierCryptoBased implements JenkinsTuleapPluginHookTokenVerifier
{
    private const ACCEPTABLE_DELAY_SECS = 20;

    public function __construct(private EncryptionKey $key)
    {
    }


    public function isTriggerTokenValid(ConcealedString $trigger_token, DateTimeImmutable $now): bool
    {
        try {
            $trigger_token_bin = sodium_hex2bin($trigger_token->getString());
        } catch (\SodiumException $e) {
            return false;
        }

        try {
            $cleartext_token = SymmetricCrypto::decrypt($trigger_token_bin, $this->key)->getString();
        } catch (InvalidCiphertextException | \SodiumException $e) {
            return false;
        }

        if (! str_starts_with($cleartext_token, JenkinsTuleapPluginHookTokenGeneratorCryptoBased::PREFIX)) {
            return false;
        }

        $timestamp_token = substr($cleartext_token, strlen(JenkinsTuleapPluginHookTokenGeneratorCryptoBased::PREFIX));
        if (! is_numeric($timestamp_token)) {
            return false;
        }

        return abs($now->getTimestamp() - (int) $timestamp_token) <= self::ACCEPTABLE_DELAY_SECS;
    }
}
