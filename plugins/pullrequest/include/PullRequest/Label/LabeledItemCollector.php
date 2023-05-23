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
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Factory;
use Tuleap\PullRequest\PullRequest;
use Tuleap\PullRequest\Reference\HTMLURLBuilder;
use UserHelper;
use UserManager;

class LabeledItemCollector
{
    /**
     * @var PullRequestLabelDao
     */
    private $label_dao;
    /**
     * @var PullRequestPermissionChecker
     */
    private $pullrequest_permission_checker;
    /**
     * @var HTMLURLBuilder
     */
    private $html_url_builder;
    /**
     * @var GlyphFinder
     */
    private $glyph_finder;
    /**
     * @var Factory
     */
    private $pullrequest_factory;
    /**
     * @var GitRepositoryFactory
     */
    private $repository_factory;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var UserHelper
     */
    private $user_helper;
    /**
     * @var Git_GitRepositoryUrlManager
     */
    private $repository_url_manager;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    public function __construct(
        PullRequestLabelDao $label_dao,
        Factory $pullrequest_factory,
        PullRequestPermissionChecker $pullrequest_permission_checker,
        HTMLURLBuilder $html_url_builder,
        GlyphFinder $glyph_finder,
        GitRepositoryFactory $repository_factory,
        UserManager $user_manager,
        UserHelper $user_helper,
        Git_GitRepositoryUrlManager $repository_url_manager,
        TemplateRenderer $template_renderer,
    ) {
        $this->label_dao                      = $label_dao;
        $this->pullrequest_permission_checker = $pullrequest_permission_checker;
        $this->html_url_builder               = $html_url_builder;
        $this->glyph_finder                   = $glyph_finder;
        $this->pullrequest_factory            = $pullrequest_factory;
        $this->repository_factory             = $repository_factory;
        $this->user_manager                   = $user_manager;
        $this->user_helper                    = $user_helper;
        $this->repository_url_manager         = $repository_url_manager;
        $this->template_renderer              = $template_renderer;
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
            $pull_request = $this->pullrequest_factory->getPullRequestById($row['id']);
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
            } catch (GitRepoNotFoundException $e) {
                // Do nothing
            } catch (Project_AccessProjectNotFoundException $e) {
                // Do nothing
            } catch (UserCannotReadGitRepositoryException $e) {
                $collection->thereAreItemsUserCannotSee();
            } catch (Project_AccessException $e) {
                $collection->thereAreItemsUserCannotSee();
            }
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
