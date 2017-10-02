<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use GitRepoNotFoundException;
use Project_AccessException;
use Project_AccessProjectNotFoundException;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Label\LabeledItem;
use Tuleap\Label\LabeledItemCollection;
use Tuleap\PullRequest\Authorization\PullRequestPermissionChecker;
use Tuleap\PullRequest\Exception\UserCannotReadGitRepositoryException;
use Tuleap\PullRequest\Factory;

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

    public function __construct(
        PullRequestLabelDao $label_dao,
        Factory $pullrequest_factory,
        PullRequestPermissionChecker $pullrequest_permission_checker,
        GlyphFinder $glyph_finder
    ) {
        $this->label_dao                      = $label_dao;
        $this->pullrequest_permission_checker = $pullrequest_permission_checker;
        $this->glyph_finder                   = $glyph_finder;
        $this->pullrequest_factory            = $pullrequest_factory;
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
                        $pull_request->getTitle()
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
}
