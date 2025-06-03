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
use Tuleap\Tracker\Semantic\Title\TrackerSemanticTitle;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CreationSemanticToCheckTest extends TestCase
{
    public function testItReturnsAnErrorIfTheCurrentSemanticIsNotSupported(): void
    {
        $current_semantic = 'hoho';
        $result           = CreationSemanticToCheck::fromREST($current_semantic);
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(SemanticNotSupportedFault::class, $result->error);
    }

    public function testItReturnsTheObjectIfTheCurrentSemanticIsSupported(): void
    {
        $current_semantic = 'title';
        $result           = CreationSemanticToCheck::fromREST($current_semantic);
        self::assertTrue(Result::isOk($result));
        self::assertSame(TrackerSemanticTitle::NAME, $result->value->semantic_to_check);
    }

    public function testItReturnsTrueIfTheCurrentSemanticIsTheSemanticTitle(): void
    {
        $current_semantic = 'title';
        $result           = CreationSemanticToCheck::fromREST($current_semantic);
        self::assertTrue($result->value->isSemanticTitle());
    }
}
