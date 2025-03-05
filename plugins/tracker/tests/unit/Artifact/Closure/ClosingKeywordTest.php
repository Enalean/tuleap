<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Closure;

use PHPUnit\Framework\Attributes\DataProvider;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ClosingKeywordTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public static function provideKeywordVariants(): array
    {
        return [
            ['resolve'],
            ['resolves'],
            ['resolved'],
            ['resolving'],
            ['close'],
            ['closes'],
            ['closed'],
            ['closing'],
            ['fix'],
            ['fixes'],
            ['fixed'],
            ['fixing'],
            ['implement'],
            ['implements'],
            ['implemented'],
            ['implementing'],
        ];
    }

    public static function provideMixedCaseKeywordVariants(): array
    {
        return [
            ['resOLving'],
            ['Resolves'],
            ['closINg'],
            ['Closes'],
            ['fIx'],
            ['Fixes'],
            ['implemenTS'],
            ['Implemented'],
        ];
    }

    #[DataProvider('provideKeywordVariants')]
    #[DataProvider('provideMixedCaseKeywordVariants')]
    public function testItAcceptsResolvesVariants(string $potential_keyword): void
    {
        $keyword = ClosingKeyword::fromString($potential_keyword);
        self::assertNotNull($keyword);
    }

    public function testItRejectsOtherKeywords(): void
    {
        self::assertNull(ClosingKeyword::fromString('blabla'));
    }

    #[DataProvider('provideKeywordVariants')]
    public function testItMatchesKeywordVariants(string $potential_keyword): void
    {
        preg_match('/' . ClosingKeyword::getKeywordsRegexpPart() . '/', $potential_keyword, $matches);
        self::assertCount(1, $matches);
    }

    public static function provideMatch(): array
    {
        return [
            'It matches resolves keyword to first argument'    => ['resolves', 'resolves match'],
            'It matches closes keyword to second argument'     => ['closes', 'closes match'],
            'It matches fixes keyword to third argument'       => ['fixes', 'fixes match'],
            'It matches implements keyword to fourth argument' => ['implements', 'implements match'],
        ];
    }

    #[DataProvider('provideMatch')]
    public function testItMatchesKeywordToArgument(string $keyword, string $expected_match): void
    {
        $keyword = ClosingKeyword::fromString($keyword);
        self::assertNotNull($keyword);
        self::assertSame(
            $expected_match,
            $keyword->match('resolves match', 'closes match', 'fixes match', 'implements match')
        );
    }
}
