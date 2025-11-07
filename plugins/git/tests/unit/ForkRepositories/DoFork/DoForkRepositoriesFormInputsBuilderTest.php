<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Git\ForkRepositories\DoFork;

use CuyZ\Valinor\MapperBuilder;
use Git;
use ProjectUGroup;
use Psr\Http\Message\ServerRequestInterface;
use Tuleap\Git\ForkRepositories\ForkPathContainsDoubleDotsFault;
use Tuleap\Git\ForkRepositories\Permissions\MissingRequiredParametersFault;
use Tuleap\Http\Server\NullServerRequest;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DoForkRepositoriesFormInputsBuilderTest extends TestCase
{
    private DoForkRepositoriesFormInputsBuilder $builder;
    private array $permissions;
    private \PFUser $user;

    #[\Override]
    protected function setUp(): void
    {
        $this->user        = UserTestBuilder::aUser()->withUserName('johndoe')->build();
        $this->builder     = new DoForkRepositoriesFormInputsBuilder(
            new MapperBuilder()->mapper(),
        );
        $this->permissions = [
            Git::PERM_READ => [(string) ProjectUGroup::PROJECT_MEMBERS],
            Git::PERM_WRITE => [(string) ProjectUGroup::PROJECT_MEMBERS],
            Git::PERM_WPLUS => [(string) ProjectUGroup::PROJECT_ADMIN],
        ];
    }

    private function buildRequestWithParsedBody(array $parsed_body): ServerRequestInterface
    {
        return (new NullServerRequest())->withParsedBody($parsed_body);
    }

    public function testItBuildsInputsForAPersonalFork(): void
    {
        $result = $this->builder->buildForPersonalFork(
            $this->buildRequestWithParsedBody([
                'path' => 'my-forks/',
                'repos' => '2,3,7',
                'repo_access' => $this->permissions,
            ]),
            $this->user
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals('2,3,7', $result->value->repositories_ids);
        self::assertEquals('my-forks/', $result->value->fork_path);
        self::assertEquals($this->permissions, $result->value->permissions);
    }

    public function testItReturnsAnErrorWhenThereAreNoRepositoriesToForkForPersonalFork(): void
    {
        $result = $this->builder->buildForPersonalFork(
            $this->buildRequestWithParsedBody([
                'path' => 'my-forks/',
                'repos' => '',
                'repo_access' => $this->permissions,
            ]),
            $this->user
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testItReturnsAnErrorWhenPathContainsDoubleDotsForPersonalFork(): void
    {
        $result = $this->builder->buildForPersonalFork(
            $this->buildRequestWithParsedBody([
                'path' => '../my-forks/',
                'repos' => '2',
                'repo_access' => $this->permissions,
            ]),
            $this->user
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ForkPathContainsDoubleDotsFault::class, $result->error);
    }

    public function testItBuildsInputsForACrossProjectsFork(): void
    {
        $result = $this->builder->buildForCrossProjectsFork(
            $this->buildRequestWithParsedBody([
                'to_project' => '115',
                'repos' => '2,3,7',
                'repo_access' => $this->permissions,
            ]),
        );

        self::assertTrue(Result::isOk($result));
        self::assertEquals('2,3,7', $result->value->repositories_ids);
        self::assertEquals('115', $result->value->destination_project_id);
        self::assertEquals($this->permissions, $result->value->permissions);
    }

    public function testItReturnsAnErrorWhenThereAreNoRepositoriesToForkForCrossProjectsFork(): void
    {
        $result = $this->builder->buildForCrossProjectsFork(
            $this->buildRequestWithParsedBody([
                'to_project' => '115',
                'repos' => '',
                'repo_access' => $this->permissions,
            ]),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testItReturnsAnErrorWhenDestinationProjectIdIsEmptyForCrossProjectsFork(): void
    {
        $result = $this->builder->buildForCrossProjectsFork(
            $this->buildRequestWithParsedBody([
                'to_project' => '',
                'repos' => '5',
                'repo_access' => $this->permissions,
            ]),
        );

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }
}
