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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\Gitlab\Reference\MergeRequest;

use Tuleap\Gitlab\Repository\GitlabRepositoryIntegration;
use Tuleap\Gitlab\Repository\Webhook\PostMergeRequest\MergeRequestTuleapReferenceDao;

class GitlabMergeRequestReferenceRetriever
{
    /**
     * @var MergeRequestTuleapReferenceDao
     */
    private $merge_request_tuleap_reference_dao;

    public function __construct(MergeRequestTuleapReferenceDao $merge_request_tuleap_reference_dao)
    {
        $this->merge_request_tuleap_reference_dao = $merge_request_tuleap_reference_dao;
    }

    public function getGitlabMergeRequestInRepositoryWithId(
        GitlabRepositoryIntegration $repository_integration,
        int $merge_request_id,
    ): ?GitlabMergeRequest {
        $row = $this->merge_request_tuleap_reference_dao->searchMergeRequestInRepositoryWithId(
            $repository_integration->getId(),
            $merge_request_id
        );

        if ($row === null) {
            return null;
        }

        return $this->getInstanceFromRow($row);
    }

    private function getInstanceFromRow(array $row): GitlabMergeRequest
    {
        return new GitlabMergeRequest(
            $row['title'],
            $row['state'],
            (new \DateTimeImmutable())->setTimestamp($row['created_at']),
            $row['author_name'],
            $row['author_email'],
        );
    }
}
