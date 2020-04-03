<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use FastRoute\RouteCollector;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Bugzilla\Administration\Controller;
use Tuleap\Bugzilla\Administration\Router;
use Tuleap\Bugzilla\CrossReferenceCreator;
use Tuleap\Bugzilla\Plugin\Info;
use Tuleap\Bugzilla\Reference\Dao;
use Tuleap\Bugzilla\Reference\ReferenceDestructor;
use Tuleap\Bugzilla\Reference\ReferenceRetriever;
use Tuleap\Bugzilla\Reference\ReferenceSaver;
use Tuleap\Bugzilla\Reference\RESTReferenceCreator;
use Tuleap\Http\HttpClientFactory;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\reference\ReferenceValidator;
use Tuleap\reference\ReservedKeywordsRetriever;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class bugzilla_referencePlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-bugzilla_reference', BUGZILLA_REFERENCE_BASE_DIR . '/site-content');

        $this->addHook('site_admin_option_hook', 'addSiteAdministrationOptionHook');
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(Event::GET_AVAILABLE_REFERENCE_NATURE);
        $this->addHook(Event::POST_REFERENCE_EXTRACTED);
        $this->addHook(Event::REMOVE_CROSS_REFERENCE);
        $this->addHook(Event::GET_REFERENCE_ADMIN_CAPABILITIES);
        $this->addHook(Event::CAN_USER_CREATE_REFERENCE_WITH_THIS_NATURE);
        $this->addHook(CollectRoutesEvent::NAME);
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

    public function routeAdmin(): DispatchableWithRequest
    {
        $encryption_key = $this->getEncryptionKey();

        $controller = new Controller(
            new AdminPageRenderer(),
            new ReferenceSaver(
                new Dao(),
                new ReferenceValidator(
                    new ReferenceDao(),
                    new ReservedKeywordsRetriever(EventManager::instance())
                ),
                $this->getReferenceRetriever(),
                ReferenceManager::instance(),
                $encryption_key
            ),
            $this->getReferenceRetriever(),
            new ReferenceDestructor(new Dao())
        );

        return new Router($controller);
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addGroup($this->getPluginPath(), function (RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/admin/[index.php]', $this->getRouteHandler('routeAdmin'));
        });
    }

    public function get_plugins_available_keywords_references(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        foreach ($this->getReferenceRetriever()->getAllReferences() as $reference) {
            $params['keywords'][] = $reference->getKeyword();
        }
    }

    public function get_available_reference_natures($params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['natures']['bugzilla'] =
            array(
                'keyword' => 'bugzilla',
                'label'   => dgettext('tuleap-bugzilla_reference', 'Bugzilla')
            );
    }

    /**
     * @return ReferenceRetriever
     */
    private function getReferenceRetriever()
    {
        return new ReferenceRetriever(new Dao(), $this->getEncryptionKey());
    }

    /**
     * @return \Tuleap\Cryptography\Symmetric\EncryptionKey
     */
    private function getEncryptionKey()
    {
        $key_factory = new \Tuleap\Cryptography\KeyFactory();
        return $key_factory->getEncryptionKey();
    }

    /** @see \Event::POST_REFERENCE_EXTRACTED */
    public function post_reference_extracted(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $cross_reference = $params['cross_reference'];
        \assert($cross_reference instanceof CrossReference);

        $bugzilla = $this->getBugzillaReferenceFromKeyword($cross_reference->targetKey);
        if (! $bugzilla) {
            return;
        }

        $this->getCrossReferenceCreator()->create($cross_reference, $bugzilla);
    }

    private function getCrossReferenceCreator()
    {
        return new CrossReferenceCreator(new CrossReferenceDao(), $this->getRESTReferenceCreator());
    }

    private function getRESTReferenceCreator(): RESTReferenceCreator
    {
        return new RESTReferenceCreator(
            HttpClientFactory::createClient(),
            HTTPFactoryBuilder::requestFactory(),
            HTTPFactoryBuilder::streamFactory(),
            \BackendLogger::getDefaultLogger('bugzilla_syslog')
        );
    }

    /** @see \Event::REMOVE_CROSS_REFERENCE */
    public function remove_cross_reference(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $cross_reference = $params['cross_reference'];
        \assert($cross_reference instanceof CrossReference);

        $bugzilla = $this->getBugzillaReferenceFromKeyword($cross_reference->targetKey);
        if (! $bugzilla) {
            return;
        }

        $dao = new CrossReferenceDao();
        $params['is_reference_removed'] = $dao->deleteFullCrossReference($cross_reference);
    }

    /** @see \Event::GET_REFERENCE_ADMIN_CAPABILITIES */
    public function get_reference_admin_capabilities(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $reference = $params['reference'];
        \assert($reference instanceof Reference);

        if ($reference->getNature() === 'bugzilla') {
            $params['can_be_deleted'] = false;
            $params['can_be_edited']  = false;
        }
    }

    /** @see \Event::CAN_USER_CREATE_REFERENCE_WITH_THIS_NATURE */
    public function can_user_create_reference_with_this_nature(array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['nature'] === 'bugzilla') {
            $params['can_create'] = false;
        }
    }

    /**
     * @return null|\Tuleap\Bugzilla\Reference\Reference
     */
    private function getBugzillaReferenceFromKeyword($keyword)
    {
        return $this->getReferenceRetriever()->getReferenceByKeyword($keyword);
    }
}
