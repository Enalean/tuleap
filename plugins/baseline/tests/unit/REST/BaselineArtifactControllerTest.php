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

use Psr\Log\NullLogger;
use Tuleap\Baseline\Adapter\UserProxy;
use Tuleap\Baseline\Domain\BaselineArtifactNotFoundException;
use Tuleap\Baseline\Domain\BaselineArtifactService;
use Tuleap\Baseline\Domain\BaselineRepository;
use Tuleap\Baseline\Domain\CurrentUserProvider;
use Tuleap\Baseline\Factory\BaselineFactory;
use Tuleap\Baseline\REST\Exception\NotFoundRestException;
use Tuleap\REST\JsonDecoder;
use Tuleap\REST\QueryParameterParser;
use Tuleap\Test\Builders\UserTestBuilder;

final class BaselineArtifactControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BaselineArtifactController $controller;

    /** @var BaselineRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_repository;

    /** @var BaselineArtifactService&\PHPUnit\Framework\MockObject\MockObject */
    private $baseline_artifact_service;

    /** @var CurrentUserProvider&\PHPUnit\Framework\MockObject\MockObject */
    private $current_user_provider;

    private UserProxy $current_user;

    /** @before */
    public function createInstance(): void
    {
        $this->current_user = UserProxy::fromUser(UserTestBuilder::aUser()->build());

        $this->baseline_repository       = $this->createMock(BaselineRepository::class);
        $this->baseline_artifact_service = $this->createMock(BaselineArtifactService::class);
        $this->current_user_provider     = $this->createMock(CurrentUserProvider::class);

        $this->current_user_provider
            ->method('getUser')
            ->willReturn($this->current_user);

        $this->controller = new BaselineArtifactController(
            $this->baseline_repository,
            $this->baseline_artifact_service,
            $this->current_user_provider,
            new QueryParameterParser(new JsonDecoder()),
            new NullLogger(),
        );
    }

    public function testGetThrows404WhenNoArtifactFound(): void
    {
        $this->expectException(NotFoundRestException::class);

        $this->baseline_repository
            ->method('findById')
            ->willReturn(BaselineFactory::one()->build());

        $this->baseline_artifact_service
            ->method('findByBaselineAndIds')
            ->willThrowException(new BaselineArtifactNotFoundException());

        $this->controller->get(1, '{"ids": [1,2,3]}');
    }
}
