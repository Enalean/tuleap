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
    private const CLOSED_KEYWORDS_REGEX = "resolves\s?|closes\s?";
    public const  RESOLVES_KEYWORD      = "resolves";
    public const  CLOSES_KEYWORD        = "closes";

    public function extractCollectionOfTuleapReferences(
        string $message
    ): WebhookTuleapReferenceCollection {
        $matches = [];
        $pattern = '/(' . self::CLOSED_KEYWORDS_REGEX . ')?(?:^|\s|[' . preg_quote('.,;:[](){}|\'"', '/') . '])tuleap-(\d+)/i';
        preg_match_all($pattern, $message, $matches);

        $parsed_tuleap_references = [];
        if (isset($matches[2])) {
            for ($match_index = 0; $match_index < count($matches[2]); $match_index++) {
                $close_artifact_keyword = preg_replace('/\s+/', '', $matches[1][$match_index]);
                $artifact_id            = $matches[2][$match_index];
                if ($close_artifact_keyword === self::RESOLVES_KEYWORD) {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, self::RESOLVES_KEYWORD);
                } elseif ($close_artifact_keyword === self::CLOSES_KEYWORD) {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, self::CLOSES_KEYWORD);
                } else {
                    $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, null);
                }
            }
        }

        sort($parsed_tuleap_references);

        return new WebhookTuleapReferenceCollection(
            array_values(array_unique($parsed_tuleap_references))
        );
    }
}
