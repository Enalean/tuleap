<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\JiraImporter;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use SimpleXMLElement;
use XML_SimpleXMLCDATAFactory;

final class JiraXMLExportTest extends TestCase
{
    public function testItExportsTimetrackingConfigurationForJiraTracker(): void
    {
        $xml_tracker = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker/>');

        $exporter = new JiraXMLExport(
            new XML_SimpleXMLCDATAFactory(),
            new NullLogger()
        );

        $exporter->exportJiraTimetracking($xml_tracker);

        $this->assertTrue(isset($xml_tracker->timetracking));
        $this->assertSame("1", (string) $xml_tracker->timetracking['is_enabled']);

        $this->assertTrue(isset($xml_tracker->timetracking->permissions));
        $this->assertTrue(isset($xml_tracker->timetracking->permissions->write));
        $this->assertCount(1, $xml_tracker->timetracking->permissions->write->children());
        $this->assertSame("project_members", (string) $xml_tracker->timetracking->permissions->write->ugroup);

        $this->assertFalse(isset($xml_tracker->timetracking->permissions->read));
    }
}
