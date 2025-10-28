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
use Tuleap\Cryptography\SymmetricLegacy2025\EncryptionKey as Legacy2025EncryptionKey;

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

    /**
     * @throws CannotPerformIOOperationException
     */
    #[\Override]
    public function getLegacy2025EncryptionKey(): Legacy2025EncryptionKey
    {
        $reflection_class = new \ReflectionClass(Legacy2025EncryptionKey::class);
        return $reflection_class->newLazyProxy(
            function (): Legacy2025EncryptionKey {
                return new Legacy2025EncryptionKey($this->getKeyMaterial());
            }
        );
    }

    private function getKeyMaterial(): ConcealedString
    {
        $encryption_key_file_path = $this->initAndGetEncryptionKeyPath();
        $file_data                = \file_get_contents($encryption_key_file_path);
        if ($file_data === false) {
            throw new CannotPerformIOOperationException("Cannot read the encryption key $encryption_key_file_path");
        }

        $file_data_hex = sodium_hex2bin($file_data);
        \sodium_memzero($file_data);

        $key_material = new ConcealedString($file_data_hex);

        \sodium_memzero($file_data_hex);

        return $key_material;
    }

    private function initAndGetEncryptionKeyPath(): string
    {
        $encryption_key_file_path = $this->getKeyPath();
        if (! \file_exists($encryption_key_file_path)) {
            $encryption_key = $this->generateEncryptionKey();
            $this->saveKeyFile($encryption_key, $encryption_key_file_path);
        }
        return $encryption_key_file_path;
    }

    private function getKeyPath(): string
    {
        return \ForgeConfig::get('sys_custom_dir') . '/conf/encryption_secret.key';
    }

    /**
     * @throws Exception\InvalidKeyException
     * @throws \SodiumException
     */
    private function generateEncryptionKey(): \Tuleap\Cryptography\SymmetricLegacy2025\EncryptionKey
    {
        $raw_encryption_key = \sodium_crypto_aead_xchacha20poly1305_ietf_keygen();
        $key_data           = new ConcealedString($raw_encryption_key);
        \sodium_memzero($raw_encryption_key);

        return new Legacy2025EncryptionKey($key_data);
    }

    private function saveKeyFile(Key $key, string $file_path): void
    {
        $is_success = \touch($file_path);
        if (! $is_success) {
            throw new CannotPerformIOOperationException("Cannot create the key file $file_path");
        }
        $is_success = \chmod($file_path, 0600);
        if (! $is_success) {
            \unlink($file_path);
            throw new CannotPerformIOOperationException("Cannot restrict rights of the key file $file_path to u:rw");
        }

        $raw_key_material     = $key->getRawKeyMaterial();
        $raw_key_material_hex = \sodium_bin2hex($raw_key_material);
        \sodium_memzero($raw_key_material);

        $written_size = \file_put_contents(
            $file_path,
            $raw_key_material_hex
        );

        \sodium_memzero($raw_key_material_hex);

        if ($written_size === false) {
            \unlink($file_path);
            throw new CannotPerformIOOperationException("Cannot write to the key file $file_path");
        }
        $is_success = \chmod($file_path, 0400);
        if (! $is_success) {
            \unlink($file_path);
            throw new CannotPerformIOOperationException("Cannot restrict rights of the key file $file_path to u:r");
        }
    }

    #[\Override]
    public function restoreOwnership(LoggerInterface $logger): void
    {
        $logger->debug(sprintf('Restore ownership on %s', $this->getKeyPath()));
        if (! chown($this->getKeyPath(), \ForgeConfig::getApplicationUserLogin())) {
            $logger->warning(sprintf('Impossible to chown %s to %s', $this->getKeyPath(), \ForgeConfig::getApplicationUserLogin()));
        }
        if (! chgrp($this->getKeyPath(), \ForgeConfig::getApplicationUserLogin())) {
            $logger->warning(sprintf('Impossible to chgrp %s to %s', $this->getKeyPath(), \ForgeConfig::getApplicationUserLogin()));
        }
    }
}
