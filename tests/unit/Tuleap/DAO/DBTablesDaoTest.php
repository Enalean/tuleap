<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\DAO;

use org\bovigo\vfs\vfsStream;
use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DBConnection;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DBTablesDaoTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var EasyDB&\PHPUnit\Framework\MockObject\MockObject
     */
    private $easy_db;
    /**
     * @var DBTablesDao
     */
    private $db_tables_dao;

    protected function setUp(): void
    {
        $db_connection = $this->createMock(DBConnection::class);
        $this->easy_db = $this->createMock(EasyDB::class);
        $db_connection->method('getDB')->willReturn($this->easy_db);
        $this->db_tables_dao = new DBTablesDao($db_connection);
    }

    public function testExecQueriesInFile(): void
    {
        $queries = <<<EOF
        CREATE TABLE A (id INT);

        CREATE TABLE B (id INT);

        INSERT INTO B SET \
        id = 1;
        EOF;

        $file_path = vfsStream::setup()->url() . '/install.sql';
        file_put_contents($file_path, $queries);

        $this->easy_db->method('run')->willReturnCallback(
            fn (string $sql): array => match ($sql) {
                'CREATE TABLE A (id INT);',
                'CREATE TABLE B (id INT);',
                'INSERT INTO B SET id = 1;' => []
            }
        );

        $this->expectNotToPerformAssertions();

        $this->db_tables_dao->updateFromFile($file_path);
    }

    public function testThrowsWhenFileCannotBeRead(): void
    {
        $do_not_exist_path = vfsStream::setup()->url() . '/do_not_exist.sql';

        $this->expectException(\RuntimeException::class);
        $this->db_tables_dao->updateFromFile($do_not_exist_path);
    }
}
