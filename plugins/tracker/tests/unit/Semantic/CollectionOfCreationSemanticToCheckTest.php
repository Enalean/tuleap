<?php
/**
 * Copyright (c) Enalean, 2023-present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic;

use Tuleap\NeverThrow\Result;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectionOfCreationSemanticToCheckTest extends TestCase
{
    public function testItReturnsAnErrorIfSemanticIsNotSupported(): void
    {
        $semantics = ['status', 'hehe'];
        $result    = CollectionOfCreationSemanticToCheck::fromREST($semantics);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(SemanticNotSupportedFault::class, $result->error);
    }

    public function testItReturnsTheCollectionIfTheSemanticsAreSupported(): void
    {
        $semantics = [];
        $result    = CollectionOfCreationSemanticToCheck::fromREST($semantics);
        self::assertTrue(Result::isOk($result));
        self::assertSame([], $result->value->semantics);
    }
}
