<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

use Tuleap\Layout\FooterConfiguration;

class Search_SearchController // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const DEFAULT_SEARCH = Search_SearchProject::NAME;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

    private $search_types = [];

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
        $this->renderer      = TemplateRendererFactory::build()->getRenderer(
            [
                ForgeConfig::get('codendi_dir') . '/src/templates/search',
            ]
        );
        $this->search_types  = [
            Search_SearchTrackerV3::NAME => new Search_SearchTrackerV3(new ArtifactDao()),
            Search_SearchProject::NAME   => new Search_SearchProject(new ProjectDao()),
            Search_SearchPeople::NAME    => new Search_SearchPeople(UserManager::instance()),
            Search_SearchForum::NAME     => new Search_SearchForum(new ForumDao()),
            Search_SearchWiki::NAME      => new Search_SearchWiki(new WikiDao()),
        ];
    }

    public function index(Codendi_Request $request)
    {
        if (! $request->get('type_of_search')) {
            $request->set('type_of_search', Search_SearchProject::NAME);
        }

        $this->results($request);
    }

    public function error(Codendi_Request $request, Search_SearchQuery $query)
    {
        $empty_result = new Search_SearchResults();

        $GLOBALS['HTML']->header(
            \Tuleap\Layout\HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('search_index', 'search'))
                ->withBodyClass(['search-page'])
                ->build()
        );
        $this->renderer->renderToPage('site-search', $this->getSearchPresenter($query, $empty_result->getResultsHtml()));
        $GLOBALS['HTML']->footer(FooterConfiguration::withoutContent());
    }

    public function ajaxResults(Codendi_Request $request)
    {
        $query = new Search_SearchQuery($request);
        $query->setNumberOfResults(Search_SearchPlugin::RESULTS_PER_QUERY);

        if (! $query->isValid()) {
            $GLOBALS['Response']->send400JSONErrors($GLOBALS['Language']->getText('search_index', 'at_least_3_ch'));
        }

        $results = $this->doSearch($query);
        $output  = [
            'has_more'      => $results->hasMore(),
            'html'          => '',
            'results_count' => $results->getCountResults(),
        ];

        if ($results->getResultsHtml() !== '') {
            $output['html'] = $this->renderer->renderToString('results', ['search_result' => $results->getResultsHtml()]);
        }

        echo json_encode($output);
    }

    public function results(Codendi_Request $request)
    {
        $query = new Search_SearchQuery($request);
        $query->setNumberOfResults(Search_SearchPlugin::RESULTS_PER_QUERY);

        if (! $query->isValid()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('search_index', 'at_least_3_ch'));
            $this->error($request, $query);
            return;
        }

        $results = $this->doSearch($query);
        if ($results->getResultsHtml() !== '') {
            $this->renderResults($query, $results->getResultsHtml());
        }
    }

    private function renderResults(Search_SearchQuery $query, $results)
    {
        $GLOBALS['HTML']->header(
            \Tuleap\Layout\HeaderConfigurationBuilder::get($GLOBALS['Language']->getText('search_index', 'search'))
                ->withBodyClass(['search-page'])
                ->build()
        );
        $this->renderer->renderToPage('site-search', $this->getSearchPresenter($query, $results));
        $GLOBALS['HTML']->footer(FooterConfiguration::withoutContent());
    }

    private function getSearchPresenter(Search_SearchQuery $query, $results)
    {
        $project_search_types = [];
        $site_search_types    = [];
        $redirect_to_services = true;

        $this->event_manager->processEvent(
            Event::SEARCH_TYPES_PRESENTERS,
            [
                'project'              => $query->getProject(),
                'words'                => $query->getWords(),
                'project_presenters'   => &$project_search_types,
                'site_presenters'      => &$site_search_types,
                'redirect_to_services' => &$redirect_to_services,
            ]
        );

        $additional_project_search_types = $this->getAdditionnalProjectWidePresentersIfNeeded(
            $query->getProject(),
            $query->getWords(),
            $redirect_to_services
        );
        $project_search_types            = array_merge($additional_project_search_types, $project_search_types);

        $search_panes = [];
        if (! $query->getProject()->isError()) {
            $project_name   = $query->getProject()->getPublicName();
            $search_panes[] = new Search_SearchPanePresenter(
                $GLOBALS['Language']->getText('search_index', 'project_wide_search', $project_name),
                $project_search_types,
                $GLOBALS['Language']->getText('search_index', 'no_searchable_services')
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

    private function getAdditionnalProjectWidePresentersIfNeeded(Project $project, $words, $redirect_to_services)
    {
        $additionnal_presenters = [];

        if ($project->usesService('wiki')) {
            $search_wiki              = new Search_SearchWiki(new WikiDao());
            $additionnal_presenters[] = $search_wiki->getFacets($project->getID(), $words);
        }

        if ($project->usesService('tracker')) {
            $search_tracker           = new Search_SearchTrackerV3(new ArtifactDao());
            $additionnal_presenters[] = $search_tracker->getFacets($project);
        }

        return $additionnal_presenters;
    }

    private function getSiteWidePane($site_search_types = [])
    {
        $search_types = [
            new Search_SearchTypePresenter(
                Search_SearchProject::NAME,
                $GLOBALS['Language']->getText('search_index', 'soft')
            ),
            new Search_SearchTypePresenter(
                Search_SearchPeople::NAME,
                $GLOBALS['Language']->getText('search_index', 'people')
            ),
        ];

        return new Search_SearchPanePresenter(
            $GLOBALS['Language']->getText('search_index', 'site_wide_search'),
            array_merge($search_types, $site_search_types),
            ''
        );
    }

    /**
     * @return Search_SearchResults
     */
    private function doSearch(Search_SearchQuery $query)
    {
        $reference_manager = ReferenceManager::instance();
        $references        = $reference_manager->extractReferences(
            $query->getWords(),
            (int) $query->getProject()->getId()
        );
        if (count($references) === 1 && $references[0]->getMatch() === trim($query->getWords())) {
            $GLOBALS['HTML']->redirect($references[0]->getFullGotoLink());
        }

        $results = new Search_SearchResults();

        $search = new Search_SearchPlugin($this->event_manager);
        $search->search($query, $results);

        if ($results->getResultsHtml() !== '') {
            return $results;
        }
        if (! isset($this->search_types[$query->getTypeOfSearch()])) {
            return $results;
        }

        $presenter = $this->search_types[$query->getTypeOfSearch()]->search($query, $results);
        if ($presenter) {
            if ($query->isAjax() && $query->getOffset() > 0) {
                $results->setResultsHtml($this->renderer->renderToString($presenter->getTemplate() . '-more', $presenter));
            } else {
                $results->setResultsHtml($this->renderer->renderToString($presenter->getTemplate(), $presenter));
            }
        }

        return $results;
    }
}
