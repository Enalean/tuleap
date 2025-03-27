<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Project;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CachedProjectPresentersBuilderTest extends TestCase
{
    public function testProjectPresentersAreRetrievedOnlyOnceForTheSameUser(): void
    {
        $builder = $this->createMock(ListOfProjectPresentersBuilder::class);
        $builder
            ->expects($this->once())
            ->method('getProjectPresenters')
            ->willReturn([
                $this->createMock(ProjectPresenter::class),
                $this->createMock(ProjectPresenter::class),
            ]);

        $cache = new CachedProjectPresentersBuilder($builder);

        $current_user = UserTestBuilder::buildWithDefaults();

        self::assertCount(2, $cache->getProjectPresenters($current_user));
        self::assertSame(
            $cache->getProjectPresenters($current_user),
            $cache->getProjectPresenters($current_user),
        );
    }

    public function testForTwoDifferentUsers(): void
    {
        $current_user = UserTestBuilder::aUser()->withId(101)->build();
        $another_user = UserTestBuilder::aUser()->withId(102)->build();

        $builder = $this->createMock(ListOfProjectPresentersBuilder::class);
        $builder
            ->expects(self::exactly(2))
            ->method('getProjectPresenters')
            ->willReturnCallback(
                fn (\PFUser $user): array => match ($user) {
                    $current_user => [
                        $this->createMock(ProjectPresenter::class),
                        $this->createMock(ProjectPresenter::class),
                    ],
                    $another_user => [
                        $this->createMock(ProjectPresenter::class),
                    ],
                }
            );

        $cache = new CachedProjectPresentersBuilder($builder);

        self::assertCount(2, $cache->getProjectPresenters($current_user));
        self::assertCount(1, $cache->getProjectPresenters($another_user));
    }
}
