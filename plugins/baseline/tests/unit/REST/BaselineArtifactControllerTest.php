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

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Tuleap\Baseline\Domain\BaselineArtifactNotFoundException;
use Tuleap\Baseline\Domain\BaselineArtifactService;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\Baseline\Support\CurrentUserContext;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterParser;

class BaselineArtifactControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use CurrentUserContext;

    /** @var BaselineArtifactController */
    private $controller;

    /** @var BaselineRepository|MockInterface */
    private $baseline_repository;

    /** @var BaselineArtifactService|MockInterface */
    private $baseline_artifact_service;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /** @var LoggerInterface|MockInterface */
    private $logger;

    /** @before */
    public function createInstance()
    {
        $this->baseline_repository       = Mockery::mock(BaselineRepository::class);
        $this->baseline_artifact_service = Mockery::mock(BaselineArtifactService::class);
        $this->current_user_provider     = Mockery::mock(CurrentUserProvider::class);
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($this->current_user)
            ->byDefault();
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->controller = new BaselineArtifactController(
            $this->baseline_repository,
            $this->baseline_artifact_service,
            $this->current_user_provider,
            new QueryParameterParser(new JsonDecoder()),
            $this->logger
        );
    }

    public function testGetThrows404WhenNoArtifactFound()
    {
        $this->expectException(NotFoundRestException::class);

        $this->baseline_repository
            ->shouldReceive('findById')
            ->andReturn(BaselineFactory::one()->build());

        $this->baseline_artifact_service
            ->shouldReceive('findByBaselineAndIds')
            ->andThrow(new BaselineArtifactNotFoundException());

        $this->controller->get(1, '{"ids": [1,2,3]}');
    }
}
