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

require_once __DIR__.'/../../bootstrap.php';

class ParserTest extends TuleapTestCase {

    public function itExtractAnEntryFromALine() {
        $line = "Tue Jan 14 05:05:49 2014 0 ::ffff:192.168.1.66 295 /.message a _ o a anon@localhost ftp 0 * c";

        $parser = new \Tuleap\ProFTPd\Xferlog\Parser();
        $entry = $parser->extract($line);

        $this->assertEqual(strtotime("Tue Jan 14 05:05:49 2014"), $entry->current_time);
        $this->assertEqual(0, $entry->transfer_time);
        $this->assertEqual("::ffff:192.168.1.66", $entry->remote_host);
        $this->assertEqual(295, $entry->file_size);
        $this->assertEqual("/.message", $entry->filename);
        $this->assertEqual("a", $entry->transfer_type);
        $this->assertEqual("_", $entry->special_action_flag);
        $this->assertEqual("o", $entry->direction);
        $this->assertEqual("a", $entry->access_mode);
        $this->assertEqual("anon@localhost", $entry->username);
        $this->assertEqual("ftp", $entry->service_name);
        $this->assertEqual("0", $entry->authentication_method);
        $this->assertEqual("*", $entry->authenticated_user_id);
        $this->assertEqual("c", $entry->completion_status);
    }

    public function itRaisesAnExceptionWhenTheLineIsInvalid() {
        $line = "invalid format";
        $this->expectException();

        $parser = new \Tuleap\ProFTPd\Xferlog\Parser();
        $parser->extract($line);
    }
}