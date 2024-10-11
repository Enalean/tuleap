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

namespace Tuleap\Git\Permissions;

use GitRepository;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\User\UserGroup\NameTranslator;

require_once __DIR__ . '/../../bootstrap.php';

class RegexpPermissionFilterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var RegexpPermissionFilter
     */
    private $permission_filter;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $permission_factory;

    /**
     * @var GitRepository
     */
    private $repository;

    /**
     * @var FineGrainedPermissionDestructor
     */
    private $permission_destructor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = \Mockery::spy(\GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturns(1);

        $this->permission_factory    = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class);
        $this->permission_destructor = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionDestructor::class);
        $this->permission_filter     = new RegexpPermissionFilter(
            $this->permission_factory,
            new PatternValidator(
                new FineGrainedPatternValidator(),
                \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRegexpValidator::class),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class)
            ),
            $this->permission_destructor,
            \Mockery::spy(\Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory::class)
        );
    }

    public function testItShouldKeepOnlyNonRegexpPattern(): void
    {
        $patterns = $this->buildPatterns();

        $this->permission_factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->andReturns($patterns);
        $this->permission_factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->andReturns([]);
        $this->permission_destructor->shouldReceive('deleteRepositoryPermissions')->times(16);

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
