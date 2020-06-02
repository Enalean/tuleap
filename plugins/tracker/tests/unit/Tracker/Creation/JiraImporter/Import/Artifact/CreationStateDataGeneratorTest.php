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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Changelog\ChangelogEntriesBuilder;
use Tuleap\Tracker\Creation\JiraImporter\Import\Structure\FieldMapping;

class CreationStateDataGeneratorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsDataForInitialChangeset(): void
    {
        $wrapper = Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->andReturn(
            $this->buildWrapperResponse()
        );

        $generator = new CreationStateDataGenerator(
            new ChangelogEntriesBuilder(
                $wrapper
            )
        );

        $current_state_data = $this->buildCurrentStateData();
        $jira_issue_key     = "key01";

        $first_state_data = $generator->generateFirstStateContent(
            $current_state_data,
            $jira_issue_key
        );

        $this->assertArrayNotHasKey("environment", $first_state_data);
        $this->assertArrayNotHasKey("customfield_10036", $first_state_data);

        $this->assertSame(['id' => "10000"], $first_state_data['status']['value']);
        $this->assertSame([['id' => "10009"]], $first_state_data['customfield_10040']['value']);
        $this->assertSame("dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq", $first_state_data['description']['value']);
        $this->assertNull($first_state_data['description']['rendered_value']);
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

    private function buildCurrentStateData(): array
    {
        return [
            "customfield_10036" => [
                new FieldMapping(
                    "customfield_10036",
                    "Fcustomfield_10036",
                    "Field 01",
                    "com.atlassian.jira.plugin.system.customfieldtypes:float"
                ),
                "11",
                null
            ],
            "status" => [
                new FieldMapping(
                    "status",
                    "Fstatus",
                    "status",
                    "status"
                ),
                "10001",
                null
            ],
            "customfield_10040" => [
                new FieldMapping(
                    "customfield_10040",
                    "Fcustomfield_10040",
                    "Field 02",
                    "com.atlassian.jira.plugin.system.customfieldtypes:multiselect"
                ),
                "[10009, 10010]",
                null
            ],
            "description" => [
                new FieldMapping(
                    "description",
                    "Fdescription",
                    "Description",
                    "description"
                ),
                "*dsdsdsds*\n\n*qdsdsqdsqdsq*\n\n\n\n*dsqdsdsq*",
                "<p>dsdsdsds\n\nqdsdsqdsqdsq\n\n\n\ndsqdsdsq</p>"
            ]
        ];
    }
}
