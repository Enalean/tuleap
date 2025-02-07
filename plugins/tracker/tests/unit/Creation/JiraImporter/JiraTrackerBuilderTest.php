<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Tuleap\Tracker\Test\Tracker\Creation\JiraImporter\Stub\JiraCloudClientStub;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;

final class JiraTrackerBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAListOfTracker(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/project/IE' => [
                    'issueTypes' => [
                        ['id' => 'epic', 'name' => 'Epics', 'subtask' => false],
                        ['id' => 'issue', 'name' => 'Issues', 'subtask' => false],
                    ],
                ],
            ];
        };

        $builder = new JiraTrackerBuilder();
        $result  = $builder->buildFromProjectKey($wrapper, 'IE');

        $this->assertCount(2, $result);

        self::assertSame('epic', $result[0]->getId());
        self::assertSame('Epics', $result[0]->getName());
        self::assertSame(['id' => 'epic', 'name' => 'Epics'], $result[0]->toArray());

        self::assertSame('issue', $result[1]->getId());
        self::assertSame('Issues', $result[1]->getName());
        self::assertSame(['id' => 'issue', 'name' => 'Issues'], $result[1]->toArray());
    }

    public function testItThrowsAnExceptionIfJiraRepresentationHasChanged(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/project/IE' => [
                    'issueTypes' => [
                        ['id' => 'epic', 'ezezezez' => 'Epics'],
                        ['id' => 'issue', 'name' => 'Issues'],
                    ],
                ],
            ];
        };

        $this->expectException(\LogicException::class);

        $builder = new JiraTrackerBuilder();
        $builder->buildFromProjectKey($wrapper, 'IE');
    }

    public function testItBuildOneIssueType(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/issuetype/10015' => [
                    'self' => 'https://jira.example.com/rest/api/3/issuetype/10015',
                    'id' => '10015',
                    'description' => '',
                    'iconUrl' => 'https:/jira.example.com/secure/viewavatar?size=medium&avatarId=10300&avatarType=issuetype',
                    'name' => 'Activities-from-Jira',
                    'untranslatedName' => 'Activities-from-Jira',
                    'subtask' => false,
                    'avatarId' => 10300,
                ],
            ];
        };

        $builder    = new JiraTrackerBuilder();
        $issue_type = $builder->buildFromIssueTypeId($wrapper, '10015');

        assertEquals('10015', $issue_type->getId());
        assertFalse($issue_type->isSubtask());
        assertEquals('Activities-from-Jira', $issue_type->getName());
    }

    public function testItReturnsNullWhenClientDoesntHaveResponse(): void
    {
        $wrapper = new class extends JiraCloudClientStub {
            public array $urls = [
                ClientWrapper::JIRA_CORE_BASE_URL . '/issuetype/10015' => null,
            ];
        };

        $builder = new JiraTrackerBuilder();
        assertNull($builder->buildFromIssueTypeId($wrapper, '10015'));
    }
}
