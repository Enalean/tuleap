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

namespace Tuleap\Gitlab\API;

use Tuleap\Gitlab\Test\Builder\CredentialsTestBuilder;
use Tuleap\Gitlab\Test\Stubs\GitlabClientWrapperStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GitlabProjectBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var Credentials
     */
    private $credentials;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = CredentialsTestBuilder::get()->build();
    }

    public function testItThrowsAnExceptionIfRequestBodyIsEmpty(): void
    {
        $gitlab_client   = GitlabClientWrapperStub::buildWithJson([]);
        $project_builder = new GitlabProjectBuilder(
            $gitlab_client
        );
        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItThrowsAnExceptionIfRequestBodyDoesNotHaveIdKey(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            'description' => 'My GitLab project',
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'last_activity_at' => '2020-11-12',
            'default_branch' => 'main',
        ]);

        $project_builder = new GitlabProjectBuilder(
            $gitlab_client
        );

        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItThrowsAnExceptionIfRequestBodyDoesNotHaveDescriptionKey(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson(['id' => 1,
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'last_activity_at' => '2020-11-12',
            'default_branch' => 'main',
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItThrowsAnExceptionIfRequestBodyDoesNotHaveWebURLKey(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            'id' => 1,
            'description' => 'My GitLab project',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'last_activity_at' => '2020-11-12',
            'default_branch' => 'main',
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItThrowsAnExceptionIfRequestBodyDoesNotHavePathKey(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            'id' => 1,
            'description' => 'My GitLab project',
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'last_activity_at' => '2020-11-12',
            'default_branch' => 'main',
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItThrowsAnExceptionIfRequestBodyDoesNotHaveLastActivityKey(): void
    {
        $gitlab_client   = GitlabClientWrapperStub::buildWithJson([
            'id' => 1,
            'description' => 'My GitLab project',
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'default_branch' => 'main',
        ]);
        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItThrowsAnExceptionIfRequestBodyDoesNotHaveDefaultBranchKey(): void
    {
        $gitlab_client   = GitlabClientWrapperStub::buildWithJson([
            'id' => 1,
            'description' => 'My GitLab project',
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'last_activity_at' => '2020-11-12',
        ]);
        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItBuildsAGitlabProjectObject(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            'id' => 1,
            'description' => 'My GitLab project',
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'last_activity_at' => '2020-11-12',
            'default_branch' => 'main',
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $gitlab_project = $project_builder->getProjectFromGitlabAPI($this->credentials, 1);

        self::assertSame(1, $gitlab_project->getId());
        self::assertSame('My GitLab project', $gitlab_project->getDescription());
        self::assertSame('root/project01', $gitlab_project->getPathWithNamespace());
        self::assertSame('https://example.com/root/project01', $gitlab_project->getWebUrl());
        self::assertSame(1605135600, $gitlab_project->getLastActivityAt()->getTimestamp());
        self::assertSame('main', $gitlab_project->getDefaultBranch());
    }

    public function testItBuildsAGitlabProjectObjectWithNullDescription(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            'id' => 1,
            'description' => null,
            'web_url' => 'https://example.com/root/project01',
            'name' => 'Project 01',
            'path_with_namespace' => 'root/project01',
            'last_activity_at' => '2020-11-12',
            'default_branch' => 'main',
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $gitlab_project = $project_builder->getProjectFromGitlabAPI($this->credentials, 1);

        self::assertSame(1, $gitlab_project->getId());
        self::assertSame('', $gitlab_project->getDescription());
        self::assertSame('root/project01', $gitlab_project->getPathWithNamespace());
        self::assertSame('https://example.com/root/project01', $gitlab_project->getWebUrl());
        self::assertSame(1605135600, $gitlab_project->getLastActivityAt()->getTimestamp());
        self::assertSame('main', $gitlab_project->getDefaultBranch());
    }

    public function testItThrowsAnExceptionIfTheResponseIsEmpty(): void
    {
        $gitlab_client   = GitlabClientWrapperStub::buildWithNullResponse();
        $project_builder = new GitlabProjectBuilder(
            $gitlab_client
        );
        $this->expectException(GitlabResponseAPIException::class);

        $project_builder->getProjectFromGitlabAPI($this->credentials, 1);
    }

    public function testItDoesNotStopWhenWeSynchronizeGitLabRepositoriesAndWhenSomeRepositoriesAreNotInitialized(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            [
                'id' => 1,
                'description' => 'My GitLab project',
                'web_url' => 'https://example.com/root/project01',
                'name' => 'Project 01',
                'path_with_namespace' => 'root/project01',
                'last_activity_at' => '2020-11-12',
            ],
            [
                'id' => 2,
                'description' => 'My GitLab project number 2',
                'web_url' => 'https://example.com/root/project02',
                'name' => 'Project 02',
                'path_with_namespace' => 'root/project02',
                'last_activity_at' => '2020-11-12',
                'default_branch' => 'main',
            ],
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $gitlab_project = $project_builder->getGroupProjectsFromGitlabAPI($this->credentials, 1);

        self::assertCount(1, $gitlab_project);
        self::assertSame(2, $gitlab_project[0]->getId());
        self::assertSame('My GitLab project number 2', $gitlab_project[0]->getDescription());
        self::assertSame('root/project02', $gitlab_project[0]->getPathWithNamespace());
        self::assertSame('https://example.com/root/project02', $gitlab_project[0]->getWebUrl());
        self::assertSame(1605135600, $gitlab_project[0]->getLastActivityAt()->getTimestamp());
        self::assertSame('main', $gitlab_project[0]->getDefaultBranch());
    }

    public function testItBuildsSeveralGitlabProjectObject(): void
    {
        $gitlab_client = GitlabClientWrapperStub::buildWithJson([
            [
                'id' => 1,
                'description' => 'My GitLab project',
                'web_url' => 'https://example.com/root/project01',
                'name' => 'Project 01',
                'path_with_namespace' => 'root/project01',
                'last_activity_at' => '2020-11-12',
                'default_branch' => 'main',
            ],
            [
                'id' => 2,
                'description' => 'My GitLab project number 2',
                'web_url' => 'https://example.com/root/project02',
                'name' => 'Project 02',
                'path_with_namespace' => 'root/project02',
                'last_activity_at' => '2020-11-12',
                'default_branch' => 'main',
            ],
        ]);

        $project_builder = new GitlabProjectBuilder($gitlab_client);

        $gitlab_project = $project_builder->getGroupProjectsFromGitlabAPI($this->credentials, 1);

        self::assertSame(1, $gitlab_project[0]->getId());
        self::assertSame('My GitLab project', $gitlab_project[0]->getDescription());
        self::assertSame('root/project01', $gitlab_project[0]->getPathWithNamespace());
        self::assertSame('https://example.com/root/project01', $gitlab_project[0]->getWebUrl());
        self::assertSame(1605135600, $gitlab_project[0]->getLastActivityAt()->getTimestamp());
        self::assertSame('main', $gitlab_project[0]->getDefaultBranch());

        self::assertSame(2, $gitlab_project[1]->getId());
        self::assertSame('My GitLab project number 2', $gitlab_project[1]->getDescription());
        self::assertSame('root/project02', $gitlab_project[1]->getPathWithNamespace());
        self::assertSame('https://example.com/root/project02', $gitlab_project[1]->getWebUrl());
        self::assertSame(1605135600, $gitlab_project[1]->getLastActivityAt()->getTimestamp());
        self::assertSame('main', $gitlab_project[1]->getDefaultBranch());
    }
}
