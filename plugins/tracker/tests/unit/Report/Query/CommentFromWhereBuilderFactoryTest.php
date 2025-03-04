<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tracker;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Artifact\Changeset\Comment\PrivateComment\PermissionChecker;
use function PHPUnit\Framework\assertInstanceOf;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CommentFromWhereBuilderFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var CommentFromWhereBuilderFactory
     */
    private $factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PermissionChecker
     */
    private $permission_checker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permission_checker = Mockery::mock(PermissionChecker::class);

        $this->factory = new CommentFromWhereBuilderFactory(
            $this->permission_checker
        );
    }

    public function testItBuildsAnObjectWithPrivateChecksIfNeeded(): void
    {
        $this->permission_checker->shouldReceive('privateCheckMustBeDoneForUser')->andReturnTrue();

        $builder = $this->factory->buildCommentFromWhereBuilderForTracker(
            UserTestBuilder::aUser()->build(),
            Mockery::mock(Tracker::class)
        );

        assertInstanceOf(CommentWithPrivateCheckFromWhereBuilder::class, $builder);
    }

    public function testItBuildsAnObjectWithoutPrivateChecksIfNotNeeded(): void
    {
        $this->permission_checker->shouldReceive('privateCheckMustBeDoneForUser')->andReturnFalse();

        $builder = $this->factory->buildCommentFromWhereBuilderForTracker(
            UserTestBuilder::aUser()->build(),
            Mockery::mock(Tracker::class)
        );

        assertInstanceOf(CommentWithoutPrivateCheckFromWhereBuilder::class, $builder);
    }
}
