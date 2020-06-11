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
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeStringBuilder;
use Tuleap\Tracker\XML\Exporter\FieldChange\FieldChangeTextBuilder;
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
            ),
            new FieldChangeTextBuilder(
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
            $changeset_node,
            new FieldMappingCollection()
        );

        $this->assertSame(
            "URL/browse/key01",
            (string) $changeset_node->field_change->value
        );
    }

    public function testItAddsRenderedHTMLContentForTextField(): void
    {
        $issue = [
            'key' => 'key01',
            'renderedFields' => [
                'description' => "<b>aaaaaa</b>"
            ]
        ];

        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'description',
                'Fdescription',
                'Description',
                'text'
            )
        );

        $changeset_node = new \SimpleXMLElement(
            "<changeset/>"
        );

        $this->updater->updateLastXMLChangeset(
            $issue,
            'URL',
            $changeset_node,
            $mapping_collection
        );

        $this->assertSame(
            "<b>aaaaaa</b>",
            (string) $changeset_node->field_change[1]->value
        );
    }

    public function testItUpdatesTheValueWithRenderedHTMLContentForTextField(): void
    {
        $issue = [
            'key' => 'key01',
            'renderedFields' => [
                'description' => "<b>aaaaaa</b>"
            ]
        ];

        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'description',
                'Fdescription',
                'Description',
                'text'
            )
        );

        $changeset_node = new \SimpleXMLElement(
            '<changeset>
                <field_change type="text" field_name="description">
                    <value format="text"><![CDATA[aaaaaa]]></value>
                </field_change>
            </changeset>'
        );

        $this->updater->updateLastXMLChangeset(
            $issue,
            'URL',
            $changeset_node,
            $mapping_collection
        );

        $this->assertSame(
            "<b>aaaaaa</b>",
            (string) $changeset_node->field_change[1]->value
        );
    }

    public function testItDoesNotUpdateTheValueWithRenderedHTMLContentIfFieldIsNotTextField(): void
    {
        $issue = [
            'key' => 'key01',
            'renderedFields' => [
                'description' => "<b>aaaaaa</b>"
            ]
        ];

        $mapping_collection = new FieldMappingCollection();
        $mapping_collection->addMapping(
            new FieldMapping(
                'description',
                'Fdescription',
                'Description',
                'string'
            )
        );

        $changeset_node = new \SimpleXMLElement(
            '<changeset>
                <field_change type="text" field_name="description">
                    <value format="text"><![CDATA[aaaaaa]]></value>
                </field_change>
            </changeset>'
        );

        $this->updater->updateLastXMLChangeset(
            $issue,
            'URL',
            $changeset_node,
            $mapping_collection
        );

        $this->assertSame(
            "aaaaaa",
            (string) $changeset_node->field_change[0]->value
        );
    }



    public function testItDoesNotUpdateTheValueWithRenderedHTMLContentIfFieldIsNotInMapping(): void
    {
        $issue = [
            'key' => 'key01',
            'renderedFields' => [
                'description' => "<b>aaaaaa</b>"
            ]
        ];

        $mapping_collection = new FieldMappingCollection();
        $changeset_node = new \SimpleXMLElement(
            '<changeset>
                <field_change type="text" field_name="description">
                    <value format="text"><![CDATA[aaaaaa]]></value>
                </field_change>
            </changeset>'
        );

        $this->updater->updateLastXMLChangeset(
            $issue,
            'URL',
            $changeset_node,
            $mapping_collection
        );

        $this->assertSame(
            "aaaaaa",
            (string) $changeset_node->field_change[0]->value
        );
    }
}
