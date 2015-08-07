<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once 'constants.php';

class phpwikiPlugin extends Plugin {

    const SEARCH_PAGENAME_EN = 'FullTextSearch';
    const SEARCH_PAGENAME_FR = 'RechercheEnTexteIntégral';

    public function __construct($id) {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::SEARCH_TYPE);
        $this->addHook(Event::SEARCH_TYPES_PRESENTERS);

        $this->addHook('backend_system_purge_files', 'purgeFiles');

        $this->name = 'phpwiki';
        $this->text = 'PHPWiki';

        $this->addHook(Event::SERVICE_ICON);

        $this->addHook('phpwiki_redirection');
    }

    public function getPluginInfo() {
        if (!is_a($this->pluginInfo, 'PHPWikiPluginInfo')) {
            $this->pluginInfo = new PHPWikiPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function getServiceShortname() {
        return 'plugin_phpwiki';
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e803';
    }

    public function process(HTTPRequest $request) {
        $wiki = new PHPWikiService($request->get('group_id'));
        $wiki->process();
    }

    public function processAdmin(HTTPRequest $request) {
        $wiki = new PHPWikiServiceAdmin($request->get('group_id'));
        $wiki->process();
    }

    public function processUpload(HTTPRequest $request) {
        $attch       = new PHPWikiAttachment();
        $request_uri = preg_replace('/^\/wiki/', PHPWIKI_PLUGIN_BASE_URL, $request->getFromServer('REQUEST_URI'));
        $attch->setUri($request_uri);
        if($attch->exist() && $attch->isActive()) {
            if($attch->isAutorized(user_getid())) {
                $attch->htmlDump();
            }
        }
        else {
            exit_error($GLOBALS['Language']->getText('global','error'),
                       $GLOBALS['Language']->getText('plugin_phpwiki_attachment_upload', 'err_not_exist'));
        }
    }

    public function layout_search_entry($params) {
        $is_in_phpwiki = strpos($_SERVER['REQUEST_URI'], PHPWIKI_PLUGIN_BASE_URL . '/') !== false;
        $params['search_entries'][] = array(
            'value'    => $this->name,
            'label'    => $this->text,
            'selected' => $is_in_phpwiki,
        );
    }

    public function search_type($params) {
        $query   = $params['query'];
        $project = $query->getProject();
        if ($query->getTypeOfSearch() === $this->name) {
            if (!$project->isError()) {
                util_return_to($this->getPhpwikiSearchURI($project, $query->getWords()));
            }
        }
    }

    public function search_types_presenters($params) {
        if ($this->isSearchEntryAvailable($params['project'])) {
            $params['project_presenters'][] = new Search_SearchTypePresenter(
                $this->name,
                $this->text,
                array(),
                $this->getPhpwikiSearchURI($params['project'], $params['words'])
            );
        }
    }

    private function isSearchEntryAvailable(Project $project = null) {
        if ($project && !$project->isError()) {
            return $project->usesService('plugin_phpwiki');
        }
        return false;
    }

    private function getPhpwikiSearchURI(Project $project, $words) {
        $project_id = $project->getID();
        $page_name  = $this->getSearchPageName($project->getID());
        return $this->getPluginPath() . '/index.php?group_id=' . $project_id . '&pagename=' . urlencode($page_name) . '&s=' . urlencode($words);
    }

    private function getSearchPageName($project_id) {
        $wiki_dao    = new PHPWikiDao();
        $search_page = self::SEARCH_PAGENAME_EN;
        if ($wiki_dao->searchLanguage($project_id) == 'fr_FR') {
            $search_page = self::SEARCH_PAGENAME_FR;
        }

        return $search_page;
    }

    public function purgeFiles($time) {
        $wiki_attachment = new PHPWikiAttachment();
        $wiki_attachment->purgeAttachments($time);
    }

    public function phpwiki_redirection($params) {
        $request       = HTTPRequest::instance();
        $requested_uri = $request->getFromServer('REQUEST_URI');
        $new_uri       = preg_replace('/^\/wiki/', PHPWIKI_PLUGIN_BASE_URL, $requested_uri);
        $GLOBALS['Response']->redirect($new_uri);
    }
}
