<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Cryptography;

class KeyFactoryTest extends \TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        \ForgeConfig::store();
    }

    public function tearDown()
    {
        \ForgeConfig::restore();
        parent::tearDown();
    }

    public function itGetsEncryptionKey()
    {
        $temporary_dir       = $this->getTmpDir();
        $encryption_key_file = $temporary_dir . '/conf/encryption_secret.key';
        mkdir(dirname($encryption_key_file));
        \ForgeConfig::set('sys_custom_dir', $temporary_dir);

        $key_factory   = new KeyFactory();
        $key_generated = $key_factory->getEncryptionKey();

        $this->assertFileExists($encryption_key_file);
        $file_read_only_permission = 0100400;
        $this->assertEqual(fileperms($encryption_key_file), $file_read_only_permission);

        $key_from_file = $key_factory->getEncryptionKey();

        $this->assertIsA($key_generated, 'Tuleap\\Cryptography\\Symmetric\\EncryptionKey');
        $this->assertIsA($key_from_file, 'Tuleap\\Cryptography\\Symmetric\\EncryptionKey');
        $this->assertEqual($key_generated->getRawKeyMaterial(), $key_from_file->getRawKeyMaterial());
    }

    public function itGetsEncryptionKeyFromFile()
    {
        $temporary_dir       = $this->getTmpDir();
        $encryption_key_file = $temporary_dir . '/conf/encryption_secret.key';
        mkdir(dirname($encryption_key_file));
        \ForgeConfig::set('sys_custom_dir', $temporary_dir);

        $raw_key_material = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);

        file_put_contents($encryption_key_file, sodium_bin2hex($raw_key_material));

        $key_factory = new KeyFactory();
        $key         = $key_factory->getEncryptionKey();

        $this->assertIsA($key, 'Tuleap\\Cryptography\\Symmetric\\EncryptionKey');
        $this->assertEqual($key->getRawKeyMaterial(), $raw_key_material);
    }

    public function itGeneratesDifferentEncryptionKeysEachTimeAGenerationIsNeeded()
    {
        $temporary_dir       = $this->getTmpDir();
        $encryption_key_file = $temporary_dir . '/conf/encryption_secret.key';
        mkdir(dirname($encryption_key_file));
        \ForgeConfig::set('sys_custom_dir', $temporary_dir);

        $key_factory     = new KeyFactory();
        $key_generated_1 = $key_factory->getEncryptionKey();

        unlink($encryption_key_file);

        $key_generated_2 = $key_factory->getEncryptionKey();

        $this->assertNotEqual($key_generated_1->getRawKeyMaterial(), $key_generated_2->getRawKeyMaterial());
    }
}
