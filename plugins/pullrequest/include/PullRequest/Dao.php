<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use ParagonIE\EasyDB\EasyStatement;
use Tuleap\DB\DataAccessObject;
use Tuleap\PullRequest\Criterion\SearchCriteria;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;

class Dao extends DataAccessObject implements SearchPullRequest, SearchPaginatedPullRequests, SearchPaginatedPullRequestsAuthors
{
    /**
     * @psalm-return array{
     *     id:int,
     *     title:string,
     *     description:string,
     *     repository_id:int,
     *     user_id:int,
     *     creation_date: int,
     *     branch_src: string,
     *     sha1_src: string,
     *     repo_dest_id: int,
     *     sha1_dest: int,
     *     status: string,
     *     merge_status: int,
     *     description_format: string
     * } | null
     */
    public function searchByPullRequestId(int $pull_request_id): ?array
    {
        $sql = 'SELECT *
                FROM plugin_pullrequest_review
                WHERE id = ?';

        return $this->getDB()->row($sql, $pull_request_id);
    }

    public function isPullRequestWithSameBranchesAndSourceReferenceAlreadyExisting(
        int $repo_src_id,
        string $branch_src,
        string $sha1_src,
        int $repo_dest_id,
        string $branch_dest,
    ): bool {
        $sql = 'SELECT NULL
                FROM plugin_pullrequest_review
                WHERE repository_id = ?
                  AND branch_src = ?
                  AND repo_dest_id = ?
                  AND branch_dest = ?
                  AND sha1_src = ?';

        $rows = $this->getDB()->run($sql, $repo_src_id, $branch_src, $repo_dest_id, $branch_dest, $sha1_src);

        return count($rows) > 0;
    }

    public function searchRepositoriesWithOpenPullRequests(array $repository_ids)
    {
        $ids_stmt = EasyStatement::open()->in('?*', $repository_ids);

        $sql = "SELECT repository_id, repo_dest_id
                FROM plugin_pullrequest_review
                WHERE status = ?
                  AND (repository_id IN ($ids_stmt) OR repo_dest_id IN ($ids_stmt))";
        return $this->getDB()->safeQuery($sql, array_merge([PullRequest::STATUS_REVIEW], $ids_stmt->values(), $ids_stmt->values()));
    }

    public function searchNbOfOpenedPullRequestsForRepositoryId($repository_id)
    {
        $sql = 'SELECT count(*) AS open_pr FROM plugin_pullrequest_review
                WHERE (repository_id= ? OR repo_dest_id = ?) AND status= ?';
        return $this->getDB()->single($sql, [$repository_id, $repository_id, PullRequest::STATUS_REVIEW]);
    }

    public function searchOpenedBySourceBranch($repository_id, $branch_name)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_review
                WHERE repository_id=? AND branch_src=? AND status=?';
        return $this->getDB()->run($sql, $repository_id, $branch_name, PullRequest::STATUS_REVIEW);
    }

    public function searchOpenedByDestinationBranch($repository_id, $branch_name)
    {
        $sql = 'SELECT * FROM plugin_pullrequest_review
                WHERE repo_dest_id=? AND branch_dest=? AND status=?';
        return $this->getDB()->run($sql, $repository_id, $branch_name, PullRequest::STATUS_REVIEW);
    }

    public function searchNbOfPullRequestsByStatusForRepositoryId($repository_id)
    {
        $sql = 'SELECT status, COUNT(*) as nb
                FROM plugin_pullrequest_review
                WHERE repository_id = ?
                   OR repo_dest_id = ?
                GROUP BY status';

        return $this->getDB()->run($sql, $repository_id, $repository_id);
    }

    public function hasRepositoryOpenPullRequestsWithBrokenGitReferences(int $repository_id): bool
    {
        $sql = "
            SELECT TRUE
            FROM plugin_pullrequest_review as review
            INNER JOIN plugin_pullrequest_git_reference as ref ON (review.id = ref.pr_id)
            WHERE review.repo_dest_id = ? AND ref.status = ? AND review.status = ?
        ";

        return $this->getDB()->exists(
            $sql,
            $repository_id,
            GitPullRequestReference::STATUS_BROKEN,
            PullRequest::STATUS_REVIEW
        );
    }

    public function create(
        $repository_id,
        $title,
        $description,
        $user_id,
        $creation_date,
        $branch_src,
        $sha1_src,
        $repo_dest_id,
        $branch_dest,
        $sha1_dest,
        $merge_status,
        string $format,
    ) {
        $this->getDB()->insert(
            'plugin_pullrequest_review',
            [
                'repository_id' => $repository_id,
                'title'         => $title,
                'description'   => $description,
                'user_id'       => $user_id,
                'creation_date' => $creation_date,
                'branch_src'    => $branch_src,
                'sha1_src'      => $sha1_src,
                'repo_dest_id'  => $repo_dest_id,
                'branch_dest'   => $branch_dest,
                'sha1_dest'     => $sha1_dest,
                'merge_status'  => $merge_status,
                'description_format' => $format,
            ]
        );

        return $this->getDB()->lastInsertId();
    }

    public function updateSha1Src($pull_request_id, $sha1_src)
    {
        $this->getDB()->run('UPDATE plugin_pullrequest_review SET sha1_src=? WHERE id=?', $sha1_src, $pull_request_id);
    }

    public function updateSha1Dest($pull_request_id, $sha1_dest)
    {
        $this->getDB()->run('UPDATE plugin_pullrequest_review SET sha1_dest=? WHERE id=?', $sha1_dest, $pull_request_id);
    }

    public function updateMergeStatus($pull_request_id, $merge_status)
    {
        $sql = 'UPDATE plugin_pullrequest_review SET merge_status=? WHERE id=?';
        $this->getDB()->run($sql, $merge_status, $pull_request_id);
    }

    public function getPaginatedPullRequests(
        int $repository_id,
        SearchCriteria $criteria,
        int $limit,
        int $offset,
    ): PullRequestsPage {
        return $this->getDB()->tryFlatTransaction(
            function () use ($repository_id, $criteria, $limit, $offset) {
                $where_status_statement = $this->getStatusStatements($criteria);

                $sql_count_pull_requests = "
                    SELECT COUNT(*)
                    FROM plugin_pullrequest_review
                    WHERE (repository_id = ? OR repo_dest_id = ?)
                    AND $where_status_statement
                ";

                $sql_get_pull_requests = "SELECT *
                    FROM plugin_pullrequest_review
                    WHERE (repository_id = ? OR repo_dest_id = ?)
                    AND $where_status_statement
                    ORDER BY creation_date DESC
                    LIMIT ?
                    OFFSET ?
                ";


                $parameters =  [$repository_id, $repository_id, ...$where_status_statement->values()];

                return new PullRequestsPage(
                    $this->getDB()->single($sql_count_pull_requests, $parameters),
                    $this->getDB()->safeQuery($sql_get_pull_requests, [...$parameters, $limit, $offset])
                );
            }
        );
    }

    /**
     * @return EasyStatement
     */
    private function getStatusStatements(SearchCriteria $search_criteria)
    {
        $statement = EasyStatement::open();

        $search_criteria->status->apply(function ($status_criterion) use ($statement) {
            if ($status_criterion->shouldOnlyRetrieveOpenPullRequests()) {
                $statement->andIn('status IN (?*)', [PullRequest::STATUS_REVIEW]);
            }

            if ($status_criterion->shouldOnlyRetrieveClosedPullRequests()) {
                $statement->andIn('status IN (?*)', [PullRequest::STATUS_ABANDONED, PullRequest::STATUS_MERGED]);
            }
        });

        $authors_id = [];
        foreach ($search_criteria->authors as $author) {
            $authors_id[] = $author->id;
        }
        if (count($authors_id) > 0) {
            $statement->andIn('user_id IN (?*)', $authors_id);
        }

        return $statement;
    }

    public function markAsAbandoned($pull_request_id)
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET status = ?
                WHERE id = ?';

        $this->getDB()->run($sql, PullRequest::STATUS_ABANDONED, $pull_request_id);
    }

    public function markAsMerged($pull_request_id)
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET status = ?
                WHERE id = ?';

        $this->getDB()->run($sql, PullRequest::STATUS_MERGED, $pull_request_id);
    }

    public function reopen(int $pull_request_id): void
    {
        $this->getDB()->update(
            'plugin_pullrequest_review',
            [
                "status" => PullRequest::STATUS_REVIEW,
            ],
            ["id" => $pull_request_id],
        );
    }

    public function updateTitle(int $pull_request_id, string $new_title): void
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET title = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $new_title, $pull_request_id);
    }

    public function updateDescription(int $pull_request_id, string $new_description, string $format): void
    {
        $sql = 'UPDATE plugin_pullrequest_review
                SET description = ?,  description_format = ?
                WHERE id = ?';

        $this->getDB()->run($sql, $new_description, $format, $pull_request_id);
    }

    public function deleteAllPullRequestsOfRepository($repository_id)
    {
        $sql = 'DELETE pr, label, comments, inline, event
                FROM plugin_pullrequest_review AS pr
                    LEFT JOIN plugin_pullrequest_label AS label ON (
                        pr.id = label.pull_request_id
                    )
                    LEFT JOIN plugin_pullrequest_comments AS comments ON (
                        pr.id = comments.pull_request_id
                    )
                    LEFT JOIN plugin_pullrequest_inline_comments AS inline ON (
                        pr.id = inline.pull_request_id
                    )
                    LEFT JOIN plugin_pullrequest_timeline_event AS event ON (
                        pr.id = event.pull_request_id
                    )
                WHERE pr.repository_id = ?';

        $this->getDB()->run($sql, $repository_id);
    }

    public function getPaginatedPullRequestsAuthorsIds(int $repository_id, int $limit, int $offset): PullRequestsAuthorsIdsPage
    {
        return $this->getDB()->tryFlatTransaction(
            function () use ($repository_id, $limit, $offset) {
                $sql_total_authors = "
                    SELECT COUNT(DISTINCT user_id)
                    FROM plugin_pullrequest_review
                    WHERE (repository_id = ? OR repo_dest_id = ?)
                ";

                $sql = "
                    SELECT DISTINCT user_id
                    FROM plugin_pullrequest_review
                    WHERE (repository_id = ? OR repo_dest_id = ?)
                    LIMIT ?
                    OFFSET ?
                ";

                $total_authors = $this->getDB()->single($sql_total_authors, [$repository_id, $repository_id]);
                $authors_ids   = $this->getDB()->column($sql, [$repository_id, $repository_id, $limit, $offset]);

                return new PullRequestsAuthorsIdsPage($total_authors, $authors_ids);
            }
        );
    }
}
