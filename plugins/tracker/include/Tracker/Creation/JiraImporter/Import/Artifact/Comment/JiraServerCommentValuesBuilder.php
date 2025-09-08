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
use Tuleap\Tracker\Creation\JiraImporter\JiraClient;
use Tuleap\Tracker\Creation\JiraImporter\JiraConnectionException;

final class JiraServerCommentValuesBuilder implements CommentValuesBuilder
{
    public function __construct(
        private JiraClient $jira_client,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return Comment[]
     * @throws JiraConnectionException
     */
    #[\Override]
    public function buildCommentCollectionForIssue(string $jira_issue_key): array
    {
        $this->logger->debug('Start build comment collection ...');
        $comment_collection = [];

        $results = $this->jira_client->getUrl(ClientWrapper::JIRA_CORE_BASE_URL . '/issue/' . urlencode($jira_issue_key) . '?expand=renderedFields');
        if (! isset($results['fields']['comment']['comments'])) {
            throw new CommentAPIResponseNotWellFormedException('JiraServer is supposed to have .fields.comment.comments');
        }
        foreach ($results['fields']['comment']['comments'] as $comment) {
            $comment_collection[] = JiraServerComment::buildFromAPIResponse($comment);
        }

        $this->logger->debug('End build comment collection ...');

        return $comment_collection;
    }
}
