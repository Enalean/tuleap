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

class Search_SearchController {

    const DEFAULT_SEARCH = Search_SearchProject::NAME;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(EventManager $event_manager) {
        $this->event_manager = $event_manager;
        $this->renderer = TemplateRendererFactory::build()->getRenderer(
            array(
                 Config::get('codendi_dir') .'/src/templates/search',
            )
        );
    }

    public function index(Codendi_Request $request) {
        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('search_index', 'search'), 'body_class' => array('search-page')));
        $this->renderer->renderToPage('index', new Search_Presenter_SearchPresenter(self::DEFAULT_SEARCH, '', null, ''));
        $GLOBALS['HTML']->footer(array('without_content' => true));
    }

    public function ajaxResults(Codendi_Request $request) {
        $query = new Search_SearchQuery($request);
        if (! $query->isValid()) {
            $GLOBALS['Response']->send400JSONErrors($GLOBALS['Language']->getText('search_index', 'at_least_3_ch'));
        }

        $results = $this->doSearch($query);
        if ($results !== null) {
            $this->renderer->renderToPage('results', array('search_result' => $results));
        }
    }

    public function results(Codendi_Request $request) {
        $query = new Search_SearchQuery($request);
        if (! $query->isValid()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('search_index', 'at_least_3_ch'));
            $GLOBALS['Response']->redirect('/search/');
        }

        $results = $this->doSearch($query);
        if ($results !== null) {
            $this->renderResults($query, $results);
        }
    }

    private function renderResults(Search_SearchQuery $query, $results) {
        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('search_index', 'search'), 'body_class' => array('search-page')));
        $this->renderer->renderToPage('site-search', $this->getSearchPresenter($query, $results));
        $GLOBALS['HTML']->footer(array('without_content' => true));
    }

    private function getSearchPresenter(Search_SearchQuery $query, $results) {
        $project_search_types = array();
        $site_search_types    = array();

        $this->event_manager->processEvent(
            Event::SEARCH_TYPES_PRESENTERS,
            array(
                'project_presenters' => &$project_search_types,
                'site_presenters'    => &$site_search_types,
            )
        );

        $search_panes = array();
        if (! $query->getProject()->isError()) {
            $search_panes[] = new Search_SearchPanePresenter(
                $GLOBALS['Language']->getText('search_index', 'project_wide_search', array($query->getProject()->getPublicName())),
                array_merge(
                    array(
                        new Search_SearchTypePresenter(
                            Search_SearchWiki::NAME,
                            'Wiki'
                        ),
                    ),
                    $project_search_types
                )
            );
        }
        $search_panes[] = new Search_SearchPanePresenter(
            $GLOBALS['Language']->getText('search_index', 'site_wide_search'),
            array_merge(
                array(
                    new Search_SearchTypePresenter(
                        Search_SearchProject::NAME,
                        Search_SearchProject::NAME
                    ),
                    new Search_SearchTypePresenter(
                        Search_SearchPeople::NAME,
                        Search_SearchPeople::NAME
                    ),
                    new Search_SearchTypePresenter(
                        Search_SearchSnippet::NAME,
                        Search_SearchSnippet::NAME
                    ),
                ),
                $site_search_types
            )
        );

        return new Search_Presenter_SearchPresenter(
            $query->getTypeOfSearch(),
            $query->getWords(),
            $results,
            $search_panes
        );
    }

    private function doSearch(Search_SearchQuery $query) {
        switch ($query->getTypeOfSearch()) {
            case Search_SearchTrackerV3::NAME:
                $search = new Search_SearchTrackerV3(new ArtifactDao());
                $search_result = $this->renderer->renderToString(
                    'search_trackerv3',
                    $search->search($query)
                );
                break;

            case Search_SearchProject::NAME:
                $search = new Search_SearchProject(new ProjectDao());
                $search_result = $this->renderer->renderToString(
                    'search_project',
                    $search->search($query)
                );
                break;

            case Search_SearchPeople::NAME:
                $search = new Search_SearchPeople(UserManager::instance());
                $search_result = $this->renderer->renderToString(
                    'search_people',
                    $search->search($query)
                );
                break;

            case Search_SearchForum::NAME:
                $search = new Search_SearchForum(new ForumDao());
                $search_result = $this->renderer->renderToString(
                    'search_forum',
                    $search->search($query)
                );
                break;

            case Search_SearchSnippet::NAME:
                $search = new Search_SearchSnippet(new SnippetDao());
                $search_result = $this->renderer->renderToString(
                    'search_snippet',
                    $search->search($query)
                );
                break;

            case Search_SearchWiki::NAME:
                $search = new Search_SearchWiki(new WikiDao());
                $search->search($query);
                break;

            default:
                $search = new Search_SearchPlugin($this->event_manager);
                $search->search($query);
                $search_result = $search->getResults();
                break;
        }
        return $search_result;
    }
}
