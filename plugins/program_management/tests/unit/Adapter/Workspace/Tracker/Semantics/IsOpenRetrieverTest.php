<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Semantics;

use Tuleap\ProgramManagement\Tests\Builder\FeatureIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Builder\UserStoryIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullArtifactStub;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Artifact\Artifact;

final class IsOpenRetrieverTest extends TestCase
{
    private static function isUserStoryOpen(Artifact $artifact): bool
    {
        $verifier = new IsOpenRetriever(RetrieveFullArtifactStub::withArtifact($artifact));
        return $verifier->isOpen(UserStoryIdentifierBuilder::withId(57));
    }

    private static function isFeatureOpen(Artifact $artifact): bool
    {
        $verifier = new IsOpenRetriever(RetrieveFullArtifactStub::withArtifact($artifact));
        return $verifier->isFeatureOpen(FeatureIdentifierBuilder::withId(58));
    }

    public static function dataProviderMethodUnderTest(): array
    {
        return [
            'User Story' => [[self::class, 'isUserStoryOpen']],
            'Feature' => [[self::class, 'isFeatureOpen']],
        ];
    }

    /**
     * @dataProvider dataProviderMethodUnderTest
     */
    public function testItReturnsTrue(callable $method_under_test): void
    {
        $artifact = $this->createConfiguredMock(Artifact::class, ['isOpen' => true]);
        self::assertTrue($method_under_test($artifact));
    }

    /**
     * @dataProvider dataProviderMethodUnderTest
     */
    public function testItReturnsFalse(callable $method_under_test): void
    {
        $artifact = $this->createConfiguredMock(Artifact::class, ['isOpen' => false]);
        self::assertFalse($method_under_test($artifact));
    }
}
