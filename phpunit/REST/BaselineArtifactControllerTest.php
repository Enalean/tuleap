<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Baseline\BaselineArtifactNotFoundException;
use Tuleap\Baseline\BaselineArtifactService;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\GlobalLanguageMock;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterParser;
use Tuleap\REST\RESTLogger;

class BaselineArtifactControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /** @var BaselineArtifactController */
    private $controller;

    /** @var BaselineService|MockInterface */
    private $baseline_service;

    /** @var BaselineArtifactService|MockInterface */
    private $baseline_artifact_service;

    /** @var CurrentUserProvider|MockInterface */
    private $current_user_provider;

    /** @var RESTLogger|MockInterface */
    private $logger;

    /** @var PFUser */
    private $current_user;

    /** @before */
    public function createInstance()
    {
        $this->current_user = new PFUser();

        $this->baseline_service          = Mockery::mock(BaselineService::class);
        $this->baseline_artifact_service = Mockery::mock(BaselineArtifactService::class);
        $this->current_user_provider     = Mockery::mock(CurrentUserProvider::class);
        $this->current_user_provider
            ->shouldReceive('getUser')
            ->andReturn($this->current_user)
            ->byDefault();
        $this->logger = Mockery::mock(RESTLogger::class);

        $this->controller = new BaselineArtifactController(
            $this->baseline_service,
            $this->baseline_artifact_service,
            $this->current_user_provider,
            new QueryParameterParser(new JsonDecoder()),
            $this->logger
        );
    }

    public function testGetThrows404WhenNoArtifactFound()
    {
        $this->expectException(NotFoundRestException::class);

        $this->baseline_service
            ->shouldReceive('findById')
            ->andReturn(BaselineFactory::one()->build());

        $this->baseline_artifact_service
            ->shouldReceive('findByBaselineAndIds')
            ->andThrow(new BaselineArtifactNotFoundException());

        $this->controller->get(1, '{"ids": [1,2,3]}');
    }
}
