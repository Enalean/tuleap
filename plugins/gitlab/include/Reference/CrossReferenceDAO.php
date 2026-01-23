<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

namespace Tuleap\Gitlab\Reference;

use Override;
use Tuleap\DB\DataAccessObject;
use Tuleap\Gitlab\Reference\Branch\GitlabBranchReference;
use Tuleap\Gitlab\Reference\Commit\GitlabCommitReference;
use Tuleap\Gitlab\Reference\MergeRequest\GitlabMergeRequestReference;
use Tuleap\Gitlab\Reference\Tag\GitlabTagReference;

final class CrossReferenceDAO extends DataAccessObject implements UpdateCrossReference
{
    #[Override]
    public function updateBranchCrossReference(int $integration_id, string $old_name, string $new_name): void
    {
        $sql = <<<SQL
            UPDATE cross_references refs
            INNER JOIN plugin_gitlab_repository_integration_branch_info AS branches ON (
                branches.integration_id = ?
                    AND refs.source_keyword = ?
                    AND refs.source_id = CONCAT(?, '/', branches.branch_name)
            )
            SET refs.source_id = CONCAT(?, '/', branches.branch_name)
            SQL;

        $this->getDB()->run(
            $sql,
            $integration_id,
            GitlabBranchReference::REFERENCE_NAME,
            $old_name,
            $new_name,
        );
    }

    #[Override]
    public function updateCommitCrossReference(int $integration_id, string $old_name, string $new_name): void
    {
        $sql = <<<SQL
            UPDATE cross_references refs
            INNER JOIN plugin_gitlab_repository_integration_commit_info AS commits ON (
                commits.integration_id = ?
                    AND refs.source_keyword = ?
                    AND refs.source_id = CONCAT(?, '/', LOWER(HEX(commits.commit_sha1)))
            )
            SET refs.source_id = CONCAT(?, '/', LOWER(HEX(commits.commit_sha1)))
            SQL;

        $this->getDB()->run(
            $sql,
            $integration_id,
            GitlabCommitReference::REFERENCE_NAME,
            $old_name,
            $new_name,
        );
    }

    #[Override]
    public function updateMergeRequestCrossReference(int $integration_id, string $old_name, string $new_name): void
    {
        $sql = <<<SQL
            UPDATE cross_references refs
            INNER JOIN plugin_gitlab_repository_integration_merge_request_info AS mrs ON (
                mrs.integration_id = ?
                    AND refs.source_keyword = ?
                    AND refs.source_id = CONCAT(?, '/', mrs.merge_request_id)
            )
            SET refs.source_id = CONCAT(?, '/', mrs.merge_request_id)
            SQL;

        $this->getDB()->run(
            $sql,
            $integration_id,
            GitlabMergeRequestReference::REFERENCE_NAME,
            $old_name,
            $new_name,
        );
    }

    #[Override]
    public function updateTagCrossReference(int $integration_id, string $old_name, string $new_name): void
    {
        $sql = <<<SQL
            UPDATE cross_references refs
            INNER JOIN plugin_gitlab_repository_integration_tag_info AS tags ON (
                tags.integration_id = ?
                    AND refs.source_keyword = ?
                    AND refs.source_id = CONCAT(?, '/', tags.tag_name)
            )
            SET refs.source_id = CONCAT(?, '/', tags.tag_name)
            SQL;

        $this->getDB()->run(
            $sql,
            $integration_id,
            GitlabTagReference::REFERENCE_NAME,
            $old_name,
            $new_name,
        );
    }
}
