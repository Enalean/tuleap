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
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;

final class DocumentServerDaoTest extends TestIntegrationTestCase
{
    private DocumentServerDao $dao;
    private DocumentServerKeyEncryption $encryption;
    private int $project_a_id;
    private int $project_b_id;
    private \ParagonIE\EasyDB\EasyDB $db;

    protected function setUp(): void
    {
        $root = vfsStream::setup()->url();
        mkdir($root . '/conf/');
        \ForgeConfig::set('sys_custom_dir', $root);

        $this->encryption = new DocumentServerKeyEncryption(new KeyFactory());
        $this->dao        = new DocumentServerDao($this->encryption);

        $this->db           = DBFactory::getMainTuleapDBConnection()->getDB();
        $this->project_a_id = (int) $this->db->insertReturnId(
            'groups',
            ['status' => 'A', 'unix_group_name' => 'project_a', 'group_name' => 'Project A']
        );
        $this->project_b_id = (int) $this->db->insertReturnId(
            'groups',
            ['status' => 'A', 'unix_group_name' => 'project_b', 'group_name' => 'Project B']
        );
    }

    public function testDocumentServerStorage(): void
    {
        // Create
        $this->dao->create('https://example.com', new ConcealedString('very_secret'));

        $servers = $this->dao->retrieveAll();
        self::assertCount(1, $servers);
        $server = $this->dao->retrieveById($servers[0]->id);
        self::assertEquals([0], $this->getServerProjectRestrictions());

        $this->dao->create('https://example.com/1', new ConcealedString('much_secret'));

        // Retrieve
        self::assertEquals('https://example.com', $server->url);
        self::assertTrue($this->decrypt($server->encrypted_secret_key)->isIdenticalTo(new ConcealedString('very_secret')));

        $servers = $this->dao->retrieveAll();
        self::assertCount(2, $servers);
        self::assertEquals('https://example.com', $servers[0]->url);
        self::assertTrue($this->decrypt($servers[0]->encrypted_secret_key)->isIdenticalTo(new ConcealedString('very_secret')));
        self::assertEquals('https://example.com/1', $servers[1]->url);
        self::assertTrue($this->decrypt($servers[1]->encrypted_secret_key)->isIdenticalTo(new ConcealedString('much_secret')));
        self::assertEquals([1, 1], $this->getServerProjectRestrictions());

        // Update
        $this->dao->update($servers[0]->id, $servers[0]->url, new ConcealedString('new_secret'));
        $servers = $this->dao->retrieveAll();
        self::assertTrue($this->decrypt($servers[0]->encrypted_secret_key)->isIdenticalTo(new ConcealedString('new_secret')));
        self::assertTrue($this->decrypt($servers[1]->encrypted_secret_key)->isIdenticalTo(new ConcealedString('much_secret')));

        // Delete
        $this->dao->delete($servers[0]->id);
        $servers = $this->dao->retrieveAll();
        self::assertCount(1, $servers);
        self::assertEquals('https://example.com/1', $servers[0]->url);
        self::assertTrue($this->decrypt($servers[0]->encrypted_secret_key)->isIdenticalTo(new ConcealedString('much_secret')));
    }

    public function testServerRestriction(): void
    {
         $this->dao->create('https://example.com', new ConcealedString('very_secret'));

         $servers = $this->dao->retrieveAll();
         self::assertCount(1, $servers);
         $server = $this->dao->retrieveById($servers[0]->id);
         self::assertFalse($server->is_project_restricted);

         $this->dao->restrict($server->id, [$this->project_a_id, $this->project_b_id]);
         $server = $this->dao->retrieveById($server->id);
         self::assertTrue($server->is_project_restricted);
         $server_names = array_map(static fn (RestrictedProject $project): string => $project->name, $server->project_restrictions);
         self::assertContains('project_a', $server_names);
         self::assertContains('project_b', $server_names);

        $this->dao->restrict($server->id, []);
        $server = $this->dao->retrieveById($server->id);
        self::assertTrue($server->is_project_restricted);
        self::assertEmpty($server->project_restrictions);

        $this->dao->create('https://example.com/another', new ConcealedString('another_secret'));
        $servers = $this->dao->retrieveAll();
        $this->dao->restrict($servers[0]->id, [$this->project_a_id]);
        $this->dao->restrict($servers[1]->id, [$this->project_a_id]);
        $server_a = $this->dao->retrieveById($servers[0]->id);
        $server_b = $this->dao->retrieveById($servers[1]->id);
        self::assertTrue($server_a->is_project_restricted);
        self::assertTrue($server_b->is_project_restricted);
        self::assertEmpty($server_a->project_restrictions);
        self::assertCount(1, $server_b->project_restrictions);

        $this->dao->delete($server_b->id);
        $are_projects_restrictions_deleted_after_server_deletion =
            $this->db->single(
                'SELECT COUNT(*) FROM plugin_onlyoffice_document_server_project_restriction WHERE server_id = ?',
                [$server_b->id]
            ) === 0;

        self::assertTrue($are_projects_restrictions_deleted_after_server_deletion);
    }

    public function testUnrestriction(): void
    {
        $this->dao->create('https://example.com', new ConcealedString('very_secret'));
        $this->dao->create('https://example.com/another', new ConcealedString('another_secret'));

        $servers = $this->dao->retrieveAll();
        $this->dao->restrict($servers[0]->id, [$this->project_a_id, $this->project_b_id]);
        $server_a = $this->dao->retrieveById($servers[0]->id);
        $server_b = $this->dao->retrieveById($servers[1]->id);

        self::assertTrue($server_a->is_project_restricted);
        self::assertTrue($server_b->is_project_restricted);

        try {
            $this->dao->unrestrict($server_a->id);
            self::fail("Cannot unrestrict when there are more than one server");
        } catch (TooManyServersException) {
        }

        $this->dao->delete($server_b->id);
        $this->dao->unrestrict($server_a->id);

        $server_a = $this->dao->retrieveById($server_a->id);
        self::assertFalse($server_a->is_project_restricted);
        self::assertEmpty($server_a->project_restrictions);
    }

    private function decrypt(ConcealedString $secret): ConcealedString
    {
        return $this->encryption->decryptValue($secret->getString());
    }

    private function getServerProjectRestrictions(): array
    {
        return $this->db->col('SELECT is_project_restricted FROM plugin_onlyoffice_document_server');
    }
}
