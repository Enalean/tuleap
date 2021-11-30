<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Repository\Webhook;

class WebhookTuleapReferencesParser
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

    public const  RESOLVES_KEYWORD   = "resolves";
    public const  CLOSES_KEYWORD     = "closes";
    public const  FIXES_KEYWORD      = "fixes";
    public const  IMPLEMENTS_KEYWORD = "implements";

    public function extractCollectionOfTuleapReferences(
        string $message,
    ): WebhookTuleapReferenceCollection {
        $matches = [];
        $pattern = '/(' . $this->buildClosureKeywordsRegexpPart() . ')?(?:^|\s|[' . preg_quote('.,;:[](){}|\'"', '/') . '])tuleap-(\d+)/i';
        preg_match_all($pattern, $message, $matches);

        $parsed_tuleap_references = [];
        if (isset($matches[2])) {
            for ($match_index = 0; $match_index < count($matches[2]); $match_index++) {
                $close_artifact_keyword = preg_replace('/\s+/', '', $matches[1][$match_index]);
                $artifact_id            = $matches[2][$match_index];
                if (in_array(strtolower($close_artifact_keyword), self::RESOLVE_KEYWORDS) === true) {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, self::RESOLVES_KEYWORD);
                } elseif (in_array(strtolower($close_artifact_keyword), self::CLOSE_KEYWORDS) === true) {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, self::CLOSES_KEYWORD);
                } elseif (in_array(strtolower($close_artifact_keyword), self::FIX_KEYWORDS) === true) {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, self::FIXES_KEYWORD);
                } elseif (in_array(strtolower($close_artifact_keyword), self::IMPLEMENT_KEYWORDS) === true) {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, self::IMPLEMENTS_KEYWORD);
                } else {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, null);
                }
            }
        }

        sort($parsed_tuleap_references);

        return WebhookTuleapReferenceCollection::fromReferences(
            ...array_values(array_unique($parsed_tuleap_references))
        );
    }

    private function buildClosureKeywordsRegexpPart(): string
    {
        $all_keywords = array_merge(
            self::CLOSE_KEYWORDS,
            self::FIX_KEYWORDS,
            self::RESOLVE_KEYWORDS,
            self::IMPLEMENT_KEYWORDS,
        );

        $regexp = implode(
            '|',
            array_map('preg_quote', $all_keywords)
        );

        return '(?:' . $regexp . ')\s?';
    }
}
