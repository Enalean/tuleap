<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Core;

use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const int PROJECT_ID = 130;
    private ProjectByIDFactoryStub $project_factory;
    private \Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->project         = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->project_factory = ProjectByIDFactoryStub::buildWith($this->project);
    }

    private function retrieveProject(): Ok|Err
    {
        $retriever = new ProjectRetriever($this->project_factory);
        return $retriever->retrieveProject(self::PROJECT_ID);
    }

    public function testItReturnsProject(): void
    {
        $result = $this->retrieveProject();
        self::assertTrue(Result::isOk($result));
        self::assertSame($this->project, $result->value);
    }

    public function testItReturnsFaultWhenProjectIsNotFound(): void
    {
        $this->project_factory = ProjectByIDFactoryStub::buildWithoutProject();

        $result = $this->retrieveProject();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(ProjectNotFoundFault::class, $result->error);
    }
}
