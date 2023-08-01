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
use Tuleap\Admin\SiteAdministrationAddOption;
use Tuleap\Admin\SiteAdministrationPluginOption;
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
use Tuleap\Reference\CrossReference;
use Tuleap\Reference\CrossReferencesDao;
use Tuleap\Reference\Nature;
use Tuleap\Reference\ReferenceValidator;
use Tuleap\Reference\ReservedKeywordsRetriever;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Reference\NatureCollection;

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';

class bugzilla_referencePlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($id)
    {
        parent::__construct($id);

        bindtextdomain('tuleap-bugzilla_reference', BUGZILLA_REFERENCE_BASE_DIR . '/site-content');

        $this->addHook(SiteAdministrationAddOption::NAME);
        $this->addHook(Event::GET_PLUGINS_AVAILABLE_KEYWORDS_REFERENCES);
        $this->addHook(NatureCollection::NAME);
        $this->addHook(Event::POST_REFERENCE_EXTRACTED);
        $this->addHook(Event::REMOVE_CROSS_REFERENCE);
        $this->addHook(Event::GET_REFERENCE_ADMIN_CAPABILITIES);
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

    public function siteAdministrationAddOption(SiteAdministrationAddOption $site_administration_add_option): void
    {
        $site_administration_add_option->addPluginOption(
            SiteAdministrationPluginOption::build($this->getPluginInfo()->getPluginDescriptor()->getFullName(), BUGZILLA_REFERENCE_BASE_URL . '/admin/')
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

    public function getAvailableReferenceNatures(NatureCollection $natures): void
    {
        $natures->addNature(
            'bugzilla',
            new Nature(
                'bugzilla',
                Nature::NO_ICON,
                dgettext('tuleap-bugzilla_reference', 'Bugzilla'),
                false
            )
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

    private function getCrossReferenceCreator(): CrossReferenceCreator
    {
        return new CrossReferenceCreator(new CrossReferencesDao(), $this->getRESTReferenceCreator());
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

        $dao                            = new \Tuleap\Reference\CrossReferencesDao();
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

    /**
     * @return null|\Tuleap\Bugzilla\Reference\Reference
     */
    private function getBugzillaReferenceFromKeyword($keyword)
    {
        return $this->getReferenceRetriever()->getReferenceByKeyword($keyword);
    }
}
