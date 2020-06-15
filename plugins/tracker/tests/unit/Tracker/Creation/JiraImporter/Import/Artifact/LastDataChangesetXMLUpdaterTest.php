<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use XML_SimpleXMLCDATAFactory;

class LastDataChangesetXMLUpdaterTest extends TestCase
{
    /**
     * @var LastDataChangesetXMLUpdater
     */
    private $updater;

    protected function setUp(): void
    {
        parent::setUp();

        $this->updater = new LastDataChangesetXMLUpdater(
            new FieldChangeStringBuilder(
                new XML_SimpleXMLCDATAFactory()
            )
        );
    }

    public function testItAddsJiraLinkInformation(): void
    {
        $issue = [
            'key' => 'key01',
            'renderedFields' => []
        ];

        $changeset_node = new \SimpleXMLElement(
            "<changeset/>"
        );

        $this->updater->updateLastXMLChangeset(
            $issue,
            'URL',
            $changeset_node
        );

        $this->assertSame(
            "URL/browse/key01",
            (string) $changeset_node->field_change->value
        );
    }
}
