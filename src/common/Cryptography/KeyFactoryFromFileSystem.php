<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\Cryptography;

use Psr\Log\LoggerInterface;
use Tuleap\Cryptography\Exception\CannotPerformIOOperationException;
use Tuleap\Cryptography\Symmetric\EncryptionKey;
use Tuleap\File\FileWriter;

final readonly class KeyFactoryFromFileSystem implements KeyFactory
{
    /**
     * @throws CannotPerformIOOperationException
     */
    #[\Override]
    public function getEncryptionKey(): EncryptionKey
    {
        $reflection_class = new \ReflectionClass(EncryptionKey::class);
        return $reflection_class->newLazyProxy(
            function (): EncryptionKey {
                return new EncryptionKey($this->getKeyMaterial());
            }
        );
    }

    private function getKeyMaterial(): ConcealedString
    {
        $encryption_key_file_path = $this->initAndGetEncryptionKeyPath();
        try {
            $file_data = \Psl\File\read($encryption_key_file_path);
        } catch (\RuntimeException $exception) {
            throw new CannotPerformIOOperationException("Cannot read the encryption key $encryption_key_file_path", $exception);
        }

        $file_data_hex = sodium_hex2bin($file_data);
        \sodium_memzero($file_data);

        $key_material = new ConcealedString($file_data_hex);

        \sodium_memzero($file_data_hex);

        return $key_material;
    }

    /**
     * @return non-empty-string
     */
    private function initAndGetEncryptionKeyPath(): string
    {
        /** @var non-empty-string $encryption_key_file_path */
        $encryption_key_file_path = \ForgeConfig::get('sys_custom_dir') . '/conf/encryption_secret.key';
        if (! \file_exists($encryption_key_file_path)) {
            $encryption_key = $this->generateEncryptionKey();
            $this->saveKeyFile($encryption_key, $encryption_key_file_path);
        }
        return $encryption_key_file_path;
    }

    /**
     * @throws Exception\InvalidKeyException
     * @throws \SodiumException
     */
    private function generateEncryptionKey(): EncryptionKey
    {
        $raw_encryption_key = \sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
        $key_data           = new ConcealedString($raw_encryption_key);
        \sodium_memzero($raw_encryption_key);

        return new EncryptionKey($key_data);
    }

    /**
     * @param non-empty-string $file_path
     */
    private function saveKeyFile(Key $key, string $file_path): void
    {
        $raw_key_material     = $key->getRawKeyMaterial();
        $raw_key_material_hex = \sodium_bin2hex($raw_key_material);
        \sodium_memzero($raw_key_material);

        try {
            FileWriter::writeFile($file_path, $raw_key_material_hex, 0400);
        } catch (\RuntimeException $exception) {
            throw new CannotPerformIOOperationException($exception->getMessage(), $exception);
        } finally {
            \sodium_memzero($raw_key_material_hex);
        }
    }

    #[\Override]
    public function restoreOwnership(LoggerInterface $logger): void
    {
        $key_path               = $this->initAndGetEncryptionKeyPath();
        $application_user_login = \ForgeConfig::getApplicationUserLogin();
        $logger->debug(sprintf('Restore ownership on %s', $key_path));
        if (! chown($key_path, $application_user_login)) {
            $logger->warning(sprintf('Impossible to chown %s to %s', $key_path, $application_user_login));
        }
        if (! chgrp($key_path, $application_user_login)) {
            $logger->warning(sprintf('Impossible to chgrp %s to %s', $key_path, $application_user_login));
        }
    }
}
