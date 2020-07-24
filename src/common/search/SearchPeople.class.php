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

class Search_SearchPeople
{
    public const NAME = 'people';

    /**
     * @var UserManager
     */
    private $manager;


    public function __construct(UserManager $manager)
    {
        $this->manager = $manager;
    }

    public function search(Search_SearchQuery $query, Search_SearchResults $search_results)
    {
        $user_collection = $this->manager->getPaginatedUsersByUsernameOrRealname(
            $query->getWords(),
            $query->getExact(),
            $query->getOffset(),
            $query->getNumberOfResults()
        );

        $results_count      = count($user_collection);
        $maybe_more_results = ($results_count < $query->getNumberOfResults()) ? false : true;
        $search_results->setHasMore($maybe_more_results)
            ->setCountResults($results_count);

        return $this->getSearchPeopleResultPresenter(
            $user_collection,
            $query->getWords(),
            $maybe_more_results
        );
    }

    private function getSearchPeopleResultPresenter(
        PaginatedUserCollection $user_collection,
        $words,
        $maybe_more_results
    ) {
        return new Search_SearchResultsPresenter(
            new Search_SearchResultsIntroPresenter($user_collection->getUsers(), $words),
            $this->getResultsPresenters($user_collection),
            self::NAME,
            $maybe_more_results
        );
    }

    private function getResultsPresenters(PaginatedUserCollection $user_collection)
    {
        $users_presenters = [];

        foreach ($user_collection->getUsers() as $user) {
            $users_presenters[] = new Search_SearchPeopleResultPresenter($user);
        }

        return $users_presenters;
    }
}
