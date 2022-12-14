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

namespace Tuleap\OnlyOffice\DocumentServer;

use org\bovigo\vfs\vfsStream;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\Cryptography\KeyFactory;
use Tuleap\DB\DBFactory;

final class DocumentServerDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private DocumentServerDao $dao;
    private DocumentServerKeyEncryption $encryption;
    private mixed $old_sys_custom_dir;

    protected function setUp(): void
    {
        $this->old_sys_custom_dir = \ForgeConfig::get('sys_custom_dir');
        $root                     = vfsStream::setup()->url();
        mkdir($root . '/conf/');
        \ForgeConfig::set('sys_custom_dir', $root);

        $this->encryption = new DocumentServerKeyEncryption(new KeyFactory());
        $this->dao        = new DocumentServerDao($this->encryption);
    }

    protected function tearDown(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();

        $db->run('DELETE FROM plugin_onlyoffice_document_server');

        \ForgeConfig::set('sys_custom_dir', $this->old_sys_custom_dir);
    }

    public function testDocumentServerStorage(): void
    {
        // Create
        $this->dao->create('https://example.com', new ConcealedString('very_secret'));
        $this->dao->create('https://example.com/1', new ConcealedString('much_secret'));

        // Retrieve
        $servers = $this->dao->retrieveAll();
        self::assertCount(2, $servers);
        self::assertEquals('https://example.com', $servers[0]->url);
        self::assertEquals('very_secret', $this->decrypt($servers[0]->encrypted_secret_key));
        self::assertEquals('https://example.com/1', $servers[1]->url);
        self::assertEquals('much_secret', $this->decrypt($servers[1]->encrypted_secret_key));

        // Update
        $this->dao->update($servers[0]->id, $servers[0]->url, new ConcealedString('new_secret'));
        $servers = $this->dao->retrieveAll();
        self::assertEquals('new_secret', $this->decrypt($servers[0]->encrypted_secret_key));
        self::assertEquals('much_secret', $this->decrypt($servers[1]->encrypted_secret_key));

        // Delete
        $this->dao->delete($servers[0]->id);
        $servers = $this->dao->retrieveAll();
        self::assertCount(1, $servers);
        self::assertEquals('https://example.com/1', $servers[0]->url);
        self::assertEquals('much_secret', $this->decrypt($servers[0]->encrypted_secret_key));
    }

    private function decrypt(ConcealedString $secret): string
    {
        return $this->encryption->decryptValue($secret->getString())->getString();
    }
}
