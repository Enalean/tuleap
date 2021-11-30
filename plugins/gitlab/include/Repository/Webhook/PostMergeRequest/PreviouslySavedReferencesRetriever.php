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

use Tuleap\Gitlab\Reference\TuleapReferencedArtifactNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceNotFoundException;
use Tuleap\Gitlab\Reference\TuleapReferenceRetriever;
use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\WebhookTuleapReference;

class PreviouslySavedReferencesRetriever
{
    /**
     * @var TuleapReferenceRetriever
     */
    private $tuleap_reference_retriever;
    /**
     * @var MergeRequestTuleapReferenceDao
     */
    private $merge_request_reference_dao;
    /**
     * @var TuleapReferencesFromMergeRequestDataExtractor
     */
    private $references_from_merge_request_data_extractor;

    public function __construct(
        TuleapReferencesFromMergeRequestDataExtractor $references_from_merge_request_data_extractor,
        TuleapReferenceRetriever $tuleap_reference_retriever,
        MergeRequestTuleapReferenceDao $merge_request_reference_dao,
    ) {
        $this->references_from_merge_request_data_extractor = $references_from_merge_request_data_extractor;
        $this->tuleap_reference_retriever                   = $tuleap_reference_retriever;
        $this->merge_request_reference_dao                  = $merge_request_reference_dao;
    }

    /**
     * @return WebhookTuleapReference[]
     */
    public function retrievePreviousReferences(
        PostMergeRequestWebhookData $webhook_data,
        GitlabRepositoryIntegration $gitlab_repository_integration,
    ): array {
        $previously_saved_merge_request_row = $this->merge_request_reference_dao->searchMergeRequestInRepositoryWithId(
            $gitlab_repository_integration->getId(),
            $webhook_data->getMergeRequestId()
        );
        if (! $previously_saved_merge_request_row) {
            return [];
        }

        $references_collection = $this->references_from_merge_request_data_extractor->extract(
            $previously_saved_merge_request_row['title'],
            $previously_saved_merge_request_row['description'],
            $previously_saved_merge_request_row['source_branch']
        );

        $good_references = [];
        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            try {
                $this->tuleap_reference_retriever->retrieveTuleapReference(
                    $tuleap_reference->getId()
                );

                $good_references[] = $tuleap_reference;
            } catch (TuleapReferencedArtifactNotFoundException | TuleapReferenceNotFoundException $reference_exception) {
                // ignore errors for old merge request data
            }
        }

        return $good_references;
    }
}
