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

namespace Tuleap\Git\ForkRepositories\Permissions;

use CuyZ\Valinor\MapperBuilder;
use Tuleap\HTTPRequest;
use Tuleap\Git\ForkRepositories\ForkPathContainsDoubleDotsFault;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ForkRepositoriesFormInputsBuilderTest extends TestCase
{
    private HTTPRequest $request;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = new HTTPRequest();
    }

    /**
     * @return Ok<ForkRepositoriesFormInputs>|Err<Fault>
     */
    private function build(): Ok|Err
    {
        $builder = new ForkRepositoriesFormInputsBuilder(
            new MapperBuilder()->mapper(),
        );

        return $builder->fromRequest($this->request, UserTestBuilder::aUser()->withUserName('johndoe')->build());
    }

    public function testItBuildsFromRequest(): void
    {
        $this->request->set('repos', ['1', '2', '3']);
        $this->request->set('to_project', '101');
        $this->request->set('path', '/my-forks');
        $this->request->set('choose_destination', ForkType::PERSONAL->value);

        $result = $this->build();

        self::assertTrue(Result::isOk($result));
        self::assertSame($this->request->get('repos'), $result->value->repositories_ids);
        self::assertSame($this->request->get('to_project'), $result->value->destination_project_id);
        self::assertSame($this->request->get('path'), $result->value->path);
        self::assertSame($this->request->get('choose_destination'), $result->value->fork_type->value);
    }

    public function testItBuildsWithEmptyDestinationProject(): void
    {
        $this->request->set('repos', ['1', '2', '3']);
        $this->request->set('path', '/my-forks');
        $this->request->set('choose_destination', ForkType::PERSONAL->value);

        $result = $this->build();

        self::assertTrue(Result::isOk($result));
        self::assertNull($result->value->destination_project_id);
    }

    public function testItBuildsWithEmptyPath(): void
    {
        $this->request->set('repos', ['1', '2', '3']);
        $this->request->set('choose_destination', ForkType::PERSONAL->value);

        $result = $this->build();

        self::assertTrue(Result::isOk($result));
        self::assertSame('', $result->value->path);
    }

    public function testItReturnsAMissingRequiredParametersFaultWhenRepositoriesIdsAreMissing(): void
    {
        $this->request->set('repos', []);
        $this->request->set('choose_destination', ForkType::PERSONAL->value);

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testItReturnsAMissingRequiredParametersFaultWhenRepositoriesIdsAreEmpty(): void
    {
        $this->request->set('choose_destination', ForkType::PERSONAL->value);

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testItReturnsAMissingRequiredParametersFaultWhenForkTypeIsMissing(): void
    {
        $this->request->set('repos', ['1', '2', '3']);

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(MissingRequiredParametersFault::class, $result->error);
    }

    public function testItReturnsAForkPathContainsDoubleDotsFault(): void
    {
        $this->request->set('path', '../my-forks');
        $this->request->set('repos', ['1', '2', '3']);
        $this->request->set('choose_destination', ForkType::PERSONAL->value);

        $result = $this->build();

        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ForkPathContainsDoubleDotsFault::class, $result->error);
    }
}
