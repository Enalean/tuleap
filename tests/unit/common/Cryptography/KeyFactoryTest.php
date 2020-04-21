<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;

final class KeyFactoryTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testEncryptionKeyCanBeGenerated(): void
    {
        $temporary_dir       = vfsStream::setup()->url();
        $encryption_key_file = $temporary_dir . '/conf/encryption_secret.key';
        mkdir(dirname($encryption_key_file));
        \ForgeConfig::set('sys_custom_dir', $temporary_dir);

        $key_factory   = new KeyFactory();
        $key_generated = $key_factory->getEncryptionKey();

        $this->assertFileExists($encryption_key_file);
        $file_read_only_permission = 0100400;
        $this->assertEquals($file_read_only_permission, fileperms($encryption_key_file));

        $key_from_file = $key_factory->getEncryptionKey();

        $this->assertEquals($key_generated->getRawKeyMaterial(), $key_from_file->getRawKeyMaterial());
    }

    public function testEncryptionKeyCanBeRetrievedFromFile(): void
    {
        $temporary_dir       = vfsStream::setup()->url();
        $encryption_key_file = $temporary_dir . '/conf/encryption_secret.key';
        mkdir(dirname($encryption_key_file));
        \ForgeConfig::set('sys_custom_dir', $temporary_dir);

        $raw_key_material = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        file_put_contents($encryption_key_file, sodium_bin2hex($raw_key_material));

        $key_factory = new KeyFactory();
        $key         = $key_factory->getEncryptionKey();

        $this->assertEquals($raw_key_material, $key->getRawKeyMaterial());
    }

    public function testADifferentEncryptionKeyIsGeneratedEachTimeAGenerationIsNeeded(): void
    {
        $temporary_dir       = vfsStream::setup()->url();
        $encryption_key_file = $temporary_dir . '/conf/encryption_secret.key';
        mkdir(dirname($encryption_key_file));
        \ForgeConfig::set('sys_custom_dir', $temporary_dir);

        $key_factory     = new KeyFactory();
        $key_generated_1 = $key_factory->getEncryptionKey();

        unlink($encryption_key_file);

        $key_generated_2 = $key_factory->getEncryptionKey();

        $this->assertNotEquals($key_generated_1->getRawKeyMaterial(), $key_generated_2->getRawKeyMaterial());
    }
}
