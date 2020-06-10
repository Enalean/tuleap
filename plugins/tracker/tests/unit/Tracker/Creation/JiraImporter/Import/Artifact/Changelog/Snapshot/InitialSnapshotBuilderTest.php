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

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\CreationStateListValueFormatter;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

class InitialSnapshotBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsSnapshotForInitialChangeset(): void
    {
        $wrapper = Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->andReturn(
            $this->buildWrapperResponse()
        );

        $generator = new InitialSnapshotBuilder(
            new ChangelogEntriesBuilder(
                $wrapper
            ),
            new CreationStateListValueFormatter()
        );

        $user             = Mockery::mock(PFUser::class);
        $current_snapshot = $this->buildCurrentSnapshot($user);
        $jira_issue_api   = [
            "key" => "key01",
            "fields" => [
                "created" => "2020-03-25T14:10:10.823+0100"
            ]
        ];

        $initial_snapshot = $generator->buildInitialSnapshot(
            $user,
            $current_snapshot,
            $jira_issue_api
        );

        $this->assertNull($initial_snapshot->getFieldInSnapshot("environment"));
        $this->assertNull($initial_snapshot->getFieldInSnapshot("customfield_10036"));

        $this->assertSame($user, $initial_snapshot->getUser());
        $this->assertSame(1585141810, $initial_snapshot->getDate()->getTimestamp());

        $this->assertSame(['id' => "10000"], $initial_snapshot->getFieldInSnapshot('status')->getValue());
        $this->assertSame([['id' => "10009"]], $initial_snapshot->getFieldInSnapshot('customfield_10040')->getValue());
        $this->assertSame("dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq", $initial_snapshot->getFieldInSnapshot('description')->getValue());
        $this->assertNull($initial_snapshot->getFieldInSnapshot('description')->getRenderedValue());
    }

    private function buildWrapperResponse(): array
    {
        return [
            "maxResults" => 100,
            "startAt"    => 0,
            "total"      => 5,
            "isLast"     => true,
            "values"     => [
                0 => [
                    "id" => "100",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10036",
                            "from"       => null,
                            "fromString" => null,
                            "to"         => null,
                            "toString"   => "9"
                        ]
                    ]
                ],
                1 => [
                    "id" => "101",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10036",
                            "from"       => null,
                            "fromString" => "9",
                            "to"         => null,
                            "toString"   => "11"
                        ]
                    ]
                ],
                2 => [
                    "id" => "102",
                    "items" => [
                        0 => [
                            "fieldId"    => "status",
                            "from"       => "10000",
                            "fromString" => "To Do",
                            "to"         => "10001",
                            "toString"   => "Done"
                        ]
                    ]
                ],
                3 => [
                    "id" => "103",
                    "items" => [
                        0 => [
                            "fieldId"    => "customfield_10040",
                            "from"       => "[10009]",
                            "fromString" => "mulit1",
                            "to"         => "[10009, 10010]",
                            "toString"   => "mulit1,multi2"
                        ],
                        1 => [
                            "fieldId"    => "environment",
                            "from"       => null,
                            "fromString" => "\r\n----\r\n",
                            "to"         => null,
                            "toString"   => "----\r\n"
                        ]
                    ]
                ],
                4 => [
                    "id" => "104",
                    "items" => [
                        0 => [
                            "fieldId"    => "description",
                            "from"       => null,
                            "fromString" => "dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq",
                            "to"         => null,
                            "toString"   => "*dsdsdsds*\n\n*qdsdsqdsqdsq*\n\n\n\n*dsqdsdsq*"
                        ]
                    ]
                ],
            ]
        ];
    }

    private function buildCurrentSnapshot(PFUser $user): Snapshot
    {
        $snapshot = new Snapshot(
            $user,
            new DateTimeImmutable(),
            [
                new FieldSnapshot(
                    new FieldMapping(
                        "customfield_10036",
                        "Fcustomfield_10036",
                        "Field 01",
                        "com.atlassian.jira.plugin.system.customfieldtypes:float"
                    ),
                    "11",
                    null
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "status",
                        "Fstatus",
                        "status",
                        "status"
                    ),
                    "10001",
                    null
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "customfield_10040",
                        "Fcustomfield_10040",
                        "Field 02",
                        "com.atlassian.jira.plugin.system.customfieldtypes:multiselect"
                    ),
                    "[10009, 10010]",
                    null
                ),
                new FieldSnapshot(
                    new FieldMapping(
                        "description",
                        "Fdescription",
                        "Description",
                        "description"
                    ),
                    "*dsdsdsds*\n\n*qdsdsqdsqdsq*\n\n\n\n*dsqdsdsq*",
                    "<p>dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq</p>"
                )
            ]
        );

        return $snapshot;
    }
}
