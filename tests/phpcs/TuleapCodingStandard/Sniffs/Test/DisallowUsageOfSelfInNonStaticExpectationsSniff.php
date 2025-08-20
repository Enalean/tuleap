<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace TuleapCodingStandard\Sniffs\Test;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * When using a PHPUnit expectation for Exceptions (`expectException()`, `expectExceptionCode()`, etc.),
 * those expectation functions are not static methods. You should use `$this->expectException()`
 */
final class DisallowUsageOfSelfInNonStaticExpectationsSniff implements Sniff
{
    /** @var list<string> */
    private const array NOT_STATIC_EXPECTATIONS = ['expectException', 'expectExceptionMessage', 'expectExceptionCode'];

    #[\Override]
    public function register(): array
    {
        return [T_SELF];
    }

    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (! str_ends_with($phpcsFile->path, 'Test.php')) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $next = $tokens[$stackPtr + 1];
        if ($next['type'] !== 'T_DOUBLE_COLON') {
            return;
        }

        $expectation = $tokens[$stackPtr + 2];
        if (
            $expectation['type'] !== 'T_STRING'
            || ! in_array($expectation['content'], self::NOT_STATIC_EXPECTATIONS, true)
        ) {
            return;
        }
        $phpcsFile->addFixableError(
            'You should use $this-> and not self:: for non-static expectations.',
            $stackPtr,
            'DisallowUsageOfSelfInNonStaticExpectations'
        );

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($stackPtr, '$this');
        $phpcsFile->fixer->replaceToken($stackPtr + 1, '->');
        $phpcsFile->fixer->endChangeset();
    }
}
