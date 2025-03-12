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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Psr\Log\NullLogger;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraCloudClientStub;
use Tuleap\Tracker\Test\Stub\Creation\JiraImporter\JiraServerClientStub;

#[DisableReturnValueGenerationForTestDoubles]
final class JiraProjectBuilderTest extends TestCase
{
    public function testItBuildsRecursivelyProjects(): void
    {
        $wrapper = JiraCloudClientStub::aJiraCloudClient([
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=0' => [
                'isLast'     => false,
                'maxResults' => 2,
                'startAt'    => 0,
                'values'     => [
                    [
                        'key'  => 'TO',
                        'name' => 'toto',
                    ],
                ],
            ],
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=1' => [
                'isLast'     => true,
                'maxResults' => 2,
                'startAt'    => 1,
                'values'     => [
                    [
                        'key'  => 'TU',
                        'name' => 'tutu',
                    ],
                ],
            ],
        ]);

        $expected_collection = new JiraProjectCollection();
        $expected_collection->addProject(
            [
                'id'    => 'TO',
                'label' => 'toto',
            ]
        );
        $expected_collection->addProject(
            [
                'id'    => 'TU',
                'label' => 'tutu',
            ]
        );

        $builder = new JiraProjectBuilder();
        $result  = $builder->build($wrapper, new NullLogger());

        self::assertEquals($expected_collection->getJiraProjects(), $result);
    }

    public function testItThrowsAndExceptionIfRecursiveCallGoesWrong(): void
    {
        $wrapper = JiraCloudClientStub::aJiraCloudClient([
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=0' => [
                'isLast'     => false,
                'maxResults' => 2,
                'startAt'    => 0,
                'values'     => [
                    [
                        'key'  => 'TO',
                        'name' => 'toto',
                    ],
                ],
            ],
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=1' => null,
        ]);

        $this->expectException(UnexpectedFormatException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($wrapper, new NullLogger());
    }

    public function testItThrowsALogicExceptionIfJiraAPIHaveChanged(): void
    {
        $wrapper = JiraCloudClientStub::aJiraCloudClient([
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=0' => [
                'isLast'     => false,
                'maxResults' => 2,
                'startAt'    => 0,
                'values'     => [
                    [
                        'key'      => 'TO',
                        'dsdsdsds' => 'toto',
                    ],
                ],
            ],
            ClientWrapper::JIRA_CORE_BASE_URL . '/project/search?startAt=1' => null,
        ]);

        $this->expectException(UnexpectedFormatException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($wrapper, new NullLogger());
    }

    public function testItIteratesOverJiraServerPayload(): void
    {
        $jira_client = JiraServerClientStub::aJiraServerClient([
            '/rest/api/2/project' => [
                [
                    'key'  => 'MPN',
                    'name' => 'My project name',
                ],
                [
                    'key'  => 'SP',
                    'name' => 'Scrum Project',
                ],
            ],
        ]);

        $builder = new JiraProjectBuilder();
        $result  = $builder->build($jira_client, new NullLogger());

        self::assertEquals(
            (new JiraProjectCollection())
                ->addProject(['id' => 'MPN', 'label' => 'My project name'])
                ->addProject(['id' => 'SP', 'label' => 'Scrum Project'])
                ->getJiraProjects(),
            $result
        );
    }

    public function testItCatchesMissingMandatoryInfoInJiraServerPayload(): void
    {
        $jira_client = JiraServerClientStub::aJiraServerClient([
            '/rest/api/2/project' => [
                [
                    'name' => 'My project name',
                ],
            ],
        ]);

        $this->expectException(UnexpectedFormatException::class);

        $builder = new JiraProjectBuilder();
        $builder->build($jira_client, new NullLogger());
    }
}
