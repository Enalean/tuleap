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
    public function extractCollectionOfTuleapReferences(
        string $message,
    ): WebhookTuleapReferenceCollection {
        $matches = [];
        $pattern = '/(' . ClosingKeyword::getKeywordsRegexpPart() . ')?(?:^|\s|[' . preg_quote('.,;:[](){}|\'"', '/') . '])tuleap-(\d+)/i';
        preg_match_all($pattern, $message, $matches);

        $parsed_tuleap_references = [];
        if (isset($matches[2])) {
            for ($match_index = 0; $match_index < count($matches[2]); $match_index++) {
                $close_artifact_keyword     = preg_replace('/\s+/', '', $matches[1][$match_index]);
                $artifact_id                = $matches[2][$match_index];
                $keyword                    = ClosingKeyword::fromString($close_artifact_keyword);
                $parsed_tuleap_references[] = new WebhookTuleapReference((int) $artifact_id, $keyword);
            }
        }

        sort($parsed_tuleap_references);

        return WebhookTuleapReferenceCollection::fromReferences(
            ...array_values(array_unique($parsed_tuleap_references))
        );
    }
}
