<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\Snapshot;

use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntryValueRepresentation;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMappingCollection;

class ChangelogSnapshotBuilderTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildsASnapshotFromChangelogEntry(): void
    {
        $builder = new ChangelogSnapshotBuilder(
            new CreationStateListValueFormatter()
        );

        $user                          = Mockery::mock(PFUser::class);
        $changelog_entry               = $this->buildChangelogEntry();
        $jira_field_mapping_collection = $this->buildFieldMappingCollection();

        $snapshot = $builder->buildSnapshotFromChangelogEntry(
            $user,
            $changelog_entry,
            $jira_field_mapping_collection
        );

        $this->assertSame($user, $snapshot->getUser());
        $this->assertSame(1585141810, $snapshot->getDate()->getTimestamp());
        $this->assertCount(2, $snapshot->getAllFieldsSnapshot());

        $this->assertNull($snapshot->getFieldInSnapshot('environment'));
        $this->assertSame("9", $snapshot->getFieldInSnapshot('customfield_10036')->getValue());
        $this->assertSame(
            [
                ['id' => '10009'],
                ['id' => '10010'],
            ],
            $snapshot->getFieldInSnapshot('customfield_10040')->getValue()
        );
    }

    private function buildChangelogEntry(): ChangelogEntryValueRepresentation
    {
        return ChangelogEntryValueRepresentation::buildFromAPIResponse(
            [
                "id" => "100",
                "created" => "2020-03-25T14:10:10.823+0100",
                "items" => [
                    0 => [
                        "fieldId"    => "customfield_10036",
                        "from"       => null,
                        "fromString" => null,
                        "to"         => null,
                        "toString"   => "9"
                    ],
                    1 => [
                        "fieldId"    => "customfield_10040",
                        "from"       => "[10009]",
                        "fromString" => "mulit1",
                        "to"         => "[10009, 10010]",
                        "toString"   => "mulit1,multi2"
                    ],
                    2 => [
                        "fieldId"    => "environment",
                        "from"       => null,
                        "fromString" => "\r\n----\r\n",
                        "to"         => null,
                        "toString"   => "----\r\n"
                    ]
                ]
            ]
        );
    }

    private function buildFieldMappingCollection(): FieldMappingCollection
    {
        $collection = new FieldMappingCollection();
        $collection->addMapping(
            new FieldMapping(
                "customfield_10036",
                "Fcustomfield_10036",
                "Field 01",
                "com.atlassian.jira.plugin.system.customfieldtypes:float"
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "status",
                "Fstatus",
                "status",
                "status"
            )
        );
        $collection->addMapping(
            new FieldMapping(
                "customfield_10040",
                "Fcustomfield_10040",
                "Field 02",
                "com.atlassian.jira.plugin.system.customfieldtypes:multiselect"
            ),
        );
        $collection->addMapping(
            new FieldMapping(
                "description",
                "Fdescription",
                "Description",
                "description"
            )
        );

        return $collection;
    }
}
