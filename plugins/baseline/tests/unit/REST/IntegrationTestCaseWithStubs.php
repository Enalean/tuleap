<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

require_once __DIR__ . '/../bootstrap.php';

use DI\Container;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Tuleap\Baseline\Domain\Authorizations;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\Clock;
use Tuleap\Baseline\Domain\ComparisonRepository;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Domain\ProjectRepository;
use Tuleap\Baseline\Stub\BaselineArtifactRepositoryStub;
use Tuleap\Baseline\Stub\BaselineRepositoryStub;
use Tuleap\Baseline\Stub\ComparisonRepositoryStub;
use Tuleap\Baseline\Stub\CurrentUserProviderStub;
use Tuleap\Baseline\Stub\FrozenClock;
use Tuleap\Baseline\Stub\FullAccessAuthorizationsStub;
use Tuleap\Baseline\Stub\ProjectRepositoryStub;
use Tuleap\Baseline\Support\ContainerBuilderFactory;
use Tuleap\Baseline\Support\CurrentUserContext;

/**
 * Useful class to write integration test with full container
 * where adapters are replaced by stubs, which ease manipulation
 * (compared to mocks).
 */
abstract class IntegrationTestCaseWithStubs extends \Tuleap\Test\PHPUnit\TestCase
{
    use CurrentUserContext;

    /** @var Container */
    private $container;

    /** @var BaselineArtifactRepositoryStub */
    protected $baseline_artifact_repository;

    /** @var BaselineRepositoryStub */
    protected $baseline_repository;

    /** @var ComparisonRepositoryStub */
    protected $comparison_repository;

    /** @var ProjectRepositoryStub */
    protected $project_repository;

    /** @var CurrentUserProviderStub */
    protected $current_user_provider;

    /** @var FrozenClock */
    protected $clock;

    /** @before */
    public function createContainer(): void
    {
        if ($this->container === null) {
            $this->container = $this->buildContainer();
        }
    }

    protected function getContainer(): Container
    {
        if ($this->container === null) {
            $this->container = $this->buildContainer();
        }
        return $this->container;
    }

    private function buildContainer(): Container
    {
        $this->clock                        = new FrozenClock();
        $this->baseline_repository          = new BaselineRepositoryStub();
        $this->comparison_repository        = new ComparisonRepositoryStub($this->clock);
        $this->project_repository           = new ProjectRepositoryStub();
        $this->baseline_artifact_repository = new BaselineArtifactRepositoryStub($this->clock);
        $this->current_user_provider        = new CurrentUserProviderStub();

        return ContainerBuilderFactory::create()
            ->addDefinitions(
                [
                    BaselineRepository::class         => $this->baseline_repository,
                    ComparisonRepository::class       => $this->comparison_repository,
                    ProjectRepository::class          => $this->project_repository,
                    BaselineArtifactRepository::class => $this->baseline_artifact_repository,
                    CurrentUserProvider::class        => $this->current_user_provider,
                    Authorizations::class             => new FullAccessAuthorizationsStub(),
                    Clock::class                      => $this->clock,
                    LoggerInterface::class            => new NullLogger(),
                ]
            )
            ->build();
    }
}
