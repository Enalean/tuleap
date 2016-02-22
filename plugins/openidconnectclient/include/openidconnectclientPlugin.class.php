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

use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountDao;
use Tuleap\OpenIDConnectClient\AccountLinker\UnlinkedAccountManager;
use Tuleap\OpenIDConnectClient\AccountLinker;
use Tuleap\OpenIDConnectClient\Authentication\AuthorizationDispatcher;
use Tuleap\OpenIDConnectClient\Authentication\Flow;
use Tuleap\OpenIDConnectClient\Authentication\StateFactory;
use Tuleap\OpenIDConnectClient\Authentication\StateManager;
use Tuleap\OpenIDConnectClient\Authentication\StateStorage;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\Login;
use Tuleap\OpenIDConnectClient\Provider\ProviderDao;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\Router;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingDao;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserPreferencesPresenter;
use Tuleap\OpenIDConnectClient\UserMapping;
use Zend\Loader\AutoloaderFactory;

require_once('constants.php');

class openidconnectclientPlugin extends Plugin {
    public function __construct($id) {
        parent::__construct($id);

        $this->setScope(self::SCOPE_SYSTEM);

        $this->addHook(Event::LOGIN_ADDITIONAL_CONNECTOR);
        $this->addHook('anonymous_access_to_script_allowed');
        $this->addHook('cssfile');
        $this->addHook(Event::MANAGE_THIRD_PARTY_APPS);
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

    public function cssfile() {
        if (strpos($_SERVER['REQUEST_URI'], '/account') === 0) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getThemePath() .'/css/style.css" />';
        }
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
        if(! $params['is_secure']) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText('plugin_openidconnectclient', 'only_https_possible')
            );
            return;
        }
        $this->loadLibrary();

        $provider_manager                  = new ProviderManager(new ProviderDao());
        $flow                              = $this->getFlow($provider_manager);
        $login_connector_presenter_builder = new ConnectorPresenterBuilder($provider_manager, $flow);
        $login_connector_presenter         = $login_connector_presenter_builder->getLoginConnectorPresenter(
            $params['return_to']
        );

        $renderer                        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $params['additional_connector'] .= $renderer->renderToString('login_connector', $login_connector_presenter);
    }

    public function manage_third_party_apps(array $params) {
        $user                 = $params['user'];
        $user_mapping_manager = new UserMappingManager(new UserMappingDao());
        $user_mappings_usage  = $user_mapping_manager->getUsageByUser($user);

        if (count($user_mappings_usage) > 0) {
            $renderer        = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
            $csrf_token      = new CSRFSynchronizerToken('openid-connect-user-preferences');
            $presenter       = new UserPreferencesPresenter($user_mappings_usage, $csrf_token);
            $params['html'] .= $renderer->renderToString('user_preference', $presenter);
        }
    }

    public function process(HTTPRequest $request) {
        if(! $this->canPluginAuthenticateUser()) {
            return;
        }
        $this->loadLibrary();

        $user_manager             = UserManager::instance();
        $provider_manager         = new ProviderManager(new ProviderDao());
        $user_mapping_manager     = new UserMappingManager(new UserMappingDao());
        $unlinked_account_manager = new UnlinkedAccountManager(new UnlinkedAccountDao(), new RandomNumberGenerator());
        $flow                     = $this->getFlow($provider_manager);

        $login_controller          = new Login\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager,
            $flow
        );
        $account_linker_controller = new AccountLinker\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager,
            $unlinked_account_manager
        );
        $user_mapping_controller   = new UserMapping\Controller(
            $user_manager,
            $provider_manager,
            $user_mapping_manager
        );
        $router                    = new Router(
            $login_controller,
            $account_linker_controller,
            $user_mapping_controller);
        $router->route($request);
    }
}