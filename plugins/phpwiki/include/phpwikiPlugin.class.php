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

        $this->name = 'phpwiki';
        $this->text = 'PHPWiki';

        $this->addHook(Event::LAYOUT_SEARCH_ENTRY);
        $this->addHook(Event::SEARCH_TYPE);
        $this->addHook(Event::SEARCH_TYPES_PRESENTERS);

        $this->addHook('backend_system_purge_files', 'purgeFiles');

        $this->addHook(Event::SERVICE_ICON);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        $this->addHook(Event::SERVICE_PUBLIC_AREAS);

        $this->addHook(Event::GET_SYSTEM_EVENT_CLASS);
        $this->addHook(Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE);
        $this->addHook('site_admin_option_hook');

        if ($this->isDocmanPluginActivated()) {
            $this->addHook(PLUGIN_DOCMAN_EVENT_GET_PHPWIKI_PAGE, 'getWikiPage');
        }

        $this->addHook('phpwiki_redirection');

        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);

        $this->addHook(Event::REST_PROJECT_GET_PHPWIKI);
        $this->addHook(Event::REST_PROJECT_OPTIONS_PHPWIKI);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::IS_IN_SITEADMIN);

        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);

    }

    private function isDocmanPluginActivated() {
        return defined('PLUGIN_DOCMAN_BASE_DIR');
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

    public function burning_parrot_get_stylesheets($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/account') === 0 || strpos($_SERVER['REQUEST_URI'], '/plugins/phpwiki') === 0) {
            $variant = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() .'/css/style-'. $variant->getName() .'.css';
        }
    }

    public function burning_parrot_get_javascript_files($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/phpwiki') === 0) {
            $params['javascript_files'][] = '/scripts/tuleap/manage-allowed-projects-on-resource.js';
        }
    }

    public function service_icon($params) {
        $params['list_of_icon_unicodes'][$this->getServiceShortname()] = '\e803';
    }

    public function service_public_areas($params) {
        if ($params['project']->usesService($this->getServiceShortname())) {
            $service   = $params['project']->getService($this->getServiceShortname());
            $wiki      = new PHPWiki($params['project']->getID());

            $presenter = new WidgetPublicAreaPresenter(
                $service->getUrl(),
                $GLOBALS['HTML']->getImagePath('ic/wiki.png'),
                $this->text,
                $wiki->getProjectPageCount()
            );
            $renderer          = TemplateRendererFactory::build()->getRenderer(PHPWIKI_TEMPLATE_DIR);
            $params['areas'][] = $renderer->renderToString('widget_public_area', $presenter);
        }
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

    public function purgeFiles($params) {
        $wiki_attachment = new PHPWikiAttachment();
        $wiki_attachment->purgeAttachments($params['time']);
    }

    public function getWikiPage($params) {
        $project_manager = ProjectManager::instance();
        $project         = $project_manager->getProject($params['project_id']);
        if ($project->usesService($this->getServiceShortname())) {
            $wiki_page              = new PHPWikiPage($params['project_id'], $params['wiki_page_name']);
            $params['phpwiki_page'] = $wiki_page;
        }
    }

    public function phpwiki_redirection($params) {
        $request       = HTTPRequest::instance();
        $project       = $request->getProject();
        if ($project && $project->usesService($this->getServiceShortname())) {
            $requested_uri = $request->getFromServer('REQUEST_URI');
            $new_uri       = preg_replace('/^\/wiki/', PHPWIKI_PLUGIN_BASE_URL, $requested_uri);
            $GLOBALS['Response']->redirect($new_uri);
        }
    }

    public function site_admin_option_hook($params)
    {
        $params['plugins'][] = array(
            'label' => $this->text,
            'href'  => $this->getPluginPath() . '/admin.php?action=index'
        );
    }

    /** @see Event::IS_IN_SITEADMIN */
    public function is_in_siteadmin($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath() .'/admin.php') === 0) {
            $params['is_in_siteadmin'] = true;
        }
    }

    public function system_event_get_types_for_default_queue(array &$params) {
        $params['types'] = array_merge($params['types'], array(SystemEvent_PHPWIKI_SWITCH_TO_PLUGIN::NAME));
    }

    public function get_system_event_class($params) {
        switch($params['type']) {
            case SystemEvent_PHPWIKI_SWITCH_TO_PLUGIN::NAME:
                $params['class']        = 'SystemEvent_PHPWIKI_SWITCH_TO_PLUGIN';
                $params['dependencies'] = array(
                    $this->getPHPWikiMigratorDao()
                );
        }
    }

    private function getPHPWikiMigratorDao() {
        return new PHPWikiMigratorDao();
    }

    public function services_truncated_emails($params) {
        $project = $params['project'];
        if ($project->usesService($this->getServiceShortname())) {
            $params['services'][] = $GLOBALS['Language']->getText('plugin_phpwiki', 'service_lbl_key');
        }
    }

    public function rest_project_get_phpwiki($params) {
        $user    = $params['user'];
        $project = $params['project'];

        if (! $this->userCanAccessPhpWikiService($user, $project)) {
            $class_exception = 'Luracast\Restler\RestException';
            throw new $class_exception(403, 'You are not allowed to access the PHPWiki plugin');
        }

        if ($project->usesService($this->getServiceShortname())) {
            $class            = 'Tuleap\PhpWiki\REST\v1\ProjectResource';
            $project_resource = new $class($this->getPaginatedPHPWikiPagesFactory());
            $project          = $params['project'];

            $params['result'] = $project_resource->getPhpWikiPlugin(
                $user,
                $project->getID(),
                $params['limit'],
                $params['offset'],
                $params['pagename']
            );
        }
    }

    public function rest_project_options_phpwiki($params) {
        $params['activated'] = true;
    }

    /**
     * @return bool
     */
    private function userCanAccessPhpWikiService(PFUser $user, Project $project) {
        $wiki = new PHPWiki($project->getID());
        return $wiki->isAutorized($user->getId());
    }

    /**
     * @return PaginatedPHPWikiPagesFactory
     */
    private function getPaginatedPHPWikiPagesFactory() {
        return new PaginatedPHPWikiPagesFactory(new PHPWikiDao());
    }

    /**
     * @see REST_RESOURCES
     */
    public function rest_resources($params) {
        $injector = new PHPWikiPlugin_REST_ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /**
     * @see Event::REST_PROJECT_RESOURCES
     */
    public function rest_project_resources(array $params) {
        $injector = new PHPWikiPlugin_REST_ResourcesInjector();
        $injector->declarePhpWikiPluginResource($params['resources'], $params['project']);
    }
}
