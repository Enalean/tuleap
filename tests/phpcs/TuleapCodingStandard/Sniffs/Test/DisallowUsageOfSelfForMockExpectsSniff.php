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
 * When using a PHPUnit mock `$mock->expects(self::once())`, the constraint builder is not a static method.
 *
 * So you should use `$this->once()`. Same for all the other (never, exactly, atLeastOnce, ...)
 */
final class DisallowUsageOfSelfForMockExpectsSniff implements Sniff
{
    public function register(): array
    {
        return [T_SELF];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        if (! str_ends_with($phpcsFile->path, 'Test.php')) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $previous = $tokens[$stackPtr - 2]; // -1 is (
        if ($previous['type'] !== 'T_STRING' || $previous['content'] !== 'expects') {
            return;
        }

        $next = $tokens[$stackPtr + 1];
        if ($next['type'] !== 'T_DOUBLE_COLON') {
            return;
        }

        $phpcsFile->addFixableError('You should use $this-> and not self::', $stackPtr, 'DisallowUsageOfSelfForMockExpects');

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($stackPtr, '$this');
        $phpcsFile->fixer->replaceToken($stackPtr + 1, '->');
        $phpcsFile->fixer->endChangeset();
    }
}
