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

namespace Tuleap\Gitlab\Repository\Webhook;

/**
 * I hold a keyword to close an Artifact. Multiple variants of each keyword are supported.
 * @psalm-immutable
 */
final class ClosingKeyword
{
    private const RESOLVE_KEYWORDS = [
        'resolve',
        'resolves',
        'resolved',
        'resolving',
    ];

    private const CLOSE_KEYWORDS = [
        'close',
        'closes',
        'closed',
        'closing',
    ];

    private const FIX_KEYWORDS = [
        'fix',
        'fixes',
        'fixed',
        'fixing',
    ];

    private const IMPLEMENT_KEYWORDS = [
        'implement',
        'implements',
        'implemented',
        'implementing',
    ];

    private const RESOLVES_KEYWORD   = 'resolves';
    private const CLOSES_KEYWORD     = 'closes';
    private const FIXES_KEYWORD      = 'fixes';
    private const IMPLEMENTS_KEYWORD = 'implements';

    private function __construct(private string $keyword)
    {
    }

    public static function fromString(string $potential_keyword): ?self
    {
        $lowercase_potential_keyword = strtolower($potential_keyword);
        if (in_array($lowercase_potential_keyword, self::RESOLVE_KEYWORDS, true) === true) {
            return self::buildResolves();
        }
        if (in_array($lowercase_potential_keyword, self::CLOSE_KEYWORDS, true) === true) {
            return self::buildCloses();
        }
        if (in_array($lowercase_potential_keyword, self::FIX_KEYWORDS, true) === true) {
            return self::buildFixes();
        }
        if (in_array($lowercase_potential_keyword, self::IMPLEMENT_KEYWORDS, true) === true) {
            return self::buildImplements();
        }
        return null;
    }

    public static function buildResolves(): self
    {
        return new self(self::RESOLVES_KEYWORD);
    }

    public static function buildCloses(): self
    {
        return new self(self::CLOSES_KEYWORD);
    }

    public static function buildFixes(): self
    {
        return new self(self::FIXES_KEYWORD);
    }

    public static function buildImplements(): self
    {
        return new self(self::IMPLEMENTS_KEYWORD);
    }

    public static function getKeywordsRegexpPart(): string
    {
        $all_keywords = array_merge(
            self::CLOSE_KEYWORDS,
            self::FIX_KEYWORDS,
            self::RESOLVE_KEYWORDS,
            self::IMPLEMENT_KEYWORDS,
        );

        $regexp = implode('|', array_map('preg_quote', $all_keywords));

        return '(?:' . $regexp . ')\s?';
    }

    /**
     * Matches the keyword "type" to the arguments.
     * If the keyword is "resolves", it returns $resolves_match.
     * If the keyword is "closes", it returns $closes_match.
     * If the keyword is "fixes", it returns $fixes_match.
     * If the keyword is "implements", it returns $implements_match
     */
    public function match(
        string $resolves_match,
        string $closes_match,
        string $fixes_match,
        string $implements_match,
    ): string {
        return match (true) {
            $this->keyword === self::CLOSES_KEYWORD => $closes_match,
            $this->keyword === self::FIXES_KEYWORD => $fixes_match,
            $this->keyword === self::IMPLEMENTS_KEYWORD => $implements_match,
            default => $resolves_match,
        };
    }
}
