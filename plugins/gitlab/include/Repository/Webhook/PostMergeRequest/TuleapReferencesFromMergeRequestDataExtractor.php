<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Repository\Webhook\PostMergeRequest;

use Tuleap\Gitlab\Repository\Webhook\PostPush\Branch\BranchNameTuleapReferenceParser;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferenceCollection;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReferencesParser;

class TuleapReferencesFromMergeRequestDataExtractor
{
    private WebhookTuleapReferencesParser $reference_parser;
    private BranchNameTuleapReferenceParser $branch_name_tuleap_reference_parser;

    public function __construct(
        WebhookTuleapReferencesParser $reference_parser,
        BranchNameTuleapReferenceParser $branch_name_tuleap_reference_parser,
    ) {
        $this->reference_parser                    = $reference_parser;
        $this->branch_name_tuleap_reference_parser = $branch_name_tuleap_reference_parser;
    }

    public function extract(string $title, string $description, ?string $branch_source): WebhookTuleapReferenceCollection
    {
        return WebhookTuleapReferenceCollection::aggregateCollections(
            $this->reference_parser->extractCollectionOfTuleapReferences(
                $title . " " . $description
            ),
            $this->extractFromBranchSource($branch_source),
        );
    }

    private function extractFromBranchSource(?string $branch_source): WebhookTuleapReferenceCollection
    {
        if ($branch_source === null) {
            return WebhookTuleapReferenceCollection::empty();
        }

        $extracted_reference = $this->branch_name_tuleap_reference_parser->extractTuleapReferenceFromBranchName($branch_source);
        if ($extracted_reference === null) {
            return WebhookTuleapReferenceCollection::empty();
        }

        return WebhookTuleapReferenceCollection::fromReferences($extracted_reference);
    }
}
