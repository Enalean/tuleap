<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Bugzilla\Administration\Controller;
use Tuleap\Bugzilla\Administration\Router;
use Tuleap\Bugzilla\Plugin\Info;
use Tuleap\Bugzilla\BugzillaLogger;
use Tuleap\Bugzilla\Reference\BugzillaReference;
use Tuleap\Bugzilla\Reference\Dao;
use Tuleap\Bugzilla\Reference\ReferenceDestructor;
use Tuleap\Bugzilla\Reference\ReferenceRetriever;
use Tuleap\Bugzilla\Reference\ReferenceSaver;
use Tuleap\Bugzilla\Reference\RESTReferenceCreator;
use Tuleap\reference\ReferenceValidator;
use Tuleap\reference\ReservedKeywordsRetriever;

require_once 'constants.php';

class bugzilla_referencePlugin extends Plugin
{
    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-bugzilla_reference', BUGZILLA_REFERENCE_BASE_DIR . '/site-content');

        $this->addHook('site_admin_option_hook', 'addSiteAdministrationOptionHook');
        $this->addHook(Event::IS_IN_SITEADMIN, 'isInSiteAdmin');
        $this->addHook(Event::BURNING_PARROT_GET_JAVASCRIPT_FILES);
        $this->addHook(Event::BURNING_PARROT_GET_STYLESHEETS);
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::POST_REFERENCE_EXTRACTED);
        $this->addHook(Event::GET_REFERENCE);
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo instanceof Info) {
            $this->pluginInfo = new Info($this);
        }

        return $this->pluginInfo;
    }

    public function addSiteAdministrationOptionHook(array $params)
    {
        $params['plugins'][] = array(
            'label' => $this->getPluginInfo()->getPluginDescriptor()->getFullName(),
            'href'  => BUGZILLA_REFERENCE_BASE_URL . '/admin/'
        );
    }

    public function isInSiteAdmin(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], BUGZILLA_REFERENCE_BASE_URL . '/admin/') === 0) {
            $params['is_in_siteadmin'] = true;
        }
    }

    public function processAdmin(HTTPRequest $request)
    {
        $controller = new Controller(
            new AdminPageRenderer(),
            new ReferenceSaver(
                new Dao(),
                new ReferenceValidator(
                    new ReferenceDao(),
                    new ReservedKeywordsRetriever(EventManager::instance())
                ),
                $this->getReferenceRetriever()
            ),
            $this->getReferenceRetriever(),
            new ReferenceDestructor(new Dao())
        );

        $router = new Router($controller);
        $router->route($request);
    }

    public function burning_parrot_get_javascript_files(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], $this->getPluginPath()) === 0) {
            $params['javascript_files'][] = BUGZILLA_REFERENCE_BASE_URL . '/scripts/bugzilla-reference.js';
        }
    }

    public function burning_parrot_get_stylesheets(array $params)
    {
        if (strpos($_SERVER['REQUEST_URI'], '/plugins/bugzilla_reference') === 0) {
            $variant                 = $params['variant'];
            $params['stylesheets'][] = $this->getThemePath() . '/css/style-' . $variant->getName() . '.css';
        }
    }

    public function get_plugins_available_keywords_references(array $params)
    {
        foreach ($this->getReferenceRetriever()->getAllReferences() as $reference) {
            $params['keywords'][] = $reference->getKeyword();
        }
    }

    /**
     * @return ReferenceRetriever
     */
    private function getReferenceRetriever()
    {
        $reference_retriever = new ReferenceRetriever(new Dao());

        return $reference_retriever;
    }

    public function post_reference_extracted(array $params)
    {
        $this->getRESTReferenceCreator()->create(
            $params['source_link'],
            $params['target_keyword'],
            $params['source_id'],
            $params['target_id'],
            $params['source_keyword']
        );
    }

    private function getRESTReferenceCreator()
    {
        return new RESTReferenceCreator($this->getReferenceRetriever(), new Http_Client(), new BugzillaLogger());
    }

    public function get_reference($params)
    {
        $reference = $this->getReferenceRetriever()->getReferenceByKeyword($params['keyword']);
        if (! $reference) {
            return;
        }

        $params['reference'] = new BugzillaReference($reference);
    }
}
