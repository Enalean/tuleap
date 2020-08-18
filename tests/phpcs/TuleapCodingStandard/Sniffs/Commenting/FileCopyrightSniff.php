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

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class FileCopyrightSniff implements Sniff
{
    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr)
    {
        $first_non_whitespace_instruction_pos = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, null, true);

        if (! is_int($first_non_whitespace_instruction_pos)) {
            $this->addErrorMissingCopyright($phpcsFile, $stackPtr);
            return $phpcsFile->numTokens + 1;
        }

        $tokens = $phpcsFile->getTokens();

        $first_non_whitespace_instruction      = $tokens[$first_non_whitespace_instruction_pos];
        $first_non_whitespace_instruction_code = $first_non_whitespace_instruction['code'];
        /** @psalm-suppress UndefinedConstant T_DOC_COMMENT_OPEN_TAG is created when the Tokens class is autoloaded, Psalm cannot know about it */
        if ($first_non_whitespace_instruction_code !== T_COMMENT && $first_non_whitespace_instruction_code !== T_DOC_COMMENT_OPEN_TAG) {
            $this->addErrorMissingCopyright($phpcsFile, $first_non_whitespace_instruction_pos);
            return $phpcsFile->numTokens + 1;
        }

        $first_non_whitespace_instruction_line = $tokens[$first_non_whitespace_instruction_pos]['line'];
        $open_tag_line                         = $tokens[$stackPtr]['line'];

        if (
            $first_non_whitespace_instruction_pos === ($stackPtr + 1) &&
            ($open_tag_line + 1) === $first_non_whitespace_instruction_line
        ) {
            return $phpcsFile->numTokens + 1;
        }

        $nb_empty_lines = $first_non_whitespace_instruction_pos - $stackPtr - 1;
        if ($nb_empty_lines > 0) {
            $fix = $phpcsFile->addFixableError(
                sprintf('Copyright block must be at the very beginning of the file, found %d empty lines', $nb_empty_lines),
                $first_non_whitespace_instruction_pos,
                'EmptyLinesBeforeCopyright'
            );
        } else {
            $fix = $phpcsFile->addFixableError(
                'Copyright block must not be on the same line than the PHP open tag',
                $first_non_whitespace_instruction_pos,
                'NoNewLineBetweenOpenTagAndCopyright'
            );
        }

        if (! $fix) {
            return $phpcsFile->numTokens + 1;
        }

        $fixer = $phpcsFile->fixer;
        $fixer->beginChangeset();
        $fixer->replaceToken($stackPtr, "<?php\n");
        for ($i = $stackPtr + 1; $i < $first_non_whitespace_instruction_pos; $i++) {
            $fixer->replaceToken($i, '');
        }
        $fixer->endChangeset();

        return $phpcsFile->numTokens + 1;
    }

    private function addErrorMissingCopyright(File $file, int $pointer): void
    {
        $file->addError(
            'You must add a copyright block at the beginning of the file',
            $pointer,
            'MissingCopyright'
        );
    }
}
