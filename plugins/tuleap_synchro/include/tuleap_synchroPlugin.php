<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\TuleapSynchro\Dao\TuleapSynchroDao;
use Tuleap\TuleapSynchro\Endpoint\EndpointBuilder;
use Tuleap\TuleapSynchro\Endpoint\EndpointChecker;
use Tuleap\TuleapSynchro\Endpoint\EndpointCreatorController;
use Tuleap\TuleapSynchro\Endpoint\EndpointDeleteController;
use Tuleap\TuleapSynchro\Endpoint\EndpointUpdater;
use Tuleap\TuleapSynchro\ListEndpoints\ListEndpointsController;
use Tuleap\TuleapSynchro\ListEndpoints\ListEndpointsPresenterBuilder;
use Tuleap\TuleapSynchro\ListEndpoints\ListEndpointsRetriever;
use Tuleap\TuleapSynchro\Webhook\WebhookGenerator;

require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

class tuleap_synchroPlugin extends Plugin  // @codingStandardsIgnoreLine
{
    public const NAME = 'tuleap_synchro';

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_SYSTEM);

        bindtextdomain('tuleap-tuleap_synchro', __DIR__ . '/../site-content');
    }

    public function getPluginInfo()
    {
        if (!$this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\TuleapSynchro\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook('site_admin_option_hook');

        return parent::getHooksAndCallbacks();
    }

    public function routeGetAdmin(): ListEndpointsController
    {
        return new ListEndpointsController(
            new AdminPageRenderer(),
            new ListEndpointsRetriever(new TuleapSynchroDao(), new EndpointBuilder()),
            new ListEndpointsPresenterBuilder()
        );
    }

    public function routePostAddEndpoint(): EndpointCreatorController
    {
        return new EndpointCreatorController(
            new EndpointChecker(new Valid_HTTPURI()),
            new EndpointUpdater(new TuleapSynchroDao(), new WebhookGenerator(new TuleapSynchroDao(), 5))
        );
    }

    public function routePostDeleteEndpoint(): EndpointDeleteController
    {
        return new EndpointDeleteController(
            new EndpointUpdater(new TuleapSynchroDao(), new WebhookGenerator(new TuleapSynchroDao(), 5))
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->get('/admin/tuleap_synchro', $this->getRouteHandler('routeGetAdmin'));
        $event->getRouteCollector()->post('/admin/tuleap_synchro/add_endpoint', $this->getRouteHandler('routePostAddEndpoint'));
        $event->getRouteCollector()->post('/admin/tuleap_synchro/delete_endpoint', $this->getRouteHandler('routePostDeleteEndpoint'));
    }

    public function site_admin_option_hook(array $params) // @codingStandardsIgnoreLine
    {
        $params['plugins'][] = [
            'label' => dgettext('tuleap-tuleap_synchro', 'Tuleap to Tuleap'),
            'href'  => '/admin/' . self::NAME,
        ];
    }
}
