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
        $this->renderer->renderToPage('index', new Search_SearchPresenter(self::DEFAULT_SEARCH, '', null, ''));
        $GLOBALS['HTML']->footer(array());
    }

    public function ajaxResults(Codendi_Request $request) {
        if (! $this->validateKeywords($request->get('words'))) {
            return;
        }

        $presenter = $this->doSearch($request);
        if ($presenter !== null) {
            $this->renderer->renderToPage('results', $presenter);
        }
    }

    public function results(Codendi_Request $request) {
        if (! $this->validateKeywords($request->get('words'))) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('search_index', 'at_least_3_ch'));
            $GLOBALS['Response']->redirect('/search/');
        }

        $presenter = $this->doSearch($request);
        if ($presenter !== null) {
            $GLOBALS['HTML']->header(array('title' => $GLOBALS['Language']->getText('search_index', 'search'), 'body_class' => array('search-page')));
            $this->renderer->renderToPage('index', $presenter);
            $GLOBALS['HTML']->footer(array());
        }
    }

    private function doSearch(Codendi_Request $request) {
        $type_of_search = $request->get('type_of_search');
        $words          = $request->get('words');
        $offset         = intval($request->getValidated('offset', 'uint', 0));
        $exact          = $request->getValidated('exact', 'uint', false);
        $group_id       = $request->get('group_id');

        if (! $this->isRedirectedSearch($type_of_search)) {
            ob_start();
            switch ($type_of_search) {
                case Search_SearchTrackerV3::NAME:
                    $search = new Search_SearchTrackerV3(new ArtifactDao());
                    $search->search($group_id, $words, $exact, $offset, $request->getValidated('atid', 'uint', 0));
                    break;

                case Search_SearchProject::NAME:
                    $search = new Search_SearchProject(new ProjectDao());
                    $this->renderer->renderToPage(
                        'search_project',
                        $search->search(
                            $words,
                            $exact,
                            $offset
                        )
                    );
                    break;

                case Search_SearchPeople::NAME:
                    $search = new Search_SearchPeople(new UserDao());
                    $this->renderer->renderToPage(
                        'search_people',
                        $search->search(
                            $words,
                            $exact,
                            $offset
                        )
                    );
                    break;

                case Search_SearchForum::NAME:
                    $search = new Search_SearchForum(new ForumDao());
                    $this->renderer->renderToPage(
                        'search_forum',
                        $search->search(
                            $words,
                            $exact,
                            $offset,
                            $request->getValidated('forum_id', 'uint', 0)
                        )
                    );
                    break;

                case Search_SearchSnippet::NAME:
                    $search = new Search_SearchSnippet(new SnippetDao());
                    $search->search($words, $exact, $offset);
                    break;

                default:
                    break;
            }
            return new Search_SearchPresenter(
                $type_of_search,
                $words,
                $group_id,
                ob_get_clean()
            );
        } else {
            switch ($type_of_search) {
                case Search_SearchWiki::NAME:
                    $search = new Search_SearchWiki(new WikiDao());
                    $search->search($group_id, $words);
                    break;

                default:
                    $search = new Search_SearchPlugin($this->event_manager);
                    $search->search($group_id, $type_of_search, $words, $offset);
                    break;
            }
            return null;
        }
    }

    private function isRedirectedSearch($type_of_search) {
        $plugins_powered_search = false;
        $this->event_manager->processEvent(
            Event::PLUGINS_POWERED_SEARCH,
            array(
                'type_of_search'         => $type_of_search,
                'plugins_powered_search' => &$plugins_powered_search
            )
        );

        return $plugins_powered_search || $type_of_search == Search_SearchWiki::NAME;
    }

    private function validateKeywords($words) {
        if (strlen($words) < 3) {
            return false;
        }

        return true;
    }
}
