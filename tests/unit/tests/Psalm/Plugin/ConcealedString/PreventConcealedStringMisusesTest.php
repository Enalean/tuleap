<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Test\Psalm\Plugin\ConcealedString;

use PhpParser\Node\Expr\MethodCall;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\MethodReturnTypeProviderEvent;
use Psalm\StatementsSource;
use Tuleap\Cryptography\ConcealedString;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PreventConcealedStringMisusesTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected function tearDown(): void
    {
        IssueBuffer::clear();
    }

    public function testMarksUnwrappedConcealedStringWithASpecialType(): void
    {
        self::assertSame([ConcealedString::class], PreventConcealedStringMisuses::getClassLikeNames());
        $return_type = PreventConcealedStringMisuses::getMethodReturnType(
            new MethodReturnTypeProviderEvent(
                $this->createMock(StatementsSource::class),
                ConcealedString::class,
                'getstring',
                $this->createStub(MethodCall::class),
                new Context(),
                $this->createMock(CodeLocation::class)
            )
        );

        $types = $return_type->getAtomicTypes();
        self::assertTrue(isset($types['string']));
        self::assertInstanceOf(TUnwrappedConcealedString::class, $types['string']);
    }
}
