<?php
/*
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

require_once 'bootstrap.php';

/**
 * Test cases are extracted from PHP tests for ldap_escape() function
 */
class LdapEscapeTest extends TuleapTestCase {
    private $query_escaper;
    private $subject;

    public function setUp() {
        parent::setUp();
        $this->query_escaper = new LdapQueryEscaper();
        $this->subject = 'foo=bar(baz)*';
    }

    public function itEscapesFilter() {
        $expected = 'foo=bar\28baz\29\2a';

        $this->assertEqual($expected, $this->query_escaper->escapeFilter($this->subject));
    }
}
