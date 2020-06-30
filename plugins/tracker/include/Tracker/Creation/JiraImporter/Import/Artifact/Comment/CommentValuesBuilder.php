<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation\JiraImporter\Import\Artifact\Comment;

use Psr\Log\LoggerInterface;
use Tuleap\Tracker\Creation\JiraImporter\ClientWrapper;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

class CommentValuesBuilder
{
    /**
     * @var ClientWrapper
     */
    private $wrapper;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ClientWrapper $wrapper,
        LoggerInterface $logger
    ) {
        $this->wrapper = $wrapper;
        $this->logger  = $logger;
    }

    /**
     * @return Comment[]
     * @throws JiraConnectionException
     */
    public function buildCommentCollectionForIssue(string $jira_issue_key): array
    {
        $this->logger->debug("Start build comment collection ...");
        $comment_collection = [];

        $comment_response = $this->wrapper->getUrl(
            $this->getCommentURL($jira_issue_key, null, null)
        );
        $comment_response_representation = CommentResponseRepresentation::buildFromAPIResponse($comment_response);

        foreach ($comment_response_representation->getComments() as $comment) {
            $comment_collection[] = Comment::buildFromAPIResponse($comment);
        }

        $count_loop = 1;
        $total      = $comment_response_representation->getTotal();
        $is_last    = $total <= $comment_response_representation->getMaxResults();
        while (! $is_last) {
            $max_results = $comment_response_representation->getMaxResults();
            $start_at    = $max_results * $count_loop;

            $comment_response = $this->wrapper->getUrl(
                $this->getCommentURL($jira_issue_key, $start_at, $max_results)
            );
            $comment_response_representation = CommentResponseRepresentation::buildFromAPIResponse($comment_response);

            foreach ($comment_response_representation->getComments() as $comment) {
                $comment_collection[] = Comment::buildFromAPIResponse($comment);
            }

            $is_last = $comment_response_representation->getTotal() <=
                ($comment_response_representation->getStartAt() + $comment_response_representation->getMaxResults());
            $count_loop++;
        }

        $this->logger->debug("End build comment collection ...");

        return $comment_collection;
    }

    private function getCommentURL(
        string $jira_issue_key,
        ?int $start_at,
        ?int $max_results
    ): string {
        $params = [
            'expand' => 'renderedBody'
        ];

        if ($start_at !== null) {
            $params['startAt'] = $start_at;
        }

        if ($max_results !== null) {
            $params['maxResults'] = $max_results;
        }

        $url = '/issue/' . urlencode($jira_issue_key) . '/comment?' . http_build_query($params);
        $this->logger->debug("  GET " .  $url);

        return $url;
    }
}
