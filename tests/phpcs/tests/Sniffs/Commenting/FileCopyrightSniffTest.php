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
 */

declare(strict_types=1);

namespace TuleapCodingStandard\Sniffs\Commenting;

use SlevomatCodingStandard\Sniffs\TestCase;

final class FileCopyrightSniffTest extends TestCase
{
    /**
     * @dataProvider providerValidFiles
     */
    public function testNoErrorWhenCopyrightBlockIsPresent(string $path): void
    {
        $report = self::checkFile($path);

        self::assertNoSniffErrorInFile($report);
    }

    public function providerValidFiles(): array
    {
        return [
            [__DIR__ . '/_fixtures/FileCopyright/Valid/OpenTagAndCopyrightDocCommentAtTheBeginning.php'],
            [__DIR__ . '/_fixtures/FileCopyright/Valid/OpenTagAndCopyrightCommentAtTheBeginning.php'],
            [__DIR__ . '/_fixtures/FileCopyright/Valid/OpenTagAndCopyrightSimpleCommentAtTheBeginning.php'],
            [__DIR__ . '/_fixtures/FileCopyright/Valid/ContentBeforeOpenTag.php'],
            [__DIR__ . '/_fixtures/FileCopyright/Valid/MultipleOpenTag.php'],
        ];
    }

    public function testCodeBeforeCopyright(): void
    {
        $report = self::checkFile(__DIR__ . '/_fixtures/FileCopyright/Invalid/CodeBeforeCopyright.php');

        $this->assertSame(1, $report->getErrorCount());
        self::assertSniffError($report, 3, 'MissingCopyright');
    }

    public function testOnlyOpenTag(): void
    {
        $report = self::checkFile(__DIR__ . '/_fixtures/FileCopyright/Invalid/OnlyPHPOpenTag.php');

        $this->assertSame(1, $report->getErrorCount());
        self::assertSniffError($report, 1, 'MissingCopyright');
    }

    public function testEmptyLinesBeforeCopyright(): void
    {
        $report = self::checkFile(__DIR__ . '/_fixtures/FileCopyright/Invalid/EmptyLinesBeforeCopyRight.php');

        $this->assertSame(1, $report->getErrorCount());
        self::assertSniffError($report, 5, 'EmptyLinesBeforeCopyright');
        self::assertAllFixedInFile($report);
    }

    public function testEmptyLinesBeforeCopyrightAndMultipleOpenTags(): void
    {
        $report = self::checkFile(__DIR__ . '/_fixtures/FileCopyright/Invalid/EmptyLinesBeforeCopyRightAndMultipleOpenTags.php');

        $this->assertSame(1, $report->getErrorCount());
        self::assertSniffError($report, 5, 'EmptyLinesBeforeCopyright');
        self::assertAllFixedInFile($report);
    }

    public function testSpacesAfterCopyright(): void
    {
        $report = self::checkFile(__DIR__ . '/_fixtures/FileCopyright/Invalid/SpacesAfterOpenTag.php');

        $this->assertSame(1, $report->getErrorCount());
        self::assertSniffError($report, 3, 'EmptyLinesBeforeCopyright');
        self::assertAllFixedInFile($report);
    }

    public function testCopyrightOnTheSameLineThanOpenTag(): void
    {
        $report = self::checkFile(__DIR__ . '/_fixtures/FileCopyright/Invalid/CopyrightOnTheSameLineThanOpenTag.php');

        $this->assertSame(1, $report->getErrorCount());
        self::assertSniffError($report, 1, 'NoNewLineBetweenOpenTagAndCopyright');
        self::assertAllFixedInFile($report);
    }
}
