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
use Tuleap\PullRequest\Criterion\PullRequestSortOrder;
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
        PullRequestSortOrder $order,
        int $limit,
        int $offset,
    ): PullRequestsPage {
        return $this->getDB()->tryFlatTransaction(
            function () use ($repository_id, $criteria, $order, $limit, $offset) {
                $search_criteria_statements = $this->getSearchCriteriaStatements($criteria);

                $sql_select_count  = 'SELECT COUNT(*) OVER () ';
                $columns_to_select = [
                    'plugin_pullrequest_review.id',
                    'plugin_pullrequest_review.title',
                    'plugin_pullrequest_review.description',
                    'plugin_pullrequest_review.repository_id',
                    'plugin_pullrequest_review.user_id',
                    'plugin_pullrequest_review.creation_date',
                    'plugin_pullrequest_review.branch_src',
                    'plugin_pullrequest_review.sha1_src',
                    'plugin_pullrequest_review.repo_dest_id',
                    'plugin_pullrequest_review.branch_dest',
                    'plugin_pullrequest_review.sha1_dest',
                    'plugin_pullrequest_review.status',
                    'plugin_pullrequest_review.merge_status',
                    'plugin_pullrequest_review.description_format',
                ];
                $sql_columns       = \Psl\Str\join($columns_to_select, ', ');
                $sql_select_data   = "SELECT $sql_columns ";

                $sql_tables = \Psl\Str\join(['plugin_pullrequest_review', ...$search_criteria_statements->tables], ', ');

                $sql_query_body = "
                    FROM $sql_tables
                    WHERE (plugin_pullrequest_review.repository_id = ? OR plugin_pullrequest_review.repo_dest_id = ?)
                        AND $search_criteria_statements->where_statement
                    GROUP BY $sql_columns
                    HAVING $search_criteria_statements->having_statement
                ";

                $parameters = [
                    $repository_id,
                    $repository_id,
                    ...$search_criteria_statements->where_statement->values(),
                    ...$search_criteria_statements->having_statement->values(),
                ];

                $sql_order = match ($order) {
                    PullRequestSortOrder::ASCENDING => 'ASC',
                    PullRequestSortOrder::DESCENDING => 'DESC',
                };

                return new PullRequestsPage(
                    $this->getDB()->single($sql_select_count . $sql_query_body . ' LIMIT 1', $parameters),
                    $this->getDB()->safeQuery(
                        $sql_select_data .
                        $sql_query_body .
                        ' ORDER BY plugin_pullrequest_review.creation_date ' . $sql_order .
                        ' LIMIT ? OFFSET ?',
                        [...$parameters, $limit, $offset]
                    )
                );
            }
        );
    }

    private function getSearchCriteriaStatements(SearchCriteria $search_criteria): PullRequestDAOSearchCriteria
    {
        $where_statement  = EasyStatement::open();
        $having_statement = EasyStatement::open();
        $tables           = [];

        $search_criteria->status->apply(function ($status_criterion) use ($where_statement) {
            if ($status_criterion->shouldOnlyRetrieveOpenPullRequests()) {
                $where_statement->andIn('status IN (?*)', [PullRequest::STATUS_REVIEW]);
            }

            if ($status_criterion->shouldOnlyRetrieveClosedPullRequests()) {
                $where_statement->andIn('status IN (?*)', [PullRequest::STATUS_ABANDONED, PullRequest::STATUS_MERGED]);
            }
        });

        $authors_id = [];
        foreach ($search_criteria->authors as $author) {
            $authors_id[] = $author->id;
        }
        if (count($authors_id) > 0) {
            $where_statement->andIn('user_id IN (?*)', $authors_id);
        }

        if (count($search_criteria->labels) > 0) {
            $labels_ids = [];
            foreach ($search_criteria->labels as $label) {
                $labels_ids[] = $label->id;
            }

            $tables[] = 'plugin_pullrequest_label';
            $having_statement->andWith('COUNT(plugin_pullrequest_label.label_id) = ?', count($labels_ids));
            $where_statement->andIn('plugin_pullrequest_review.id = plugin_pullrequest_label.pull_request_id AND plugin_pullrequest_label.label_id IN (?*)', $labels_ids);
        }

        return new PullRequestDAOSearchCriteria(
            $where_statement,
            $having_statement,
            $tables
        );
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
                    AND user_id != 0
                ";

                $sql = "
                    SELECT DISTINCT user_id
                    FROM plugin_pullrequest_review
                    WHERE (repository_id = ? OR repo_dest_id = ?)
                    AND user_id != 0
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
