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

namespace Tuleap\PullRequest\REST\v1;

use Codendi_HTMLPurifier;
use GitRepository;
use Tuleap\Markdown\ContentInterpretor;
use Tuleap\PullRequest\GitReference\GitPullRequestReference;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequest\Timeline\TimelineComment;
use Tuleap\REST\JsonCast;
use Tuleap\User\REST\MinimalUserRepresentation;

class PullRequestRepresentation extends PullRequestMinimalRepresentation
{
    public const ROUTE = parent::ROUTE;

    public const COMMENTS_ROUTE = 'comments';
    public const INLINE_ROUTE   = 'inline-comments';
    public const LABELS_ROUTE   = 'labels';
    public const FILES_ROUTE    = 'files';
    public const DIFF_ROUTE     = 'file_diff';
    public const TIMELINE_ROUTE = 'timeline';

    public const STATUS_ABANDON = 'abandon';
    public const STATUS_MERGE   = 'merge';
    public const STATUS_REVIEW  = 'review';

    public const NO_FASTFORWARD_MERGE = 'no_fastforward';
    public const FASTFORWARD_MERGE    = 'fastforward';
    public const CONFLICT_MERGE       = 'conflict';
    public const UNKNOWN_MERGE        = 'unknown-merge-status';

    /**
     * @var string {@type string}
     */
    public $description;

    /**
     * @var string {@type string}
     */
    public $reference_src;

    /**
     * @var string {@type string}
     */
    public $reference_dest;

    /**
     * @var string
     */
    public $head_reference;

    /**
     * @var array {@type array}
     */
    public $resources;

    /**
     * @var bool {@type bool}
     */
    public $user_can_merge;

    /**
     * @var bool {@type bool}
     */
    public $user_can_abandon;

    /**
     * @var bool {@type bool}
     */
    public $user_can_update_labels;

    /**
     * @var string {@type string}
     */
    public $merge_status;

    /**
     * @var PullRequestShortStatRepresentation {@type PullRequestShortStatRepresentation}
     */
    public $short_stat;

    /**
     * @var string {@type string}
     */
    public $last_build_status;

    /**
     * @var string {@type string}
     */
    public $last_build_date;

    /**
     * @var string {@type string}
     */
    public $raw_title;

    /**
     * @var string {@type string}
     */
    public $raw_description;

    /**
     * @var int {@type int} {@required false}
     */
    public $parent_id;

    /**
     * @var bool {@type bool}
     */
    public bool $user_can_reopen;

    /**
     * @var PullRequestStatusInfoRepresentation | null {@type PullRequestStatusInfoRepresentation | null}
     */
    public ?PullRequestStatusInfoRepresentation $status_info;
    public bool $user_can_update_title_and_description;
    public string $description_format;
    public string $post_processed_description;

    /**
     * @param MinimalUserRepresentation[] $reviewers
     */
    public function build(
        Codendi_HTMLPurifier $purifier,
        ContentInterpretor $common_mark_interpreter,
        PullRequest $pull_request,
        GitRepository $repository,
        GitRepository $repository_dest,
        GitPullRequestReference $git_reference,
        bool $user_can_merge,
        bool $user_can_abandon,
        bool $user_can_reopen,
        bool $user_can_update_labels,
        $last_build_status_name,
        $last_build_date,
        \PFUser $user,
        array $reviewers,
        PullRequestShortStatRepresentation $pr_short_stat_representation,
        ?PullRequestStatusInfoRepresentation $status_info_representation,
    ) {
        $this->buildMinimal($pull_request, $repository, $repository_dest, $git_reference, $reviewers);

        $project_id                       = $repository->getProjectId();
        $this->description_format         = $pull_request->getDescriptionFormat();
        $this->description                = $purifier->purify($pull_request->getDescription(), Codendi_HTMLPurifier::CONFIG_BASIC, $project_id);
        $this->post_processed_description = $this->getPurifiedDescriptionFromHTML($purifier, $common_mark_interpreter, $pull_request->getDescriptionFormat(), $project_id, $pull_request->getDescription());

        $this->reference_src  = $pull_request->getSha1Src();
        $this->reference_dest = $pull_request->getSha1Dest();
        $this->head_reference = $git_reference->getGitHeadReference();
        $this->status         = $this->expandStatusName($pull_request->getStatus());

        $this->last_build_status = $last_build_status_name;
        $this->last_build_date   = JsonCast::toDate($last_build_date);

        $this->user_can_update_labels                = $user_can_update_labels;
        $this->user_can_update_title_and_description = $user_can_merge || $pull_request->getUserId() === $user->getId();
        $this->user_can_merge                        = $user_can_merge;
        $this->user_can_abandon                      = $user_can_abandon;
        $this->user_can_reopen                       = $user_can_reopen;
        $this->merge_status                          = $this->expandMergeStatusName($pull_request->getMergeStatus());

        $this->short_stat  = $pr_short_stat_representation;
        $this->status_info = $status_info_representation;

        $this->raw_title       = $pull_request->getTitle();
        $this->raw_description = $pull_request->getDescription();

        $this->resources = [
            self::COMMENTS_ROUTE => [
                'uri' => $this->uri . '/' . self::COMMENTS_ROUTE,
            ],
            self::INLINE_ROUTE => [
                'uri' => $this->uri . '/' . self::INLINE_ROUTE,
            ],
            self::LABELS_ROUTE => [
                'uri' => $this->uri . '/' . self::LABELS_ROUTE,
            ],
            self::FILES_ROUTE => [
                'uri' => $this->uri . '/' . self::FILES_ROUTE,
            ],
            self::DIFF_ROUTE => [
                'uri' => $this->uri . '/' . self::DIFF_ROUTE,
            ],
            self::TIMELINE_ROUTE => [
                'uri' => $this->uri . '/' . self::TIMELINE_ROUTE,
            ],
        ];
    }

    private function expandStatusName($status_acronym)
    {
        $status_name = [
            PullRequest::STATUS_ABANDONED => self::STATUS_ABANDON,
            PullRequest::STATUS_MERGED    => self::STATUS_MERGE,
            PullRequest::STATUS_REVIEW    => self::STATUS_REVIEW,
        ];

        return $status_name[$status_acronym];
    }

    private function expandMergeStatusName($merge_status_acronym)
    {
        $status_name = [
            PullRequest::NO_FASTFORWARD_MERGE => self::NO_FASTFORWARD_MERGE,
            PullRequest::FASTFORWARD_MERGE    => self::FASTFORWARD_MERGE,
            PullRequest::CONFLICT_MERGE       => self::CONFLICT_MERGE,
            PullRequest::UNKNOWN_MERGE        => self::UNKNOWN_MERGE,
        ];

        return $status_name[$merge_status_acronym];
    }

    private function getPurifiedDescriptionFromHTML(
        Codendi_HTMLPurifier $purifier,
        ContentInterpretor $common_mark_interpreter,
        string $description_format,
        int $project_id,
        string $description,
    ): string {
        if ($description_format === TimelineComment::FORMAT_MARKDOWN) {
            return $common_mark_interpreter->getInterpretedContentWithReferences(
                $description,
                $project_id
            );
        }

        return $purifier->purify($description, Codendi_HTMLPurifier::CONFIG_BASIC, $project_id);
    }
}
