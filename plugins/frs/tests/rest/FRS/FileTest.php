<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
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

namespace Tuleap\FRS\Tests\REST;

use RestBase;

class FileTest extends RestBase
{
    const PROJECT_NAME = 'frs-test';

    private $project_id;

    public function setUp()
    {
        parent::setUp();
        $this->project_id = $this->getProjectId(self::PROJECT_NAME);
    }

    public function testOPTIONSFile()
    {
        $response = $this->getResponse($this->client->options('frs_files/1'));
        $this->assertEquals(array('OPTIONS', 'GET'), $response->getHeader('Allow')->normalize()->toArray());
    }

    public function testGETFile()
    {
        $file = $this->getResponse($this->client->get('frs_files/1'))->json();

        $this->assertEquals(1, $file['id']);
        $this->assertEquals('BooksAuthors.txt', $file['name']);
        $this->assertEquals('x86_64', $file['arch']);
        $this->assertEquals('text', $file['type']);
        $this->assertEquals(72, $file['file_size']);
        $this->assertEquals('2015-12-03T16:46:00+01:00', $file['date']);
        $this->assertEquals('7865eaef28db1b906eaf1e4fa353796d', $file['computed_md5']);
        $this->assertEquals("/file/download.php/{$this->project_id}/1/BooksAuthors.txt", $file['download_url']);
        $this->assertEquals('rest_api_tester_1', $file['owner']['username']);
    }
}
