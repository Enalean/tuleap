<?php
/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository;

use Tuleap\Gitlab\Test\Builder\GitlabProjectBuilder;
use Tuleap\Gitlab\Test\Stubs\RetrieveIntegrationDaoStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RepositoryIntegrationRetrieverTest extends TestCase
{
    private RetrieveIntegrationDaoStub $integration_retriever_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->integration_retriever_dao = RetrieveIntegrationDaoStub::fromNullRow();
    }

    public function getOneIntegration(): Ok|Err
    {
        $repository_integration_retriever = new RepositoryIntegrationRetriever($this->integration_retriever_dao);

        $project        = ProjectTestBuilder::aProject()->build();
        $gitlab_project = GitlabProjectBuilder::aGitlabProject(10)->build();
        return $repository_integration_retriever->getOneIntegration($project, $gitlab_project);
    }

    public function testItReturnsAnErrorIfTheRepositoryIntegrationIsNotRetrieved(): void
    {
        $result = $this->getOneIntegration();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(RepositoryIntegrationNotFoundFault::class, $result->error);
    }

    public function testItReturnsTheRepositoryIntegration(): void
    {
        $this->integration_retriever_dao = RetrieveIntegrationDaoStub::fromDefaultRow();
        $result                          = $this->getOneIntegration();

        self::assertTrue(Result::isOk($result));
        self::assertInstanceOf(GitlabRepositoryIntegration::class, $result->value);
    }
}
