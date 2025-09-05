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
use PHP_CodeSniffer\Util\Tokens;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;

final class EnforcePHPUnitAttributesSniff implements Sniff
{
    #[\Override]
    public function register(): array
    {
        return [T_CLASS];
    }

    #[\Override]
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (! str_ends_with($phpcsFile->path, 'Test.php')) {
            return;
        }

        $tokens = $phpcsFile->getTokens();

        $attribute_pointer = $stackPtr - 1;

        while ($previous_pointer = $phpcsFile->findPrevious(T_ATTRIBUTE, $attribute_pointer - 1)) {
            $attribute_pointer = $previous_pointer;
            $token             = $tokens[$previous_pointer];

            $attribute_name = '';
            for ($i = $token['attribute_opener'] + 1; $i <= $token['attribute_closer'] - 1; $i++) {
                if (in_array($tokens[$i]['code'], Tokens::$emptyTokens, true)) {
                    continue;
                }

                $attribute_name .= $tokens[$i]['content'];
            }

            if (
                $attribute_name === '\\' . DisableReturnValueGenerationForTestDoubles::class ||
                $attribute_name === basename(str_replace('\\', '/', DisableReturnValueGenerationForTestDoubles::class))
            ) {
                return;
            }
        }

        $fix = $phpcsFile->addFixableError(
            'Test cases must disable return value generation for test doubles',
            $stackPtr,
            'TestCasesMustDisableReturnValueGenerationForTestDoubles'
        );

        if (! $fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->addContentBefore(
            $phpcsFile->findStartOfStatement($stackPtr),
            '#[\\' . DisableReturnValueGenerationForTestDoubles::class . "]\n"
        );
        $phpcsFile->fixer->endChangeset();
    }
}
