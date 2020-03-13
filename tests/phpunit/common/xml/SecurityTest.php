<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\XML;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use XML_Security;

class XML_SecurityTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    use MockeryPHPUnitIntegration;

    private $bad_xml = '<!DOCTYPE root
        [
        <!ENTITY foo SYSTEM "file:///etc/passwd">
        ]>
        <test><testing>&foo;</testing></test>';

    protected function tearDown(): void
    {
        libxml_disable_entity_loader(true);
    }

    public function testItDisableExternalLoadOfEntities()
    {
        $doc = $this->loadXML();
        $this->assertEquals('', (string) $doc->testing);
    }

    private function loadXML()
    {
        $xml_security = new XML_Security();
        $xml_security->disableExternalLoadOfEntities();

        $xml = simplexml_load_string($this->bad_xml);

        $xml_security->enableExternalLoadOfEntities();

        return $xml;
    }
}
