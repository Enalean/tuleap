<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

use Tuleap\ProFTPd\Xferlog\InvalidEntryException;

require_once __DIR__ . '/../../bootstrap.php';

class ParserTest extends \PHPUnit\Framework\TestCase
{

    public function testItExtractAnEntryFromALine()
    {
        $line = "Tue Jan 14 05:05:49 2014 0 ::ffff:192.168.1.66 295 /.message a _ o a anon@localhost ftp 0 * c";

        $parser = new \Tuleap\ProFTPd\Xferlog\Parser();
        $entry = $parser->extract($line);

        $this->assertEquals(strtotime("Tue Jan 14 05:05:49 2014"), $entry->current_time);
        $this->assertEquals(0, $entry->transfer_time);
        $this->assertEquals("::ffff:192.168.1.66", $entry->remote_host);
        $this->assertEquals(295, $entry->file_size);
        $this->assertEquals("/.message", $entry->filename);
        $this->assertEquals("a", $entry->transfer_type);
        $this->assertEquals("_", $entry->special_action_flag);
        $this->assertEquals("o", $entry->direction);
        $this->assertEquals("a", $entry->access_mode);
        $this->assertEquals("anon@localhost", $entry->username);
        $this->assertEquals("ftp", $entry->service_name);
        $this->assertEquals("0", $entry->authentication_method);
        $this->assertEquals("*", $entry->authenticated_user_id);
        $this->assertEquals("c", $entry->completion_status);
    }

    public function testItRaisesAnExceptionWhenTheLineIsInvalid()
    {
        $line = "invalid format";

        $parser = new \Tuleap\ProFTPd\Xferlog\Parser();

        $this->expectException(InvalidEntryException::class);

        $parser->extract($line);
    }
}
