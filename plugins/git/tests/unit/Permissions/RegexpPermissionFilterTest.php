<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use GitRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\User\UserGroup\NameTranslator;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class RegexpPermissionFilterTest extends TestCase
{
    private RegexpPermissionFilter $permission_filter;
    private FineGrainedPermissionFactory&MockObject $permission_factory;
    private GitRepository $repository;
    private FineGrainedPermissionDestructor&MockObject $permission_destructor;

    protected function setUp(): void
    {
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1)->build();

        $this->permission_factory    = $this->createMock(FineGrainedPermissionFactory::class);
        $this->permission_destructor = $this->createMock(FineGrainedPermissionDestructor::class);
        $this->permission_filter     = new RegexpPermissionFilter(
            $this->permission_factory,
            new PatternValidator(
                new FineGrainedPatternValidator(),
                $this->createMock(FineGrainedRegexpValidator::class),
                $this->createMock(RegexpFineGrainedRetriever::class)
            ),
            $this->permission_destructor,
            $this->createMock(DefaultFineGrainedPermissionFactory::class)
        );
    }

    public function testItShouldKeepOnlyNonRegexpPattern(): void
    {
        $patterns = $this->buildPatterns();

        $this->permission_factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn($patterns);
        $this->permission_factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn([]);
        $this->permission_destructor->expects($this->exactly(16))->method('deleteRepositoryPermissions');

        $this->permission_filter->filterNonRegexpPermissions($this->repository);
    }

    private function buildPatterns(): array
    {
        $patterns = [
            '*',
            '/*',
            'master',
            'master*',
            'master/*',
            'master/*/*',
            'master/dev',
            'master/dev*',
            'master*/dev',
            '',
            'master*[dev',
            'master dev',
            'master?dev',

            "master\n",
            "master\r",
            "master\n\r",
            "master\ndev",
            "\n",
            "\v",
            "\f",
        ];

        $built_pattern = [];

        foreach ($patterns as $key => $pattern) {
            $built_pattern[] = new FineGrainedPermission(
                $key,
                $this->repository->getId(),
                $pattern,
                [NameTranslator::PROJECT_ADMINS],
                [NameTranslator::PROJECT_MEMBERS]
            );
        }

        return $built_pattern;
    }
}
