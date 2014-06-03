<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Search_SearchProject {
    const NAME = 'soft';

    /**
     * @var ProjectDao
     */
    private $dao;


    public function __construct(ProjectDao $dao) {
        $this->dao = $dao;
    }

    public function search(Search_SearchQuery $query) {
        $user = UserManager::instance()->getCurrentUser();
        if ($user->isRestricted()) {
            return $this->getSearchProjectResultPresenter($this->dao->searchGlobalForRestrictedUsers($query->getWords(), $query->getOffset(), $query->getExact(), $user->getId()), $query->getWords());
        }

        return $this->getSearchProjectResultPresenter($this->dao->searchGlobal($query->getWords(), $query->getOffset(), $query->getExact()), $query->getWords());
    }

    private function getSearchProjectResultPresenter(DataAccessResult $results, $words) {
        return new Search_SearchResultsPresenter(
            new Search_SearchResultsIntroPresenter($results, $words),
            $this->getResultsPresenters($results)
        );
    }

    private function getResultsPresenters(DataAccessResult $results) {
        $results_presenters = array();

        foreach ($results as $result) {
            $results_presenters[] = new Search_SearchProjectResultPresenter($result);
        }

        return $results_presenters;
    }
}
