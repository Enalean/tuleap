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

class XML_SecurityTest extends TuleapTestCase {

    private $bad_xml = '<!DOCTYPE root
        [
        <!ENTITY foo SYSTEM "file:///etc/passwd">
        ]>
        <test><testing>&foo;</testing></test>';


    public function itDisableExternalLoadOfEntities() {
        if ($this->isCentOS5()) {
            $this->expectError();
        }
        $doc = $this->loadXML();
        $this->assertEqual('', (string)$doc->testing);
    }

    private function loadXML() {
        $xml_security = new XML_Security();
        $xml_security->disableExternalLoadOfEntities();

        $xml = simplexml_load_string($this->bad_xml);

        $xml_security->enableExternalLoadOfEntities();

        return $xml;
    }

    private function isCentOS5() {
        $etc_issue = file("/etc/issue");

        return strpos($etc_issue[0], "CentOS release 5") === 0;
    }

}
