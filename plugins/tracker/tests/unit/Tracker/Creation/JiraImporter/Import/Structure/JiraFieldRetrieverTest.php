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

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Structure;

use Psr\Log\NullLogger;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\Import\AlwaysThereFieldsExporter;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraServerClientStub;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

final class JiraFieldRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public static function getTestData(): iterable
    {
        yield 'it exports jira fields with common createmeta and build an array indexed by id' => [
            'payloads' => [
                ClientWrapper::JIRA_CORE_BASE_URL . "/issue/createmeta?projectKeys=projID&issuetypeIds=issueName&expand=projects.issuetypes.fields" => [
                    'projects' => [
                        [
                            'issuetypes' => [
                                [
                                    'fields' => [
                                        'summary' => [
                                            'required' => true,
                                            'schema' => [
                                                'type' => 'string',
                                                'system' => 'summary',
                                            ],
                                            'name' => 'Summary',
                                            'key' => 'summary',
                                            'hasDefaultValue' => false,
                                            'operation' => [
                                                'set',
                                            ],
                                        ],
                                        'custom_01' => [
                                            'required' => false,
                                            'schema' => [
                                                'type' => 'user',
                                                'custom' => 'com.atlassian.jira.toolkit:lastupdaterorcommenter',
                                                'customId' => 10071,
                                            ],
                                            'name' => '[opt] Last updator',
                                            'key' => 'customfield_10071',
                                            'hasDefaultValue' => false,
                                            'operation' => [
                                                'set',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'tests' => function (array $result) {
                assertCount(2, $result);

                $system_field_representation = $result['summary'];
                assertEquals("summary", $system_field_representation->getId());
                assertEquals("Summary", $system_field_representation->getLabel());
                assertNotNull($system_field_representation->getSchema());
                assertTrue($system_field_representation->isRequired());
                assertTrue($system_field_representation->isSubmit());

                $custom_field_representation = $result['custom_01'];
                assertEquals("custom_01", $custom_field_representation->getId());
                assertEquals("[opt] Last updator", $custom_field_representation->getLabel());
                assertNotNull($custom_field_representation->getSchema());
                assertFalse($custom_field_representation->isRequired());
                assertTrue($custom_field_representation->isSubmit());
            },
        ];

        yield 'it exports jira fields that are only visible in the edit screen' => [
            'payloads' => [
                ClientWrapper::JIRA_CORE_BASE_URL . "/issue/createmeta?projectKeys=projID&issuetypeIds=issueName&expand=projects.issuetypes.fields" => [
                    'projects' => [
                        [
                            'issuetypes' => [
                                [
                                    'fields' => [
                                        'summary' => [
                                            'required' => true,
                                            'schema' => [
                                                'type' => 'string',
                                                'system' => 'summary',
                                            ],
                                            'name' => 'Summary',
                                            'key' => 'summary',
                                            'hasDefaultValue' => false,
                                            'operation' => [
                                                'set',
                                            ],
                                        ],
                                        'custom_01' => [
                                            'required' => false,
                                            'schema' => [
                                                'type' => 'user',
                                                'custom' => 'com.atlassian.jira.toolkit:lastupdaterorcommenter',
                                                'customId' => 10071,
                                            ],
                                            'name' => '[opt] Last updator',
                                            'key' => 'customfield_10071',
                                            'hasDefaultValue' => false,
                                            'operation' => [
                                                'set',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22projID%22+AND+issuetype%3DissueName&expand=editmeta&startAt=0&maxResults=1' => [
                    "expand"     => "names,schema",
                    "startAt"    => 0,
                    "maxResults" => 1,
                    "total"      => 18,
                    "issues"     => [
                        [
                            "expand" => "operations,versionedRepresentations,editmeta,changelog,renderedFields",
                            "id"     => "10033",
                            "self"   => "https://jira.example.com/rest/api/2/issue/10033",
                            "key"    => "projID-6",
                            'editmeta' => [
                                'fields' => [
                                    'summary' => [
                                        'required' => true,
                                        'schema' => [
                                            'type' => 'string',
                                            'system' => 'summary',
                                        ],
                                        'name' => 'Summary',
                                        'key' => 'summary',
                                        'hasDefaultValue' => false,
                                        'operation' => [
                                            'set',
                                        ],
                                    ],
                                    'custom_01' => [
                                        'required' => false,
                                        'schema' => [
                                            'type' => 'user',
                                            'custom' => 'com.atlassian.jira.toolkit:lastupdaterorcommenter',
                                            'customId' => 10071,
                                        ],
                                        'name' => '[opt] Last updator',
                                        'key' => 'customfield_10071',
                                        'hasDefaultValue' => false,
                                        'operation' => [
                                            'set',
                                        ],
                                    ],
                                    "customfield_10249" => [
                                        "required"      => false,
                                        "schema"        => [
                                            "type"     => "option",
                                            "custom"   => "com.atlassian.jira.plugin.system.customfieldtypes:select",
                                            "customId" => 10249,
                                        ],
                                        "name"          => "Branche intégration",
                                        "fieldId"       => "customfield_10249",
                                        "operations"    => [
                                            "set",
                                        ],
                                        "allowedValues" => [
                                            [
                                                "self"  => "https://jira.example.com/rest/api/2/customFieldOption/10142",
                                                "value" => "BACKBONE_ITG",
                                                "id"    => "10142",
                                            ],
                                            [
                                                "self"  => "https://jira.example.com/rest/api/2/customFieldOption/10430",
                                                "value" => "ETHERCAT_ITG",
                                                "id"    => "10430",
                                            ],
                                            [
                                                "self"  => "https://jira.example.com/rest/api/2/customFieldOption/10143",
                                                "value" => "STD_ITG_01.01",
                                                "id"    => "10143",
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'tests' => function (array $result) {
                assertCount(3, $result);

                assertTrue($result['summary']->isSubmit());
                assertTrue($result['custom_01']->isSubmit());

                assertFalse($result['customfield_10249']->isRequired());
                assertEquals('Branche intégration', $result['customfield_10249']->getLabel());
                assertEquals('com.atlassian.jira.plugin.system.customfieldtypes:select', $result['customfield_10249']->getSchema());
                assertCount(3, $result['customfield_10249']->getBoundValues());
                assertEquals('10142', $result['customfield_10249']->getBoundValues()[0]->getId());
                assertEquals('BACKBONE_ITG', $result['customfield_10249']->getBoundValues()[0]->getName());
                assertFalse($result['customfield_10249']->isSubmit());
            },
        ];

        yield 'it returns an empty array when no fields are found' => [
            'payloads' => [],
            'tests' => function (array $results) {
                assertEquals([], $results);
            },
        ];

        yield 'it adds priority if the field is detected in editmeta payload but was not listed in createmeta or editmeta (jira 8.5.1)' => [
            'payloads' => [
                ClientWrapper::JIRA_CORE_BASE_URL . "/issue/createmeta?projectKeys=projID&issuetypeIds=issueName&expand=projects.issuetypes.fields" => [
                    'projects' => [
                        [
                            'issuetypes' => [
                                [
                                    'fields' => [
                                        'summary' => [
                                            'required' => true,
                                            'schema' => [
                                                'type' => 'string',
                                                'system' => 'summary',
                                            ],
                                            'name' => 'Summary',
                                            'key' => 'summary',
                                            'hasDefaultValue' => false,
                                            'operation' => [
                                                'set',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                ClientWrapper::JIRA_CORE_BASE_URL . '/search?jql=project%3D%22projID%22+AND+issuetype%3DissueName&expand=editmeta&startAt=0&maxResults=1' => [
                    "expand"     => "names,schema",
                    "startAt"    => 0,
                    "maxResults" => 1,
                    "total"      => 18,
                    "issues"     => [
                        [
                            "expand" => "operations,versionedRepresentations,editmeta,changelog,renderedFields",
                            "id"     => "10033",
                            "self"   => "https://jira.example.com/rest/api/2/issue/10033",
                            "key"    => "projID-6",
                            'fields' => [
                                "priority" => [
                                    "self"    => "https://jira.example.com/rest/api/2/priority/4",
                                    "iconUrl" => "https://jira.example.com/images/icons/priorities/trivial.svg",
                                    "name"    => "Normal",
                                    "id"      => "4",
                                ],
                            ],
                            'editmeta' => [
                                'fields' => [
                                    'summary' => [
                                        'required' => true,
                                        'schema' => [
                                            'type' => 'string',
                                            'system' => 'summary',
                                        ],
                                        'name' => 'Summary',
                                        'key' => 'summary',
                                        'hasDefaultValue' => false,
                                        'operation' => [
                                            'set',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                ClientWrapper::JIRA_CORE_BASE_URL . '/priority' => [
                    [
                        "self"        => "https://jira.example.com/rest/api/2/priority/1",
                        "statusColor" => "#d04437",
                        "description" => "This problem will block progress.",
                        "iconUrl"     => "https://jira.example.com/images/icons/priorities/highest.svg",
                        "name"        => "Highest",
                        "id"          => "1",
                    ],
                    [
                        "self"        => "https://jira.example.com/rest/api/2/priority/2",
                        "statusColor" => "#f15C75",
                        "description" => "Serious problem that could block progress.",
                        "iconUrl"     => "https://jira.example.com/images/icons/priorities/high.svg",
                        "name"        => "High",
                        "id"          => "2",
                    ],
                    [
                        "self"        => "https://jira.example.com/rest/api/2/priority/3",
                        "statusColor" => "#f79232",
                        "description" => "Has the potential to affect progress.",
                        "iconUrl"     => "https://jira.example.com/images/icons/priorities/medium.svg",
                        "name"        => "Medium",
                        "id"          => "3",
                    ],
                    [
                        "self"        => "https://jira.example.com/rest/api/2/priority/4",
                        "statusColor" => "#707070",
                        "description" => "Minor problem or easily worked around.",
                        "iconUrl"     => "https://jira.example.com/images/icons/priorities/low.svg",
                        "name"        => "Low",
                        "id"          => "4",
                    ],
                    [
                        "self"        => "https://jira.example.com/rest/api/2/priority/5",
                        "statusColor" => "#999999",
                        "description" => "Trivial problem with little or no impact on progress.",
                        "iconUrl"     => "https://jira.example.com/images/icons/priorities/lowest.svg",
                        "name"        => "Lowest",
                        "id"          => "5",
                    ],
                ],
            ],
            'tests' => function (array $result) {
                assertCount(2, $result);

                assertArrayHasKey('summary', $result);
                assertArrayHasKey('priority', $result);

                assertEquals('Priority', $result['priority']->getLabel());
                assertEquals(AlwaysThereFieldsExporter::JIRA_PRIORITY_NAME, $result['priority']->getSchema());
                assertCount(5, $result['priority']->getBoundValues());
                assertEquals('1', $result['priority']->getBoundValues()[0]->getId());
                assertEquals('Highest', $result['priority']->getBoundValues()[0]->getName());
            },
        ];
    }

    /**
     * @dataProvider getTestData
     */
    public function testGetAllJiraFields(array $payloads, callable $tests): void
    {
        $wrapper = new class ($payloads) extends JiraCloudClientStub {
            public function __construct(array $payloads)
            {
                $this->urls = $payloads;
            }
        };

        $field_retriever = new JiraFieldRetriever(
            $wrapper,
            new NullLogger(),
            new AppendFieldsFromCreateMetaAPI($wrapper, new NullLogger()),
        );

        $result = $field_retriever->getAllJiraFields(
            'projID',
            'issueName',
            new FieldAndValueIDGenerator(),
        );

        $tests($result);
    }

    public function testItGetAllFieldsWithTheCreateMeta(): void
    {
        $payloads = [
            ClientWrapper::JIRA_CORE_BASE_URL . "/issue/createmeta/projID/issuetypes/issueName?startAt=0" => [
                "maxResults" => 50,
                "startAt" => 0,
                "total" => 2,
                "isLast" => true,
                'values' => [
                    [
                        'required' => true,
                        'schema' => [
                            'type' => 'string',
                            'system' => 'summary',
                        ],
                        'name' => 'Summary',
                        'fieldId' => 'summary',
                        'hasDefaultValue' => false,
                        'operation' => [
                            'set',
                        ],
                    ],
                    [
                        'required' => false,
                        'schema' => [
                            'type' => 'user',
                            'custom' => 'com.atlassian.jira.toolkit:lastupdaterorcommenter',
                            'customId' => 10071,
                        ],
                        'name' => '[opt] Last updator',
                        'fieldId' => 'customfield_10071',
                        'hasDefaultValue' => false,
                        'operation' => [
                            'set',
                        ],
                    ],
                ],
            ],
        ];

        $wrapper = new class ($payloads) extends JiraServerClientStub {
            public function __construct(array $payloads)
            {
                $this->urls = $payloads;
            }

            public function isJiraServer9(): bool
            {
                return false;
            }
        };

        $field_retriever = new JiraFieldRetriever(
            $wrapper,
            new NullLogger(),
            new AppendFieldsFromCreateMetaServer9API($wrapper, new NullLogger()),
        );

        $result = $field_retriever->getAllJiraFields(
            'projID',
            'issueName',
            new FieldAndValueIDGenerator(),
        );

        self::assertCount(2, $result);

        $system_field_representation = $result['summary'];
        self::assertEquals("summary", $system_field_representation->getId());
        self::assertEquals("Summary", $system_field_representation->getLabel());
        self::assertNotNull($system_field_representation->getSchema());
        self::assertTrue($system_field_representation->isRequired());
        self::assertTrue($system_field_representation->isSubmit());

        $custom_field_representation = $result['customfield_10071'];
        self::assertEquals("customfield_10071", $custom_field_representation->getId());
        self::assertEquals("[opt] Last updator", $custom_field_representation->getLabel());
        self::assertNotNull($custom_field_representation->getSchema());
        self::assertFalse($custom_field_representation->isRequired());
        self::assertTrue($custom_field_representation->isSubmit());
    }
}
