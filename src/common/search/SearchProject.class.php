<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class Search_SearchProject
{
    public const NAME = 'soft';

    /**
     * @var ProjectDao
     */
    private $dao;


    public function __construct(ProjectDao $dao)
    {
        $this->dao = $dao;
    }

    public function search(Search_SearchQuery $query, Search_SearchResults $search_results)
    {
        $user = UserManager::instance()->getCurrentUser();
        if ($user->isRestricted()) {
            $dao_results = $this->dao->searchGlobalPaginatedForRestrictedUsers($query->getWords(), $query->getOffset(), $query->getExact(), $user->getId(), $query->getNumberOfResults());
        } else {
            $dao_results = $this->dao->searchGlobalPaginated($query->getWords(), $query->getOffset(), $query->getExact(), $query->getNumberOfResults());
        }

        $results_count      = count($dao_results);
        $maybe_more_results = ($results_count < $query->getNumberOfResults()) ? false : true;
        $search_results->setHasMore($maybe_more_results)
            ->setCountResults($results_count);

        return $this->getSearchProjectResultPresenter($dao_results, $query->getWords(), $maybe_more_results);
    }

    private function getSearchProjectResultPresenter(LegacyDataAccessResultInterface $results, $words, $maybe_more_results)
    {
        return new Search_SearchResultsPresenter(
            new Search_SearchResultsIntroPresenter($results, $words),
            $this->getResultsPresenters($results),
            self::NAME,
            $maybe_more_results
        );
    }

    private function getResultsPresenters(LegacyDataAccessResultInterface $results)
    {
        $results_presenters = [];

        foreach ($results as $result) {
            $results_presenters[] = new Search_SearchProjectResultPresenter($result);
        }

        return $results_presenters;
    }
}
