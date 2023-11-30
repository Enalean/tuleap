<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\PullRequest\Label;

use Git_GitRepositoryUrlManager;
use GitRepoNotFoundException;
use GitRepositoryFactory;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use TemplateRenderer;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Label\LabeledItem;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\NeverThrow\Fault;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\PullRequestRetriever;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

class LabeledItemCollector
{
    public function __construct(
        private readonly PullRequestLabelDao $label_dao,
        private readonly PullRequestRetriever $pull_request_retriever,
        private readonly PullRequestPermissionChecker $pullrequest_permission_checker,
        private readonly HTMLURLBuilder $html_url_builder,
        private readonly GlyphFinder $glyph_finder,
        private readonly GitRepositoryFactory $repository_factory,
        private readonly UserManager $user_manager,
        private readonly UserHelper $user_helper,
        private readonly Git_GitRepositoryUrlManager $repository_url_manager,
        private readonly TemplateRenderer $template_renderer,
    ) {
    }

    public function collect(LabeledItemCollection $collection)
    {
        $labels_ids = $collection->getLabelIds();
        $project_id = $collection->getProject()->getID();
        $limit      = $collection->getLimit();
        $offset     = $collection->getOffset();
        $dar        = $this->label_dao->searchPullRequestsByLabels($project_id, $labels_ids, $limit, $offset);
        $collection->setTotalSize($this->label_dao->foundRows());
        foreach ($dar as $row) {
            $this->pull_request_retriever->getPullRequestById($row['id'])->match(
                function (PullRequest $pull_request) use ($collection) {
                    try {
                        $this->pullrequest_permission_checker->checkPullRequestIsReadableByUser($pull_request, $collection->getUser());
                        $collection->add(
                            new LabeledItem(
                                $this->glyph_finder->get('tuleap-pullrequest'),
                                $this->glyph_finder->get('tuleap-pullrequest-small'),
                                $this->getHTMLMessage($pull_request),
                                $this->html_url_builder->getPullRequestOverviewUrl($pull_request)
                            )
                        );
                    } catch (GitRepoNotFoundException | Project_AccessProjectNotFoundException) {
                        // Do nothing
                    } catch (UserCannotReadGitRepositoryException | Project_AccessException) {
                        $collection->thereAreItemsUserCannotSee();
                    }
                },
                static fn(Fault $fault) => throw new \LogicException(sprintf("A label cannot be used in a nonexistent pull request: %s", $fault))
            );
        }
    }

    private function getHTMLMessage(PullRequest $pull_request)
    {
        $repository      = $this->repository_factory->getRepositoryById($pull_request->getRepoDestId());
        $repository_link = $repository->getHTMLLink($this->repository_url_manager);

        $user      = $this->user_manager->getUserById($pull_request->getUserId());
        $user_link = $this->user_helper->getLinkOnUser($user);

        return $this->template_renderer->renderToString(
            "labeled-pull-request",
            new LabeledPullRequestPresenter(
                $pull_request,
                $repository_link,
                $user_link
            )
        );
    }
}
