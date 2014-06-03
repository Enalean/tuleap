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

class Search_SearchPeople {
    const NAME = 'people';

    /**
     * @var UserManager
     */
    private $manager;


    public function __construct(UserManager $manager) {
        $this->manager = $manager;
    }

    public function search(Search_SearchQuery $query) {
        return $this->getSearchPeopleResultPresenter($this->manager->getAllUsersByUsernameOrRealname($query->getWords(), $query->getOffset(), $query->getExact()), $query->getWords());
    }

    private function getSearchPeopleResultPresenter(array $users, $words) {
        return new Search_SearchResultsPresenter(
            new Search_SearchResultsIntroPresenter($users, $words),
            $this->getResultsPresenters($users)
        );
    }

    private function getResultsPresenters(array $users) {
        $users_presenters = array();

        foreach ($users as $user) {
            $users_presenters[] = new Search_SearchPeopleResultPresenter($user);
        }

        return $users_presenters;
    }
}
