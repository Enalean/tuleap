<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\BranchUpdate;

use PFUser;
use TemplateRendererFactory;
use Tuleap\Git\GitPHP\Project as GitResourceAccessor;
use Tuleap\PullRequest\Notification\FilterUserFromCollection;
use Tuleap\PullRequest\Notification\NotificationEnhancedContent;
use Tuleap\PullRequest\Notification\NotificationTemplatedContent;
use Tuleap\PullRequest\Notification\NotificationToProcess;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;

final class PullRequestUpdatedNotification implements NotificationToProcess
{
    /**
     * @var PullRequest
     * @psalm-readonly
     */
    private $pull_request;
    /**
     * @var string
     * @psalm-readonly
     */
    private $change_user_display_name;
    /**
     * @var array
     * @psalm-readonly
     */
    private $owners;
    /**
     * @var NotificationEnhancedContent
     * @psalm-readonly
     */
    private $enhanced_content;
    /**
     * @var CommitPresenter[]
     *
     * @psalm-var non-empty-array<CommitPresenter> $new_commits
     * @psalm-readonly
     */
    private $new_commits;

    /**
     * @param PFUser[] $owners_without_change_user
     * @param CommitPresenter[] $new_commits
     *
     * @psalm-param non-empty-array<CommitPresenter> $new_commits
     */
    private function __construct(
        PullRequest $pull_request,
        string $change_user_display_name,
        array $owners_without_change_user,
        NotificationEnhancedContent $enhanced_content,
        array $new_commits
    ) {
        $this->pull_request              = $pull_request;
        $this->change_user_display_name  = $change_user_display_name;
        $this->owners                    = $owners_without_change_user;
        $this->enhanced_content          = $enhanced_content;
        $this->new_commits               = $new_commits;
    }

    /**
     * @param PFUser[] $owners
     * @param string[] $new_commit_references
     *
     * @psalm-param non-empty-array<string> $new_commit_references
     */
    public static function fromOwnersAndReferences(
        UserHelper $user_helper,
        HTMLURLBuilder $html_url_builder,
        FilterUserFromCollection $filter_user_from_collection,
        PullRequest $pull_request,
        PFUser $change_user,
        array $owners,
        GitResourceAccessor $git_resource_accessor,
        RepositoryURLToCommitBuilder $repository_url_to_commit_builder,
        array $new_commit_references
    ): ?self {
        $commit_presenters = self::buildCommitPresenters($git_resource_accessor, $repository_url_to_commit_builder, ...$new_commit_references);
        if (empty($commit_presenters)) {
            return null;
        }

        $owners_without_change_user = $filter_user_from_collection->filter($change_user, ...$owners);
        $change_user_display_name   = $user_helper->getDisplayNameFromUser($change_user) ?? '';

        return new self(
            $pull_request,
            $change_user_display_name,
            $owners_without_change_user,
            new NotificationTemplatedContent(
                TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/branch-update'),
                'branch-updated-mail-content',
                new PullRequestUpdatedContentPresenter(
                    $change_user_display_name,
                    $user_helper->getAbsoluteUserURL($change_user),
                    $pull_request->getId(),
                    $pull_request->getTitle(),
                    $html_url_builder->getAbsolutePullRequestOverviewUrl($pull_request),
                    $commit_presenters
                )
            ),
            $commit_presenters
        );
    }

    /**
     * @return CommitPresenter[]
     *
     * @psalm-param non-empty-array<string> $commit_references
     */
    private static function buildCommitPresenters(
        GitResourceAccessor $git_resource_accessor,
        RepositoryURLToCommitBuilder $repository_url_to_commit_builder,
        string ...$commit_references
    ): array {
        $presenters = [];
        foreach ($commit_references as $commit_reference) {
            $commit = $git_resource_accessor->GetCommit($commit_reference);
            if ($commit === null) {
                continue;
            }
            $presenters[] = new CommitPresenter(
                $commit_reference,
                $commit->GetTitle() ?? '',
                $repository_url_to_commit_builder->buildURLForReference($commit_reference)
            );
        }

        return $presenters;
    }

    /**
     * @psalm-mutation-free
     */
    public function getPullRequest(): PullRequest
    {
        return $this->pull_request;
    }

    /**
     * @psalm-mutation-free
     */
    public function getRecipients(): array
    {
        return $this->owners;
    }

    /**
     * @psalm-mutation-free
     */
    public function asPlaintext(): string
    {
        $nb_new_commits = count($this->new_commits);
        $plaintext      = sprintf(
            dngettext(
                'tuleap-pullrequest',
                '%s pushed %d commit updating the pull request #%d: %s.',
                '%s pushed %d commits updating the pull request #%d: %s.',
                count($this->new_commits)
            ),
            $this->change_user_display_name,
            $nb_new_commits,
            $this->pull_request->getId(),
            $this->pull_request->getTitle()
        ) . "\n\n";

        foreach ($this->new_commits as $new_commit) {
            $plaintext .= $new_commit->short_reference . '  ' . $new_commit->title . "\n";
        }

        return trim($plaintext);
    }

    /**
     * @psalm-mutation-free
     */
    public function asEnhancedContent(): NotificationEnhancedContent
    {
        return $this->enhanced_content;
    }
}
