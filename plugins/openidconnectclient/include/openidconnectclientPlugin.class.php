<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\OpenIDConnectClient\Authentication\AuthorizationDispatcher;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\StateFactory;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Authentication\StateStorage;
use Tuleap\OpenIDConnectClient\LoginConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\LoginController;
use Tuleap\OpenIDConnectClient\Provider\ProviderDao;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Router;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Zend\Loader\AutoloaderFactory;

require_once('constants.php');

class openidconnectclientPlugin extends Plugin {
    public function __construct($id) {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook(Event::LOGIN_ADDITIONAL_CONNECTOR);
        $this->addHook('anonymous_access_to_script_allowed');
    }

    /**
     * @return OpenIDConnectClientPluginInfo
     */
    public function getPluginInfo() {
        if (! is_a($this->pluginInfo, 'OpenIDConnectClientPluginInfo')) {
            $this->pluginInfo = new OpenIDConnectClientPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function anonymous_access_to_script_allowed($params) {
        $params['anonymous_allowed'] = strpos($params['script_name'], $this->getPluginPath()) === 0;
    }

    private function loadLibrary() {
        AutoloaderFactory::factory(
            array(
                'Zend\Loader\StandardAutoloader' => array(
                    'namespaces' => array(
                        'InoOicClient' => '/usr/share/php/InoOicClient/'
                    )
                )
            )
        );
    }

    /**
     * @return Flow
     */
    private function getFlow(ProviderManager $provider_manager) {
        $state_manager = new StateManager(
            new StateStorage(),
            new StateFactory(new RandomNumberGenerator())
        );
        $flow          = new Flow(
            $state_manager,
            new AuthorizationDispatcher($state_manager),
            $provider_manager
        );
        return $flow;
    }

    /**
     * @return bool
     */
    private function canPluginAuthenticateUser() {
        return ForgeConfig::get('sys_auth_type') !== 'ldap';
    }

    public function login_additional_connector(array $params) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
        }
        $this->loadLibrary();

        $provider_manager                  = new ProviderManager(new ProviderDao());
        $flow                              = $this->getFlow($provider_manager);
        $login_connector_presenter_builder = new LoginConnectorPresenterBuilder($provider_manager, $flow);
        $login_connector_presenter         = $login_connector_presenter_builder->getLoginConnectorPresenter(
            $params['return_to']
        );

        $renderer                        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $params['additional_connector'] .= $renderer->renderToString('login_connector', $login_connector_presenter);
    }

    public function process(HTTPRequest $request) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
        }
        $this->loadLibrary();

        $user_manager         = UserManager::instance();
        $provider_manager     = new ProviderManager(new ProviderDao());
        $user_mapping_manager = new UserMappingManager(new UserMappingDao());
        $flow                 = $this->getFlow($provider_manager);

        $login_controller     = new LoginController(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $flow
        );
        $router               = new Router($login_controller);
        $router->route($request);
    }
}