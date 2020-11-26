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

namespace Tuleap\Gitlab\Repository\Webhook\PostPush;

use Psr\Log\LoggerInterface;
use Tuleap\Gitlab\Repository\Webhook\PostPush\Commits\CommitTuleapReferencesParser;

class PostPushWebhookActionProcessor
{
    /**
     * @var CommitTuleapReferencesParser
     */
    private $commit_tuleap_references_parser;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CommitTuleapReferencesParser $commit_tuleap_references_parser,
        LoggerInterface $logger
    ) {
        $this->commit_tuleap_references_parser = $commit_tuleap_references_parser;
        $this->logger                          = $logger;
    }

    public function process(PostPushWebhookData $webhook_data): void
    {
        foreach ($webhook_data->getCommits() as $commit_webhook_data) {
            $this->parseCommitReferences($commit_webhook_data);
        }
    }

    private function parseCommitReferences(PostPushCommitWebhookData $commit_webhook_data): void
    {
        $references_collection = $this->commit_tuleap_references_parser->extractCollectionOfTuleapReferences(
            $commit_webhook_data
        );

        $this->logger->info(count($references_collection->getTuleapReferences()) . " Tuleap references found in commit " . $commit_webhook_data->getSha1());
        foreach ($references_collection->getTuleapReferences() as $tuleap_reference) {
            $this->logger->info("Reference to Tuleap artifact #" . $tuleap_reference->getId() . " found.");
        }
    }
}
