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

    private $search_types = array();

    public function __construct(EventManager $event_manager) {
        $this->event_manager = $event_manager;
        $this->renderer = TemplateRendererFactory::build()->getRenderer(
            array(
                 Config::get('codendi_dir') .'/src/templates/search',
            )
        );
        $this->search_types = array(
            Search_SearchTrackerV3::NAME => new Search_SearchTrackerV3(new ArtifactDao()),
            Search_SearchProject::NAME   => new Search_SearchProject(new ProjectDao()),
            Search_SearchPeople::NAME    => new Search_SearchPeople(UserManager::instance()),
            Search_SearchForum::NAME     => new Search_SearchForum(new ForumDao()),
            Search_SearchSnippet::NAME   => new Search_SearchSnippet(new SnippetDao()),
            Search_SearchWiki::NAME      => new Search_SearchWiki(new WikiDao()),
        );
    }

    public function index(Codendi_Request $request) {
        $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('search_index', 'search'), 'body_class' => array('search-page')));
        $this->renderer->renderToPage('site-search', new Search_Presenter_SearchPresenter(
            self::DEFAULT_SEARCH,
            '',
            '',
            array($this->getSiteWidePane())
        ));
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
                'project'            => $query->getProject(),
                'words'              => $query->getWords(),
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
                            'Wiki',
                            array(),
                            $this->search_types[Search_SearchWiki::NAME]->getRedirectUrl(
                                $query->getProject()->getId(),
                                $this->search_types[Search_SearchWiki::NAME]->getSearchPageName($query),
                                $query->getWords()
                            )
                        ),
                    ),
                    $project_search_types
                )
            );
        }
        $search_panes[] = $this->getSiteWidePane($site_search_types);

        return new Search_Presenter_SearchPresenter(
            $query->getTypeOfSearch(),
            $query->getWords(),
            $results,
            $search_panes,
            $query->getProject()
        );
    }

    private function getSiteWidePane($site_search_types = array()) {
        return new Search_SearchPanePresenter(
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
    }

    private function doSearch(Search_SearchQuery $query) {
        $search = new Search_SearchPlugin($this->event_manager);
        $plugin_results = $search->search($query);
        if ($plugin_results !== null) {
            return $plugin_results;
        }
        if (isset($this->search_types[$query->getTypeOfSearch()])) {
            $presenter = $this->search_types[$query->getTypeOfSearch()]->search($query);
            if ($presenter) {
                return $this->renderer->renderToString($presenter->getTemplate(), $presenter);
            }
        }
        return '';
    }
}
