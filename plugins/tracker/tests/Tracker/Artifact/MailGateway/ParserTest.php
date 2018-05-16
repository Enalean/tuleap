<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\MailGateway;

require_once __DIR__.'/../../../bootstrap.php';

use Tracker_Artifact_MailGateway_Parser;

class ParserTest extends \TuleapTestCase
{
    private $email_in_iso_8859_1;

    public function setUp()
    {
        parent::setUp();
        $fixtures = dirname(__FILE__). '/_fixtures';

        $this->email_in_iso_8859_1 = file_get_contents($fixtures .'/mail-iso-8859-1.txt');
    }

    public function itReturnsBodyEncodedInUTF8()
    {
        $parser = new Tracker_Artifact_MailGateway_Parser();
        $raw_mail = $parser->parse($this->email_in_iso_8859_1);
        $this->assertEqual($raw_mail['body'], 'This should be correctly displayed: èàéô');
    }
}
