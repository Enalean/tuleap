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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNull;

final class JiraTrackerBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsAListOfTracker(): void
    {
        $builder = new JiraTrackerBuilder();

        $wrapper     = \Mockery::mock(ClientWrapper::class);
        $project_key = "IE";

        $tracker_one = ["id" => "epic", "name" => "Epics", "subtask" => false];
        $tracker_two = ["id" => "issue", "name" => "Issues", "subtask" => false];


        $project_details = ["issueTypes" => [$tracker_one, $tracker_two]];

        $wrapper->shouldReceive('getUrl')->with(ClientWrapper::JIRA_CORE_BASE_URL . "/project/" . $project_key)->andReturn($project_details);

        $result = $builder->buildFromProjectKey($wrapper, $project_key);

        $this->assertCount(2, $result);

        $this->assertSame("epic", $result[0]->getId());
        $this->assertSame("Epics", $result[0]->getName());
        $this->assertSame(['id' => 'epic', 'name' => 'Epics'], $result[0]->toArray());

        $this->assertSame("issue", $result[1]->getId());
        $this->assertSame("Issues", $result[1]->getName());
        $this->assertSame(['id' => 'issue', 'name' => 'Issues'], $result[1]->toArray());
    }

    public function testItThrowsAnExceptionIfJiraRepresentationHasChanged(): void
    {
        $builder = new JiraTrackerBuilder();

        $wrapper     = \Mockery::mock(ClientWrapper::class);
        $project_key = "IE";

        $tracker_one = ["id" => "epic", "ezezezez" => "Epics"];
        $tracker_two = ["id" => "issue", "name" => "Issues"];

        $project_details = ["issueTypes" => [$tracker_one, $tracker_two]];

        $wrapper->shouldReceive('getUrl')->with(ClientWrapper::JIRA_CORE_BASE_URL . "/project/" . $project_key)->andReturn($project_details);

        $this->expectException(\LogicException::class);
        $builder->buildFromProjectKey($wrapper, $project_key);
    }

    public function testItBuildOneIssueType(): void
    {
        $builder = new JiraTrackerBuilder();

        $wrapper = \Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->with(ClientWrapper::JIRA_CORE_BASE_URL . '/issuetype/10015')->once()->andReturn(
            [
                "self" => "https://jira.example.com/rest/api/3/issuetype/10015",
                "id" => "10015",
                "description" => "",
                "iconUrl" => "https:/jira.example.com/secure/viewavatar?size=medium&avatarId=10300&avatarType=issuetype",
                "name" => "Activities-from-Jira",
                "untranslatedName" => "Activities-from-Jira",
                "subtask" => false,
                "avatarId" => 10300,
            ]
        );

        $issue_type = $builder->buildFromIssueTypeId($wrapper, '10015');

        assertEquals('10015', $issue_type->getId());
        assertFalse($issue_type->isSubtask());
        assertEquals('Activities-from-Jira', $issue_type->getName());
    }

    public function testItReturnsNullWhenClientDoesntHaveResponse(): void
    {
        $builder = new JiraTrackerBuilder();

        $wrapper = \Mockery::mock(ClientWrapper::class);
        $wrapper->shouldReceive('getUrl')->with(ClientWrapper::JIRA_CORE_BASE_URL . '/issuetype/10015')->once()->andReturnNull();

        assertNull($builder->buildFromIssueTypeId($wrapper, '10015'));
    }
}
